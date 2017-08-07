<?php
namespace SeanMorris\Access\Route;
class AccessRoute extends \SeanMorris\PressKit\Controller
{
	const CONFIRM_TOKEN_SECRET = 'ACCESS ROUTE SECRET HERE';

	protected
		$formTheme = 'SeanMorris\Form\Theme\Theme'
		, $modelClass = 'SeanMorris\Access\User'
		, $access = [
			'register' => TRUE
			, 'login' => TRUE
			, 'logout' => TRUE
			, 'confirm' => TRUE
			, 'view' => TRUE
			, 'facebookConnect' => TRUE
			, 'facebookProfile' => TRUE

			, 'view' => TRUE
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'create' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
			, 'index' => TRUE
		]
	;
	public
		$alias = [
			'index' => 'login'
		]
		, $title = 'Users'
	;
	protected static
		$titleField = 'username'
		, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $sessionStarted = FALSE
		, $userLoaded = FALSE
		, $menus = [
			'main' => [
				'Login' => [
					'_link'		=> 'login'
					, '_weight' => 100
					, '_attrs' => ['data-no-instant' => 'data-no-instant']
				]	
				, 'Register' => [
					'_link'		=> 'register'
					, '_weight' => 101
					, '_attrs' => ['data-no-instant' => 'data-no-instant']
				]
			]
		]
	;

	public static function _currentUser(\SeanMorris\Access\User $user = NULL)
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if($user)
		{
			$session['user'] = $user;
		}

		if(!isset($session['user']))
		{
			$session['user'] = new \SeanMorris\Access\User;
		}

		if(!static::$userLoaded)
		{
			if(isset($session['user']->id))
			{
				$session['user'] = \SeanMorris\Access\User::loadOneById($session['user']->id);
			}

			static::$userLoaded = TRUE;
		}

		return $session['user'];
	}

	public static function _resetCurrentUser()
	{
		$user = NULL;
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if(isset($session['user']))
		{
			$user = $session['user'];
		}

		unset($session['user']);

		return static::_currentUser();
	}

	public function _init($router)
	{
		if(session_status() === PHP_SESSION_NONE)
		{
			session_start();
		}
		$this->context['user'] = static::_currentUser();
		parent::_init($router);
	}

	public function register($router)
	{
		$this->context['breadcrumbsSuffix']['Register'] = '';

		$this->context['title'] = 'Register';
		$this->context['body'] = 'Register';

		$loginForm['_method'] = 'POST';

		
		if($facebookLoginUrl = static::facebookLink($router))
		{
			$loginForm['facebook'] = [
				'type' => 'html'
				//, 'value' => '<br /><br /><div class="fb-login-button" data-max-rows="1" data-size="medium" data-show-faces="false" data-auto-logout-link="false"></div>'
				, 'value' => sprintf(
					'<a href = "%s" class = "fbLogin">
						<img src = "/SeanMorris/TheWhtRbt/images/facebook_login.png" style = "width:100%%;">
					</a><br />

					- OR -

					<br />'
					, $facebookLoginUrl
				)
			];
		}

		$loginForm['username'] = [
			'_title' => 'username'
			, 'type' => 'text'
		];

		$loginForm['email'] = [
			'_title' => 'email address'
			, 'type' => 'text'
			, '_validators' => [
				'SeanMorris\Form\Validator\Email' =>
					'%s must be a valid email.'
			]
		];

		$loginForm['password'] = [
			'_title' => 'password'
			, 'type' => 'password'
		];

		$loginForm['confirmPassword'] = [
			'_title' => 'confirm password'
			, 'type' => 'password'
		];

		$loginForm['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		$form = new \SeanMorris\Form\Form($loginForm);

		$loggedIn = false;

		$messages = \SeanMorris\Message\MessageHandler::get();

		if($_POST && $form->validate($_POST))
		{
			$user = \SeanMorris\Access\User::loadOneByUsername($_POST['username']);

			if($_POST['password'] !== $_POST['confirmPassword'])
			{
				$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Password much match confirmation.'));
			}
			else
			{
				//$user = new \SeanMorris\Access\User;
				$user = static::_currentUser();

				if($user->id)
				{
					$messages->addFlash(new \SeanMorris\Message\ErrorMessage('User already exists.'));
				}
				else
				{
					$user->consume($form->getValues());

					try
					{
						if($user->save())
						{
							static::_currentUser($user);
							$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Registration successful.'));

							$token = \SeanMorris\Ids\HashToken::getToken(
								$user->username
								, static::CONFIRM_TOKEN_SECRET
								, 60*60*24*7
								, 15
							);

							$confirmUrl = sprintf(
								'//%s/user/confirm/%s/%s'
								, $router->request()->host()
								, $user->publicId
								, $token
							);

							$mail = new \SeanMorris\Ids\Mail();
							$mail->to($user->email);
							$mail->subject('Confirm your email.');
							$mail->body($confirmUrl);
							$mail->send();

						}
					}
					catch(\Exception $e)
					{
						if($e->getCode() == 1062)
						{
							$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Username taken.'));
						}
						else
						{
							$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Unknown error.'));

							\SeanMorris\Ids\Log::logException($e);
							die;
						}
					}
				}
			}
		}
		else if($errors = $form->errors())
		{
			foreach($errors as $error)
			{
				$messages->addFlash(new \SeanMorris\Message\ErrorMessage($error));
			}
		}

		$formTheme = $this->formTheme;

		return $form->render($formTheme);
	}

	public function logout()
	{
		session_destroy();

		$redirect = '';

		$messages = \SeanMorris\Message\MessageHandler::get();
		$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Logged out.'));

		if(isset($_GET['page']))
		{
			$redirect = parse_url($_GET['page'], PHP_URL_PATH);
		}

		throw new \SeanMorris\Ids\Http\Http303($redirect);
	}

	public function login($router)
	{
		$this->context['_router'] = $router;
		$this->context['_controller'] = $this;

		$this->context['title'] = 'Login';
		$this->context['body'] = 'Login';

		$loginForm['_method'] = 'POST';

		if($facebookLoginUrl = static::facebookLink($router))
		{
			$loginForm['facebook'] = [
				'type' => 'html'
				//, 'value' => '<br /><br /><div class="fb-login-button" data-max-rows="1" data-size="medium" data-show-faces="false" data-auto-logout-link="false"></div>'
				, 'value' => sprintf(
					'<a href = "%s" class = "fbLogin">
						<img src = "/SeanMorris/TheWhtRbt/images/facebook_login.png" style = "width:100%%;">
					</a><br />

					- OR -

					<br />'
					, $facebookLoginUrl
				)
			];
		}

		$loginForm['username'] = [
			'_title' => 'username'
			, 'type' => 'text'
		];

		$loginForm['password'] = [
			'_title' => 'password'
			, 'type' => 'password'
		];

		$loginForm['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		$form = new \SeanMorris\Form\Form($loginForm);
		$loggedIn = false;
		$currentUri = $router->request()->uri();
		$statusCode = 200;

		if($_POST && $form->validate($_POST))
		{
			$user = \SeanMorris\Access\User::loadOneByUsername($_POST['username']);
			$messages = \SeanMorris\Message\MessageHandler::get();

			if($user)
			{
				if($user->login($_POST['password']))
				{
					static::_currentUser($user);

					$redirect = $currentUri;

					if(isset($_GET['page']))
					{
						$redirect = parse_url($_GET['page'], PHP_URL_PATH);
					}

					$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Logged in.'));

					throw new \SeanMorris\Ids\Http\Http303($redirect);
				}
			}

			static::_resetCurrentUser($user);
				
			$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Bad username/password.'));

			$statusCode = 400;
		}

		$user = static::_currentUser();

		if($user->publicId)
		{
			throw new \SeanMorris\Ids\Http\Http303($currentUri . '/' . $user->publicId);
		}

		$formTheme = $this->formTheme;
		
		return new \SeanMorris\Ids\Http\HttpResponse($form->render($formTheme), $statusCode);
	}

	public function confirm($router)
	{
		$path = $router->path();

		if(!$userId = $path->consumeNode())
		{
			\SeanMorris\Ids\Log::debug('No user id found.');
			return FALSE;
		}

		if(!$user = \SeanMorris\Access\User::loadOneByPublicId($userId))
		{
			\SeanMorris\Ids\Log::debug('No user found.');
			return FALSE;
		}

		$token = implode(
			'/'
			, $tokenParts = array_filter([
				$path->consumeNode()
				, $path->consumeNode()
				, $path->consumeNode()
				, $path->consumeNode()
			])
		);

		if(count($tokenParts) !== 4)
		{
			\SeanMorris\Ids\Log::debug('No token found.');
			return false;
		}

		if(\SeanMorris\Ids\HashToken::checkToken($token, $user->username, static::CONFIRM_TOKEN_SECRET))
		{
			\SeanMorris\Ids\Log::debug('Token VALID.');

			$verifiedRole = '\SeanMorris\Access\Role\User';

			if(0 && $user->hasRole($verifiedRole))
			{
				return FALSE;
			}

			$role = new $verifiedRole();
			$role->save();

			$user->addSubject('roles', $role);
			$user->save();

			if($user->hasRole($verifiedRole))
			{
				$messages = \SeanMorris\Message\MessageHandler::get();

				$messages->addFlash(
					new \SeanMorris\Message\SuccessMessage('Email verified.')
				);

				throw new \SeanMorris\Ids\Http\Http303('');
			}
		}
		else
		{
			\SeanMorris\Ids\Log::debug('Token NOT VALID.');

			return FALSE;
		}

		return 'LOL!';
	}

	protected static function facebook()
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		$facebookAppSettings = \SeanMorris\Ids\Settings::read('facebookApp');

		if(!isset(
			$facebookAppSettings
			, $facebookAppSettings->id
			, $facebookAppSettings->secret
			, $facebookAppSettings->apiVersion
		)){
			return;
		}

		return new \Facebook\Facebook([
			'app_id'                => $facebookAppSettings->id,
			'app_secret'            => $facebookAppSettings->secret,
			'default_graph_version' => $facebookAppSettings->apiVersion,
		]);
	}

	protected static function facebookLink($router)
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if(!$facebook = static::facebook())
		{
			return FALSE;
		}

		$helper = $facebook->getRedirectLoginHelper();

		$permissions = ['email'];

		$callbackUrl = $router->request()->scheme()
			. $router->request()->host()
			. '/user/facebookConnect'
		;

		return $helper->getLoginUrl($callbackUrl, $permissions);
	}

	public function facebookConnect()
	{
		$session     =& \SeanMorris\Ids\Meta::staticSession(1);
		if(!$facebook = static::facebook())
		{
			return FALSE;
		}
		$helper      = $facebook->getRedirectLoginHelper();
		$accessToken = $helper->getAccessToken();

		if(!isset($accessToken))
		{
			if($error = $helper->getError())
			{
				\SeanMorris\Ids\Log::error('Facebook error', $error);
			}
			return FALSE;
		}

		$oAuth2Client = $facebook->getOAuth2Client();
		$tokenMetadata = $oAuth2Client->debugToken($accessToken->getValue());

		$facebookAppSettings = \SeanMorris\Ids\Settings::read('facebookApp');

		$tokenMetadata->validateAppId($facebookAppSettings->id);

		$tokenMetadata->validateExpiration();

		if (!$accessToken->isLongLived())
		{
			$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		}

		$session['facebookAccessToken'] = (string) $accessToken;

		throw new \SeanMorris\Ids\Http\Http303('user/facebookProfile');
	}

	public function facebookProfile()
	{
		$messages = \SeanMorris\Message\MessageHandler::get();
		$session  =& \SeanMorris\Ids\Meta::staticSession(1);
		$facebook = static::facebook();
		$response = $facebook->get('/me?fields=id,name,email', $session['facebookAccessToken']);
		$facebookUser = $response->getGraphUser();
		$facebookId = $facebookUser->getId();

		if(!$user = \SeanMorris\Access\User::loadOneByFacebookId($facebookId))
		{
			$user = new \SeanMorris\Access\User();
		}

		if($user->id)
		{
			$user->consume(['facebookId' => $facebookId], TRUE);
		}
		else
		{
			$user->consume([
				'facebookId'   => $facebookId
				, 'username'   => $facebookUser->getName()
				, 'email'      => $facebookUser->getEmail()
				, 'password'   => sha1(rand(255,65355))
			], TRUE);
		}

		if($user->forceSave())
		{
			$messages->addFlash(
				new \SeanMorris\Message\SuccessMessage('Facebook linked.')
			);

			static::_currentUser($user);

			throw new \SeanMorris\Ids\Http\Http303('index');
		}
		else
		{
			$messages->addFlash(
				new \SeanMorris\Message\ErrorMessage('Facebook link failed.')
			);
		}

		throw new \SeanMorris\Ids\Http\Http303('user');
	}

	public function _menu(\SeanMorris\Ids\Router $router, $path, \SeanMorris\Ids\Routable $routable = NULL)
	{
		$user = static::_currentUser();

		if($user->id)
		{
			static::$menus['main'] = [
				'username' => [
					'_weight' => 101
					, '_link'	=> $user->publicId
					, '_title'	=> $user->username
					, 'Profile' => [
						'_link' => $user->publicId
					]
				]
				, 'Logout' => [
					'_weight' => 102
					, '_link'    => 'logout?page=' . $router->request()->uri()
					, '_attrs' => ['data-no-instant' => 'data-no-instant']
				]
			];
		}
		else
		{
			static::$menus['main']['Login']['_link']
				.= '?page=' . $router->request()->uri();
		}

		return parent::_menu($router, $path);
	}

	public static function _loginLink($router)
	{
		while($router->subRouted())
		{
			$router = $router->parent();
		}

		return $router->root()->routes()->_pathTo(get_called_class())
			. '/login?page=' . $router->request()->uri();
	}

	public static function _logoutLink($router)
	{
		while($router->subRouted())
		{
			$router = $router->parent();
		}

		return $router->root()->routes()->_pathTo(get_called_class())
			. '/logout?page=' . $router->request()->uri();
	}

	public static function _registerLink($router)
	{
		while($router->subRouted())
		{
			$router = $router->parent();
		}

		return $router->root()->routes()->_pathTo(get_called_class())
			. '/register?page=' . $router->request()->uri();
	}
}
