<?php

namespace ReverseOAuth2;

use Zend\Http\PhpEnvironment\Request;

abstract class AbstractOAuth2Client
{

    /**
     * Holds session data
     * 
     * @var \Zend\Session\Container 
     */
    protected $session;

    /**
     * Holds credentials for connecting to provider
     * 
     * @var array
     */
    protected $options;

    /**
     * Holds error options
     * 
     * @var array 
     */
    protected $error;

    /**
     * Holds provider name
     * 
     * @var string
     */
    protected $providerName;

    public function __construct()
    {
        $this->session = new \Zend\Session\Container('ReverseOAuth2_' . get_class($this));
    }

    /**
     * @return string Will return URL connecting provider and making requests
     */
    public abstract function getUrl();

    /**
     * @return bool Will get token from provider
     */
    public abstract function getToken(Request $request);

    /**
     * Returns information gathered from provider
     * 
     * @return bool
     */
    public function getInfo()
    {
        if (is_object($this->session->info)) {
            return $this->session->info;
        } elseif (isset($this->session->token->access_token)) {
            $urlProfile = $this->options['info_uri'] . '?access_token=' . $this->session->token->access_token;
            $retVal = file_get_contents($urlProfile);
            if (strlen(trim($retVal)) > 0) {
                $this->session->info = $retVal; //\Zend\Json\Decoder::decode($retVal);
                return $this->session->info;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Manages scope of the application
     * 
     * @return string
     */
    public function getScope()
    {
        if (count($this->options['scope']) > 0) {
            $str = urlencode(implode(',', $this->options['scope']));
            return '&scope=' . $str;
        } else {
            return '';
        }
    }

    /**
     * Returns state from session, if not set generates one
     * 
     * @return string
     */
    public function getState()
    {
        return isset($this->session->state) ? $this->session->state : $this->generateState();
    }

    /**
     * Creates and Returns state of session to be used
     * 
     * @return string
     */
    protected function generateState()
    {
        $this->session->state = md5(microtime() . '-' . get_class($this));
        return $this->session->state;
    }

    /**
     * Sets options
     * 
     * @param array $options 
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Returns error taken from request, made upon to provider
     * 
     * @return array 
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns token from session
     * 
     * @return string
     */
    public function getSessionToken()
    {
        return $this->session->token;
    }

    /**
     * Returns current session container
     * 
     * @return \Zend\Session\Container 
     */
    public function getSessionContainer()
    {
        return $this->session;
    }

    /**
     * Returns current provider's name
     * 
     * @return string
     */
    public function getProvider()
    {
        return $this->providerName;
    }

}