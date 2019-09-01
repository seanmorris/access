<?php
namespace SeanMorris\Access\Route;
class AccessRoute extends \SeanMorris\PressKit\Controller
{
	const CONFIRM_TOKEN_SECRET = 'ACCESS ROUTE SECRET HERE';

	protected
		$formTheme    = 'SeanMorris\Form\Theme\Theme'
		, $modelClass = 'SeanMorris\Access\User'
		, $access     = [
			'register'          => TRUE
			, 'login'           => TRUE
			, 'logout'          => TRUE
			, 'current'         => TRUE
			, 'confirm'         => TRUE
			, 'view'            => TRUE
			, 'facebookConnect' => TRUE
			, 'facebookProfile' => TRUE
			, 'roles'           => TRUE

			, 'view'            => TRUE
			, 'edit'            => 'SeanMorris\Access\Role\Administrator'
			, 'create'          => 'SeanMorris\Access\Role\Administrator'
			, 'delete'          => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu'    => 'SeanMorris\Access\Role\Administrator'
			, 'index'           => TRUE
		]
	;
	public
		$title = 'Users'
		/*
		, $alias = [
			'index' => 'login'
		]
		*/
		, $routes = [
			'roles' => 'SeanMorris\Access\Route\RoleRoute'
		]
	;
	protected static
		$titleField = 'username'
		, $resourceClass = 'SeanMorris\PressKit\Api\Resource'
		, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $sessionStarted = FALSE
		, $userLoaded = FALSE
		, $forms = [
			'search'     => 'SeanMorris\PressKit\Form\UserSearchForm'
			, 'edit'     => 'SeanMorris\PressKit\Form\UserForm'
			, 'login'    => 'SeanMorris\Access\Form\LoginForm'
			, 'register' => 'SeanMorris\Access\Form\RegisterForm'
		]
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

	public function index($router)
	{
		if($user = static::_currentUser())
		{
			if($user->hasRole('SeanMorris\Access\Role\Administrator'))
			{
				return parent::index($router);
			}
		}

		throw new \SeanMorris\Ids\Http\Http303(
			$router->path()->append('login')->pathString()
		);
	}

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

		$userId = NULL;

		if($session['user'])
		{
			$userId = $session['user']->unconsume(FALSE, TRUE)['id'];
		}

		if(!static::$userLoaded && $userId)
		{
			if($user = $session['user']::loadOneById($userId))
			{
				$session['user']    = $user;
				static::$userLoaded = TRUE;
			}
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
		\SeanMorris\Ids\Meta::staticSession(1);
		$this->context['user'] = static::_currentUser();
		parent::_init($router);
	}

	public function register($router)
	{
		$this->context['breadcrumbsSuffix']['Register'] = '';

		$this->context['title'] = 'Register';
		$this->context['body'] = 'Register';

		$formClass = $this->_getForm('register');

		$form = new $formClass();

		$loggedIn = FALSE;
		$succcess = TRUE;
		$user     = NULL;

		$messages = \SeanMorris\Message\MessageHandler::get();

		if($_POST && $form->validate($_POST))
		{
			$user = $this->modelClass::loadOneByUsername($_POST['username']);

			if($_POST['password'] !== $_POST['confirmPassword'])
			{
				$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Password much match confirmation.'));
			}
			else
			{
				$user = new $this->modelClass;
				// $user = static::_currentUser();

				if($user->id)
				{
					$messages->addFlash(new \SeanMorris\Message\ErrorMessage('You\'re logged in already.'));
				}
				else
				{
					$user->consume($form->getValues(), TRUE);

					try
					{
						$user->save();

						if($user->id)
						{
							static::_currentUser($user);

							$userRole = '\SeanMorris\Access\Role\User';

							$role = new $userRole();
							$role->save();

							$user->addSubject('roles', $role, TRUE);
							$user->forceSave();

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
							$mail->send(TRUE);

							$redirect = $router->path()->pop()->append('current')->pathString();
							throw new \SeanMorris\Ids\Http\Http303($redirect);
						}
						else
						{
							$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Unknown error.'));
						}
					}
					catch(\Exception $e)
					{
						\SeanMorris\Ids\Log::logException($e);

						if($e instanceof \SeanMorris\Ids\Http\HttpException)
						{
							throw $e;
						}

						$succcess = false;
						
						if($e->getCode() == 1062)
						{
							if(preg_match('/.+\'(.+?)\'.*?$/', $e->getMessage(), $groups))
							{
								switch($groups[1])
								{
									case 'email':
										$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Email address already registered.'));
										break;
									case 'username':
										$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Username taken.'));
										break;
									default:
										$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Unknown error.'));
										break;
								}
							}

						}
						else
						{
							$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Unknown error.'));

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

			$succcess = false;
		}

		$formTheme = $this->formTheme;

		$get = $router->request()->get();

		if(isset($get['api']) && !$router->subRouted())
		{
			if($get['api'] == 'html')
			{
				print $form->render($formTheme);
			}
			else if($get['api'])
			{
				$parentController = $router->parent()->routes();

				$resourceClass = $parentController::$resourceClass
					?? static::$resourceClass;

				$resource = new $resourceClass(
					$router
					, []
					, $succcess ? 0 : 1
				);

				$resource->meta('form', $form->toStructure());
				$resource->body([]);

				if($user)
				{
					$resource->model($user);
				}

				return $resource;
			}
		}

		return $form->render($formTheme);
	}

	public function logout()
	{
		session_destroy();
		session_unset();

		$redirect = $redirectQuery = '';

		$messages = \SeanMorris\Message\MessageHandler::get();
		$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Logged out.'));

		if(isset($_GET['page']))
		{
			$redirect      = parse_url($_GET['page'], PHP_URL_PATH);
			$redirectQuery = parse_url($_GET['page'], PHP_URL_QUERY);
		}
		
		if($redirectQuery) {
			$redirect .= '?' . $redirectQuery;
		}

		throw new \SeanMorris\Ids\Http\Http303($redirect);
	}

	public function login($router)
	{
		$params = $router->request()->params();

		if(!$_POST
			&& isset($params['api'])
			&& $params['api'] !== 'html'
			&& !static::_currentUser()->id
		){
			// $resource = new static::$resourceClass($router);
			// print $resource->encode($params['api']);
			// die;
		}

		$this->context['_router'] = $router;
		$this->context['_controller'] = $this;

		$this->context['title'] = 'Login';
		$this->context['body'] = 'Login';

		// $form = new \SeanMorris\Access\Form\LoginForm();

		$formClass = $this->_getForm('login');

		$form = new $formClass();

		$loggedIn = false;
		$currentUri = $router->request()->uri();
		$statusCode = 200;
		$success    = true;

		if($_POST && $form->validate($_POST))
		{
			$user = $this->modelClass::loadOneByUsername(
				$_POST['username'] ?? NULL
			);
			$messages = \SeanMorris\Message\MessageHandler::get();

			if($user && $user->login($_POST['password'] ?? NULL))
			{
				static::_currentUser($user);

				$this->model = $user;

				$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Logged in.'));

				if(isset($params['api']))
				{
					$resource = new static::$resourceClass($router);
					echo $resource->encode($params['api'] ?? 'json');

					die;
				}
				else
				{
					$redirect = $currentUri;

					if(isset($_GET['page']))
					{
						$redirect = parse_url($_GET['page'], PHP_URL_PATH);
					}

					throw new \SeanMorris\Ids\Http\Http303($redirect);
				}
			}
			else if (!$user)
			{
				$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Bad username.'));
			}

			static::_resetCurrentUser($user);
				
			$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Bad username/password.'));

			$success    = false;

			$statusCode = 400;
		}

		$user = static::_currentUser();
		$userIdUrl = $router->path()->consumeNode();

		if(!$userIdUrl && $user->publicId)
		{
			throw new \SeanMorris\Ids\Http\Http303(
				$router->path()->append($user->publicId)->pathString()
			);
		}

		$get = $router->request()->get();

		if(isset($get['api']) && !$router->subRouted())
		{
			if($get['api'] == 'html')
			{
				print $form->render($formTheme);
			}
			else if($get['api'])
			{
				$parentController = $router->parent()->routes();

				$resourceClass = $parentController::$resourceClass
					?? static::$resourceClass;

				$resource = new $resourceClass($router);

				// $resource->meta('form', $form->toStructure());

				$resource->meta('form', $form->toStructure());
				$resource->body((object)[]);

				return $resource;
			}
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

		if(!$user = $this->modelClass::loadOneByPublicId($userId))
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

	public static function facebookLink($router, $redirect = NULL)
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if(!$facebook = static::facebook())
		{
			return FALSE;
		}

		$helper = $facebook->getRedirectLoginHelper();

		$permissions   = ['email'];
		$callbackQuery = ['page' => $redirect];
		$params        = $router->request()->get();

		if($params['api'])
		{
			$callbackQuery['api'] = ['api' => 'json'];
		}

		$callbackUrl = $router->request()->scheme()
			. $router->request()->host()
			. '/user/facebookConnect?'
			. http_build_query($callbackQuery)
		;

		return $helper->getLoginUrl($callbackUrl, $permissions);
	}

	public static function facebookToken()
	{
		$session = \SeanMorris\Ids\Meta::staticSession(1);

		return $session['facebookAccessToken'] ?? NULL;
	}

	public function facebookConnect($router)
	{
		// var_dump(static::facebookLink($router));die;

		$session     =& \SeanMorris\Ids\Meta::staticSession(1);
		if(!$facebook = static::facebook())
		{
			return FALSE;
		}

		$redirect = NULL;

		$params = $router->request()->get();

		if(isset($params['page']))
		{
			$redirect = parse_url($params['page']);

			$redirect = $redirect['path'] ?? NULL . (
				isset($redirect['query'])
					? '?' . $redirect['query']
					: NULL
			);

			$redirect = $params['page'];
		}

		try
		{
			$helper      = $facebook->getRedirectLoginHelper();
			$accessToken = $helper->getAccessToken();
		}
		catch(\Exception $e)
		{
			$messages = \SeanMorris\Message\MessageHandler::get();

			$messages->addFlash(new \SeanMorris\Message\ErrorMessage(
				'Facebook error.'
			));
		}

		if(!isset($accessToken))
		{
			if($error = $helper->getError())
			{
				\SeanMorris\Ids\Log::error('Facebook error', $error);
			}
			else
			{
				if($facebookLink = static::facebookLink($router, $redirect))
				{
					throw new \SeanMorris\Ids\Http\Http303($facebookLink);
				}
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

		$messages = \SeanMorris\Message\MessageHandler::get();
		
		$response = $facebook->get('/me?fields=id,name,email,birthday', $session['facebookAccessToken']);
		$facebookUser  = $response->getGraphUser();
		$facebookId    = $facebookUser->getId();
		$facebookName  = $facebookUser->getName();
		$facebookEmail = $facebookUser->getEmail();
		$birthday      = $facebookUser->getBirthday();

		$username      = $facebookName;

		if(!$user = $this->modelClass::loadOneByFacebookId($facebookId))
		{
			if(!$user = $this->modelClass::loadOneByEmail($facebookEmail))
			{
				while($existingUser = $this->modelClass::loadOneByUsername($username))
				{
					$existingData = $existingUser->unconsume(TRUE);

					if($existingData['facebookId'] == $facebookId)
					{
						break;
					}

					$username = $facebookName . '.' . rand();
				}

				$user = new $this->modelClass();
			}
		}

		if($user->id)
		{
			$user->consume(['facebookId' => $facebookId], TRUE);
		}
		else
		{
			$user->consume([
				'facebookId'  => $facebookId
				, 'username'  => $username
				, 'email'     => $facebookEmail
				, 'birthdate' => $birthday
					? $birthday->format('Y-m-d')
					: '1970-01-01'
			], TRUE);
		}

		$params = $router->request()->get();

		if($user->forceSave())
		{
			$messages->addFlash(
				new \SeanMorris\Message\SuccessMessage('Facebook linked.')
			);

			static::_currentUser($user);
		}
		else
		{
			$messages->addFlash(
				new \SeanMorris\Message\ErrorMessage('Facebook link failed.')
			);
		}

		throw new \SeanMorris\Ids\Http\Http303($redirect);
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

	public function current($router)
	{
		// return static::_currentUser();
		// throw new \SeanMorris\Ids\Http\Http303(
		// 	$router->path()->pop()->append('login')->pathString()
		// );
		$user = static::_currentUser();
		$params = $router->request()->params();
		
		if(isset($params['api']))
		{
			//\SeanMorris\Ids\Log::debug($resource);
			if($params['api'] == 'html')
			{
				echo $list;
			}
			else
			{
				$resource = new static::$resourceClass($router);
				$resource->model($user);

				return $resource;
			}
			// else if($params['api'] == 'xml')
			// {
			// 	header('Content-Type: application/xml');
			// 	echo $resource->toXml();
			// }
			// else
			// {
			// 	header('Content-Type: application/json');
			// 	echo $resource->toJson(2);
			// }
		}

		// return $user;
	}
}
