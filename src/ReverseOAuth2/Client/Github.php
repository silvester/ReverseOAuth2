<?php

namespace ReverseOAuth2\Client;

use \ReverseOAuth2\AbstractOAuth2Client;
use \Zend\Http\PhpEnvironment\Request;

class Github extends AbstractOAuth2Client
{

    protected $providerName = 'github';
    
    public function getUrl()
    {
        
        $url = $this->options->getAuthUri().'?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope(',');

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
                'state'         => $this->getState()
            ));
            
            $retVal = $client->send()->getContent();
            
            parse_str($retVal, $token);      
            
            if(is_array($token) AND isset($token['access_token'])) {
                
                $this->session->token = (object)$token;
                return true;
                
            } else {
                
                $this->error = array('error' => $retVal, 'internal-error' => 'Unknown error.');                
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