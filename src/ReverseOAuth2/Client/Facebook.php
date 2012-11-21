<?php

namespace ReverseOAuth2\Client;

use \ReverseOAuth2\AbstractOAuth2Client;
use \Zend\Http\PhpEnvironment\Request;

class Facebook extends AbstractOAuth2Client
{

    protected $providerName = 'facebook';
    
    public function getUrl()
    {
        
        $url = $this->options->getAuthUri().'?'
            . 'redirect_uri='  . urlencode($this->options->getRedirectUri())
            . '&client_id='    . $this->options->getClientId()
            . '&state='        . $this->generateState()
            . $this->getScope();

        return $url;
        
    }
    
    
    public function getToken(Request $request) 
    {
        
        if(isset($this->session->token)) {
        
            return true;
            
        } elseif($this->session->state == $request->getQuery('state') AND strlen($request->getQuery('code')) > 5) {
            
            $client = new \Zend\Http\Client($this->options->getTokenUri(), array('timeout' => 30, 'adapter' => 'Zend\Http\Client\Adapter\Curl'));
            $client->setMethod(Request::METHOD_POST);
            $client->setParameterPost(array(
                'code'          => $request->getQuery('code'),
                'client_id'     => $this->options->getClientId(),
                'client_secret' => $this->options->getClientSecret(),
                'redirect_uri'  => $this->options->getRedirectUri()
            ));
            
            parse_str($client->send()->getContent(), $token);
            
            if(is_array($token) AND isset($token['access_token']) AND $token['expires'] > 0) {
                $this->session->token = (object)$token;
            } elseif(is_array($token) AND isset($token['error'])) {
                $this->error = $token;
                return false;
            } else {
                $this->error = 'Facebook service not available.';
                return false;
            }
                        
            return true;
            
        } else {

            $this->error = array(
                'session-state' => $this->session->state,
                'request-state' => $request->getQuery('state'),
                'code'          => $request->getQuery('code')
            );
            return false;
            
        }
        
    }
    
    
    public function getScope()
    {
        if(count($this->options->getScope()) > 0) {
            $str = urlencode(implode(',', $this->options->getScope()));
            return '&scope=' . $str;
        } else {
            return '';
        }
    }
    
}