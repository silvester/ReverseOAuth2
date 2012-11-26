<?php

namespace ReverseOAuth2;

use Zend\Http\PhpEnvironment\Request;
use Zend\Session\Container;
use ReverseOAuth2\ClientOptions;
use ReverseOAuth2\OAuht2HttpClient;

abstract class AbstractOAuth2Client
{
    
    /**
     * @var Zend\Session\Container
     */
    protected $session;
    
    /**
     * @var ReverseOAuth2\ClientOptions
     */
    protected $options;
    
    protected $error;
    
    /**
     * @var ReverseOAuth2\OAuth2HttpClient
     */
    protected $httpClient;

    public function __construct()
    {
        $this->session = new Container('ReverseOAuth2_'.get_class($this));
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
            $urlProfile = $this->options->getInfoUri() . '?access_token='.$this->session->token->access_token;
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
    
    public function setOptions(ClientOptions $options)
    {
        $this->options = $options;
    }
    
    public function getOptions()
    {
        return $this->options;
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
    
    public function setHttpClient($client)
    {
        if($client instanceof OAuth2HttpClient) {
            $this->httpClient = $client;
        } else {
            throw new Exception\HttpClientException('Passed HTTP client is not supported.');
        }
    }
    
    public function getHttpClient()
    {
        
        if(!$this->httpClient) {
            $this->httpClient = new OAuth2HttpClient(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'));
        }
        
        return $this->httpClient;
        
    }
    
}