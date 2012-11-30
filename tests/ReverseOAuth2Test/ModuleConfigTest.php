<?php

use ReverseOAuth2Test\Bootstrap;

namespace ReverseOAuth2Test;

use PHPUnit_Framework_TestCase;

class ModuleConfigTest extends PHPUnit_Framework_TestCase
{
    
    public function testModuleConfig()
    {
        
        $githubClient = Bootstrap::getServiceManager()->get('ReverseOAuth2\Github');   
        $facebookClient = Bootstrap::getServiceManager()->get('ReverseOAuth2\Facebook');   
        $googleClient = Bootstrap::getServiceManager()->get('ReverseOAuth2\Google');   
        
        $this->assertSame('github', $githubClient->getProvider());
        $this->assertSame('facebook', $facebookClient->getProvider());
        $this->assertSame('google', $googleClient->getProvider());

    }
    
}
