<?php

namespace ReverseOAuth2\Authentication\Adapter;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use ReverseOAuth2\AbstractOAuth2Client;

class ReverseOAuth2 implements AdapterInterface, EventManagerAwareInterface
{
    
    protected $client;
    protected $sharedEventManager;   
    
    public function setOAuth2Client($oauth2)
    {
        if($oauth2 instanceof AbstractOAuth2Client) {
            $this->client = $oauth2;
        }
    }
    
    public function authenticate()
    {
        
        if(is_object($this->client->getInfo())) { 
            
            $rs = $this->getEventManager()->prepareArgs((array)$this->client->getInfo());
            
            $this->getEventManager()->trigger(
                'oauth2.success', $this, array('provider' => $this->client->getProvider(), 'rs' => $rs)
            );
            
            return new Result(Result::SUCCESS, $rs);
            
        } else {
            
            return new Result(Result::FAILURE, $this->client->getError());
            
        }
        
    }    
    
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(__CLASS__);
        $this->events = $events;
        return $this;
    }
    
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }
    
}