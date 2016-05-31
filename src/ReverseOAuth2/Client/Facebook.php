<?php

namespace ReverseOAuth2\Client;

use \ReverseOAuth2\AbstractOAuth2Client;
use \Zend\Http\PhpEnvironment\Request;

class Facebook extends AbstractOAuth2Client
{

    protected $providerName = 'facebook';
     /**
     *
     * @var array
     */
    protected $fields;

    /**
     *
     * @param array $fields
     */
    public function setFields($fields)
    {
        if (!is_array($fields)) {
            throw new Exception("Error fields not is an array.");
        } elseif (!count($fields)) {
            throw new Exception("fields do not exist.");
        }

        $this->fields = $fields;

    }

    public function getFields()
    {
        if (!$this->fields) {
            $this->fields = array('id', 'name');
        }

        return $this->fields;

    }
    
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

            $client->setHeaders(array('Accept-encoding' => 'utf-8'));

            $client->setMethod(Request::METHOD_POST);
            
            $client->setParameterPost(array(
                'code'          => $request->getQuery('code'),
                'client_id'     => $this->options->getClientId(),
                'client_secret' => $this->options->getClientSecret(),
                'redirect_uri'  => $this->options->getRedirectUri()
            ));
            
            $retVal = $client->send()->getContent();
            
            parse_str($retVal, $token);
            
            if(is_array($token) AND isset($token['access_token']) AND $token['expires'] > 0) {
                
                $this->session->token = (object)$token;
                return true;
                
            } else {
                
                try {
                    
                    $error = \Zend\Json\Decoder::decode($retVal);
                    $this->error = array(
                        'internal-error' => 'Facebook settings error.',
                        'message' => $error->error->message,
                        'type' => $error->error->type,
                        'code' => $error->error->code
                    );
                    
                } catch(\Zend\Json\Exception\RuntimeException $e) {
                    
                    $this->error = $token;
                    $this->error['internal-error'] = 'Unknown error.';
                                        
                }
                
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

    public function getInfo()
    {
        if(is_object($this->session->info)) {

            return $this->session->info;

        } elseif(isset($this->session->token->access_token)) {

            $client = $this->getHttpclient()
                ->resetParameters(true)
                ->setHeaders(array('Accept-encoding' => 'utf-8'))
                ->setMethod(Request::METHOD_GET)
                ->setUri($this->options->getInfoUri());

            $client->setParameterGet(array(
                'access_token' => $this->session->token->access_token,
                'fields' => implode(',', array_unique($this->fields))
            ));

            $retVal = $client->send()->getContent();

            if(strlen(trim($retVal)) > 0) {

                $this->session->info = \Zend\Json\Decoder::decode($retVal);
                return $this->session->info;

            } else {

                $this->error = array('internal-error' => 'Get info return value is empty.');
                return false;

            }

        } else {

            $this->error = array('internal-error' => 'Session access token not found.');
            return false;

        }
    }
    
}
