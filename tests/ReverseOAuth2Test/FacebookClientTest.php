<?php

use ReverseOAuth2Test\Bootstrap;

namespace ReverseOAuth2Test;

use PHPUnit_Framework_TestCase;

class FacebookClientTest extends PHPUnit_Framework_TestCase
{
    
    protected $providerName = 'Facebook';
    
    public function setup()
    {
        
        $this->client = Bootstrap::getServiceManager()->get('ReverseOAuth2\\'.$this->providerName);
        
        $httpClientMock = $this->getMock(
            '\ReverseOAuth2\OAuth2HttpClient', 
            null, 
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl')), 
            'OAuth2HttpClient'
        );
        
        //$this->client->setHttpClient($httpClientMock);       
        
    }
    
    public function testInstanceTypes()
    {
        $this->assertInstanceOf('ReverseOAuth2\AbstractOAuth2Client', $this->client);
        $this->assertInstanceOf('ReverseOAuth2\Client\\'.$this->providerName, $this->client);
        $this->assertInstanceOf('ReverseOAuth2\ClientOptions', $this->client->getOptions());
        $this->assertInstanceOf('Zend\Session\Container', $this->client->getSessionContainer());
        $this->assertInstanceOf('ReverseOAuth2\OAuth2HttpClient', $this->client->getHttpClient());
    }
    
    public function testFailSetHttpAdapter()
    {
        $this->setExpectedException('ReverseOAuth2\Exception\HttpClientException');
        $this->client->setHttpClient(new \Zend\Http\Client);
    }
    
    public function testSessionState()
    {
        
        $this->assertEmpty($this->client->getState());
        $this->client->getUrl();
        $this->assertEquals(strlen($this->client->getState()), 32);
        
    }
    
    public function testLoginUrlCreation()
    {
        
        $uri = \Zend\Uri\UriFactory::factory($this->client->getUrl());
        $this->assertTrue($uri->isValid());
        
    }
    
    public function testGetScope()
    {
        
        if(count($this->client->getOptions()->getScope()) > 0) {
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        } else {
            $this->assertTrue(strlen($this->client->getScope()) == 0);
        }
        
    }
    
    public function testFailGetToken()
    {
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        
        $this->assertFalse($this->client->getToken($request));
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => 'some state')));      
        
        $this->assertFalse($this->client->getToken($request));
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertFalse($this->client->getToken($request));
        
    }
    
}
