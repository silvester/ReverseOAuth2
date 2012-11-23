<?php

use ReverseOAuth2Test\Bootstrap;

namespace ReverseOAuth2Test;

use PHPUnit_Framework_TestCase;

class GithubClientTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->client = Bootstrap::getServiceManager()->get('ReverseOAuth2\Google');
    }
    
    public function testInstanceTypes()
    {
        
        $this->assertInstanceOf('ReverseOAuth2\AbstractOAuth2Client', $this->client);
        $this->assertInstanceOf('ReverseOAuth2\Client\Google', $this->client);
        $this->assertInstanceOf('ReverseOAuth2\ClientOptions', $this->client->getOptions());
        $this->assertInstanceOf('Zend\Session\Container', $this->client->getSessionContainer());
 
    }
    
    public function testSessionState()
    {
        
        $this->assertEmpty($this->client->getState());
        
        $url = $this->client->getUrl();
        //$urlValidator = new \Zend\Validator\Hostname;
        //$urlValidator->isValid($url);
        //$this->assertTrue($urlValidator->isValid($url));
        
        $this->assertEquals(strlen($this->client->getState()), 32);
        
    }
    
}
