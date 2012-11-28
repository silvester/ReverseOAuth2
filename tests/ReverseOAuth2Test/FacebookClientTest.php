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
        
    }
    
    public function testInstanceTypes()
    {
        $this->assertInstanceOf('ReverseOAuth2\AbstractOAuth2Client', $this->client);
        $this->assertInstanceOf('ReverseOAuth2\Client\\'.$this->providerName, $this->client);
        $this->assertInstanceOf('ReverseOAuth2\ClientOptions', $this->client->getOptions());
        $this->assertInstanceOf('Zend\Session\Container', $this->client->getSessionContainer());
        $this->assertInstanceOf('ReverseOAuth2\OAuth2HttpClient', $this->client->getHttpClient());
    }
    
    public function testGetProviderName()
    {
        $this->assertSame(strtolower($this->providerName), $this->client->getProvider());
    }
    
    public function testSetHttpClient()
    {
        $httpClientMock = $this->getMock(
                '\ReverseOAuth2\OAuth2HttpClient',
                null,
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $this->client->setHttpClient($httpClientMock);
    }
    
    public function testFailSetHttpClient()
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
            $this->client->getOptions()->setScope(array('some', 'scope'));
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        }
        
    }
    
    public function testFailGetToken()
    {
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        
        $this->assertFalse($this->client->getToken($request));
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => 'some state')));      
        
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('variables do not match the session variables.', $error['internal-error']);
        
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('settings error.', $error['internal-error']);
        
    }
    
    public function testFailGetTokenMocked()
    {
        
        $this->client->clearError();
        $httpClientMock = $this->getMock(
            '\ReverseOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getFaultyMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertFalse($this->client->getToken($request));
                
        $error = $this->client->getError();
        $this->assertStringEndsWith('Unknown error.', $error['internal-error']);
        
        //print_r($this->client->getError());
        //print_r($this->client->getSessionToken());
        //print_r($this->client->getInfo());
        //print_r($this->client->getHttpClient());
        
    }
    
    public function testGetTokenMocked()
    {
        
        $this->client->clearError();
        
        $httpClientMock = $this->getMock(
            '\ReverseOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertTrue($this->client->getToken($request));
        
        $this->assertTrue($this->client->getToken($request)); // from session
        
        $this->assertTrue(strlen($this->client->getSessionToken()->access_token) > 0);
        
    }
    
    public function testGetInfo()
    {
        
        $this->client->clearError();
        
        $httpClientMock = $this->getMock(
                '\ReverseOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedUserInfoResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $rs = $this->client->getInfo();
        $this->assertSame('560366914', $rs->id);
        
        $rs = $this->client->getInfo(); // from session
        $this->assertSame('560366914', $rs->id);
        
        //print_r($this->client->getHttpClient());
        
    }
    
    public function getMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('access_token=AAAEDkf9KDoQBABLbTeTDEe9kvfZCvwFb4rOT2KwO7EZAUWGwdZCBBLuCOgWLQpyMUxZBQjkrCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZD&expires=5117581');

        return $response;

    }
    
    public function getFaultyMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('token=AAAEDkf9KDoQBABLbTeTDEe9kvfZCvwFb4rOT2KwO7EZAUWGwdZCBBLuCOgWLQpyMUxZBQjkrCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZg&expires=1');

        return $response;

    }
    
    public function getMockedUserInfoResponse()
    {
        
        $content = '{"id": "560366914",
        "name": "Silvester Mara\u017e",
        "first_name": "Silvester",
        "last_name": "Mara\u017e",
        "link": "http:\/\/www.facebook.com\/silvester.maraz",
        "username": "silvester.maraz",
        "gender": "male",
        "timezone": 1,
        "locale": "sl_SI",
        "verified": true,
        "updated_time": "2012-09-14T12:37:27+0000"
        }';
        
        $response = new \Zend\Http\Response;
        
        $response->setContent($content);
        
        return $response;
        
    }
    
    public function getFaultyMockedUserInfoResponse()
    {
    
        $content = '';
    
        $response = new \Zend\Http\Response;
    
        $response->setContent($content);
    
        return $response;
    
    }
    
}
