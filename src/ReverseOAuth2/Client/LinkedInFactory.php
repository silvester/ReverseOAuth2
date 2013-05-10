<?php
namespace ReverseOAuth2\Client;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LinkedInFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $me = new \ReverseOAuth2\Client\LinkedIn;
        $cf = $serviceLocator->get('Config');
        $me->setOptions(new \ReverseOAuth2\ClientOptions($cf['reverseoauth2']['linkedin']));
        return $me;
    }
}