ReverseOAuth2
===========

[![Build Status](https://secure.travis-ci.org/silvester/ReverseOAuth2.png?branch=master)](https://travis-ci.org/silvester/ReverseOAuth2)

Another OAuth2 client for ZF2. It provides clients for github, google, facebook and linkedin others soon to come. 

The library is kept as simple as possible, it does not provide routes or controllers.

Demo
----
Minimum rights are used. If you feel intimidated revoke the rights. Click the login button. 

Github: http://reverseform.modo.si/oauth-github

Google: http://reverseform.modo.si/oauth-google

Facebook: http://reverseform.modo.si/oauth-facebook

Installation with Composer
--------------------------
1. Add this project in your `composer.json`:
```json
    "require": {
        "silvester/reverse-oauth2": "dev-master",
    }
```

2. Fetch the repository with composer:
```bash
$ php composer.phar update
```

3. Enable it in your `config/application.config.php` file:
```php
return array(
	'modules' => array(
		// ...
		'ReverseOAuth2',
	),
	// ...
);
```

Usage
-----

As usual add it to your application.config.php 'ReverseOAuth2'.

Copy & rename the `config/reverseoauth2.local.php.dist` to your autoload folder and fill the information needed. 

### In your controller/action do:
```php
public function callbackAction()
{

    $me = $this->getServiceLocator()->get('ReverseOAuth2\Google');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\Github');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\Facebook');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\LinkedIn');

    if (strlen($this->params()->fromQuery('code')) > 10) {
    	
    	if($me->getToken($this->request)) {
    		$token = $me->getSessionToken(); // token in session
    	} else {
    		$token = $me->getError(); // last returned error (array)
    	}
        
        $info = $me->getInfo();
        
    } else {
    
        $url = $me->getUrl();
        
    }

    return array('token' => $token, 'info' => $info, 'url' => $url);

}
```

The action name depends on your settings. getUrl() will return the url where you should redirect the user, there is no automatic redirection do it yourself.

### Client Configuration

Beside the configuration options in `module.config.php` and `reverseoath2.local.php` you can change the client configuration on runtime.

```php
public function callbackAction()
{

    $me = $this->getServiceLocator()->get('ReverseOAuth2\Google');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\Github');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\Facebook');
    //$me = $this->getServiceLocator()->get('ReverseOAuth2\LinkedIn');

	$me->getOptions()->setScope(array('email', 'user'));
	$me->getOptions()->setAuthUri('http://google.com/');
	$me->getOptions()->setTokenUri('http://google.com/');
	$me->getOptions()->setInfoUri('http://google.com/');
	$me->getOptions()->setClientId('my-id.com');
	$me->getOptions()->setClientSecret('my-secret');
	$me->getOptions()->setRedirectUri('http://my-server.com/');

}
```

### The ReverseOAuth2 authentication adapter

The module provides also an zend\authentication\adapter.

```php
public function authGithubAction() // controller action
{

    $me = $this->getServiceLocator()->get('ReverseOAuth2\Github');

    $auth = new AuthenticationService(); // zend
    
    if (strlen($this->params()->fromQuery('code')) > 10) {
         
        if($me->getToken($this->request)) { // if getToken is true, the user has authenticated successfully by the provider, not yet by us.
            $token = $me->getSessionToken(); // token in session
        } else {
            $token = $me->getError(); // last returned error (array)
        }
        
        $adapter = $this->getServiceLocator()->get('ReverseOAuth2\Auth\Adapter'); // added in module.config.php
        $adapter->setOAuth2Client($me); // $me is the oauth2 client
        $rs = $auth->authenticate($adapter); // provides an eventManager 'oauth2.success'
        
        if (!$rs->isValid()) {
            foreach ($rs->getMessages() as $message) {
                echo "$message\n";
            }
            echo 'no valid';
        } else {
            echo 'valid';
        }

    } else {
        $url = $me->getUrl();
    }

    $view = new ViewModel(array('token' => $token, 'info' => $info, 'url' => $url, 'error' => $me->getError()));
    
    return $view;

}
```

The adapter also provides an event called `oauth2.success`. Here you can check the data from the client against your user registry. You will be provided with
information from the user, token info and provider type.

In your module class you could do:

```php
public function onBootstrap(Event $e)
{
    /* Some bad code here, only for demo purposes. */
    $userTable = new UserTable($e->getApplication()->getServiceManager()->get('Zend\Db\Adapter\Adapter')); // my user table
    $e->getApplication()->getServiceManager()->get('ReverseOAuth2\Auth\Adapter')->getEventManager() // the the adapters eventmanager
        ->attach('oauth2.success', //attach to the event
            function($e) use ($userTable){
                
                $params = $e->getParams(); //print_r($params); so you see whats in if
                
                if($user = $userTable->getUserByRemote($params['provider'], $params['info']['id'])) { // check for user from facebook with id 1000
    
                    $user->token = $params['token']['access_token'];
                    $expire = (isset($params['token']['expires'])) ? $params['token']['expires'] : 3600;
                    $user->token_valid = new \Zend\Db\Sql\Expression('DATE_ADD(NOW(), INTERVAL '.$expire.' SECOND)');
                    $user->date_update = new \Zend\Db\Sql\Expression('NOW()');
                    
                    $userTable->saveUser($user);
                                    
                } else {
                    
                    $user = new User;
                    $user->token = $params['token']['access_token'];
                    $expire = (isset($params['token']['expires'])) ? $params['token']['expires'] : 3600;
                    $user->token_valid = new \Zend\Db\Sql\Expression('DATE_ADD(NOW(), INTERVAL '.$expire.' SECOND)');
                    $user->date_update = new \Zend\Db\Sql\Expression('NOW()');
                    $user->date_create = new \Zend\Db\Sql\Expression('NOW()');
                    $user->remote_source = $params['provider'];
                    $user->remote_id = $params['info']['id'];
                    $user->name = $params['info']['name'];
                    $user->info = \Zend\Json\Encoder::encode($params['info']);
                    
                    $userTable->saveUser($user);
                    
                }
                
                $user = $userTable->getUserByRemote($params['provider'], $params['info']['id']);
                $params['info'] = $user->getArrayCopy();
                $params['info']['info'] = false;
    
    			// here the params info is rewitten. The result object returned from the auth object will have the db row.
    			
    			$params['code'] = \Zend\Authentication\Result::FAILURE; // this would deny authentication. default is \Zend\Authentication\Result::SUCCESS.
    
            });

}
```

TODO
----
* Add other clients
* Write some decent documentation.
* Demo module is on it's way.
