<?php

namespace ReverseOAuth2Test\Client;

use ReverseOAuth2Test\Bootstrap;
use PHPUnit_Framework_TestCase;

class GoogleClientTest extends PHPUnit_Framework_TestCase
{
    
    protected $providerName = 'Google';
    
    public function setup()
    {
         $this->client = $this->getClient();
    }
    
    public function tearDown()
    {

        unset($this->client->getSessionContainer()->token);
        unset($this->client->getSessionContainer()->state);
        unset($this->client->getSessionContainer()->info);
        
    }
    
    public function getClient()
    {
        $me = new \ReverseOAuth2\Client\Google;
        $cf = Bootstrap::getServiceManager()->get('Config');
        $me->setOptions(new \ReverseOAuth2\ClientOptions($cf['reverseoauth2']['google']));
        return $me;
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
        
        $this->client->getUrl();
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        
        $this->assertFalse($this->client->getToken($request));
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => 'some state')));      
        
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('variables do not match the session variables.', $error['internal-error']);
        
        if(!getenv('ZF2_PATH')) {
            
            $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
            $this->assertFalse($this->client->getToken($request));
            $error = $this->client->getError();
            $this->assertStringEndsWith('settings error.', $error['internal-error']);
            
        }
        
    }
    
    public function testFailGetTokenMocked()
    {
        
        $this->client->getUrl();
        
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
        $this->assertStringEndsWith('settings error.', $error['internal-error']);
        
    }
    
    public function testFailGetTokenMockedNonJson()
    {
    
        $this->client->getUrl();
    
        $httpClientMock = $this->getMock(
                '\ReverseOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getFaultyMockedNonJsonTokenResponse')));
    
        $this->client->setHttpClient($httpClientMock);
    
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
    
        $this->assertFalse($this->client->getToken($request));
    
        $error = $this->client->getError();
        $this->assertStringEndsWith('Unknown error.', $error['internal-error']);
    
    }
    
    public function testGetTokenMocked()
    {
        
        $this->client->getUrl();
        
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
        $this->client->getToken($request);
        
        $this->assertTrue($this->client->getToken($request)); // from session
        
        $this->assertTrue(strlen($this->client->getSessionToken()->access_token) > 0);
        
    }
    
    public function testGetInfo()
    {

        $httpClientMock = $this->getMock(
            '\ReverseOAuth2\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
        
        $rs = $this->client->getInfo();
        $this->assertSame('500', $rs->id);
        
        $rs = $this->client->getInfo(); // from session
        $this->assertSame('500', $rs->id);
        
    }
    
    public function testFailNoReturnGetInfo()
    {
    
        $httpClientMock = $this->getMock(
                '\ReverseOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponseEmpty')));

        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
    
        $this->client->setHttpClient($httpClientMock);
    
        $this->assertFalse($this->client->getInfo());
    
        $error = $this->client->getError();
        $this->assertSame('Get info return value is empty.', $error['internal-error']);
    
    }
    
    public function testFailNoTokenGetInfo()
    {
    
        $httpClientMock = $this->getMock(
                '\ReverseOAuth2\OAuth2HttpClient',
                array('send'),
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
    
        $this->client->setHttpClient($httpClientMock);
    
        $this->assertFalse($this->client->getInfo());
    
        $error = $this->client->getError();
        $this->assertSame('Session access token not found.', $error['internal-error']);
    
    }
    
    public function getMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('{
            "access_token": "ya29.AHES6ZQkpzzWwC6K3G6EHH-2s4DRVYCHSPwG",
            "token_type": "Bearer",
            "expires_in": 3600,
            "id_token": "eyJo_V3ftjOB4JnPlx7AXU8B6u5PKYNhkI6OSB0uEeE0x9aTjEm5q15Ukruxqrsk"
        }');

        return $response;

    }
    
    public function getFaultyMockedTokenResponse()
    {

        $response = new \Zend\Http\Response;

        $response->setContent('{"error": "some error"}');

        return $response;

    }
    
    public function getFaultyMockedNonJsonTokenResponse()
    {
    
        $response = new \Zend\Http\Response;
    
        $response->setContent('some=error+not+kul');
    
        return $response;
    
    }
    
    public function getMockedInfoResponse()
    {
    
        $content = '{
            "id": "500",
            "name": "John Doe",
            "first_name": "John",
            "last_name": "Doe",
            "link": "http:\/\/www.facebook.com\/john.doe",
            "username": "john.doe",
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
    
    public function getMockedInfoResponseEmpty()
    {
        
        $response = new \Zend\Http\Response;    
        return $response;
    
    }
    
}
