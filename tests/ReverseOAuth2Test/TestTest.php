<?php

namespace ReverseOAuth2Test;

use PHPUnit_Framework_TestCase;

class TestTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        
    }
    
    public function testSetEventManagerWorks()
    {
        
        $me = new \ReverseOAuth2\ClientOptions(array('some'));
        
        $mocked = $this->getMock('\ReverseOAuth2\ClientOptions');

        print_r($mocked);
        
        $this->assertSame('me', 'me');
        
    }
    
}
