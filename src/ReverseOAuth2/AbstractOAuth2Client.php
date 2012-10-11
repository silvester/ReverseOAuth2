<?php

namespace ReverseOAuth2;

use Zend\Http\PhpEnvironment\Request;

abstract class AbstractOAuth2Client
{
    
    protected $session;
    protected $options;
    protected $error;

    public function __construct()
    {
        $this->session = new \Zend\Session\Container('ReverseOAuth2_'.get_class($this));
    }
    
    public function getUrl()
    {

    }
    
    
    public function getToken(Request $request) 
    {
        
    }
    
    public function getInfo()
    {
        if(is_object($this->session->info)) {
            return $this->session->info;
        } elseif(isset($this->session->token->access_token)) {
            $urlProfile = $this->options['info_uri'] . '?access_token='.$this->session->token->access_token;
            $retVal = file_get_contents($urlProfile);
            if(strlen(trim($retVal)) > 0) {
                $this->session->info = \Zend\Json\Decoder::decode($retVal);
                return $this->session->info;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function getScope()
    {
        
    }
    
    public function getState()
    {
        return $this->session->state;
    }
    
    protected function generateState()
    {
        $this->session->state = md5(microtime().'-'.get_class($this));
        return $this->session->state;
    }
    
    public function setOptions($options)
    {
        $this->options = $options;
    }
    
    public function getError()
    {
        return $this->error;
    }
    
    public function getSessionToken()
    {
        return $this->session->token;
    }
    
    public function getSessionContainer()
    {
        return $this->session;
    }
    
    public function getProvider()
    {
        return $this->providerName;
    }
    
}