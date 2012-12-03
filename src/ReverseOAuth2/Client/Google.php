<?php

namespace ReverseOAuth2\Client;

use ReverseOAuth2\AbstractOAuth2Client;
use ReverseOAuth2\ClientOptions;
use Zend\Http\PhpEnvironment\Request;

class Google extends AbstractOAuth2Client
{
    
    protected $providerName = 'google';

    public function getUrl()
    {
        
        $url = $this->options->getAuthUri().'?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&response_type=code'
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope(' ');

        return $url;
        
    }
    
    
    public function getToken(Request $request) 
    {
        
        if(isset($this->session->token)) {
            
            return true;
            
        } elseif(strlen($this->session->state) > 0 AND $this->session->state == $request->getQuery('state') AND strlen($request->getQuery('code')) > 5) {
            
            $client = $this->getHttpClient();
            
            $client->setUri($this->options->getTokenUri());
            
            $client->setMethod(Request::METHOD_POST);
            
            $client->setParameterPost(array(
                'code'          => $request->getQuery('code'),
                'client_id'     => $this->options->getClientId(),
                'client_secret' => $this->options->getClientSecret(),
                'redirect_uri'  => $this->options->getRedirectUri(),
                'grant_type'    => 'authorization_code'
            ));
            
            $retVal = $client->send()->getContent();
            
            try {
                
                $token = \Zend\Json\Decoder::decode($retVal);
                
                if(isset($token->access_token) AND $token->expires_in > 0) {
                    
                    $this->session->token = $token;
                    
                    return true;
                    
                } else {
                    
                    $this->error  = array(
                        'internal-error' => 'Google settings error.',
                        'error'          => $token->error,
                        'token'          => $token
                    );
                    
                    return false;

                }

            } catch (\Zend\Json\Exception\RuntimeException $e) {
                
                $this->error['internal-error'] = 'Unknown error.';
                $this->error['token'] = $retVal;
                
                return false;
                
            }
                
        } else {
            
            $this->error = array(
                'internal-error'=> 'State error, request variables do not match the session variables.',
                'session-state' => $this->session->state, 
                'request-state' => $request->getQuery('state'), 
                'code'          => $request->getQuery('code')
            );
            
            return false;
            
        }
        
    }
    
}
