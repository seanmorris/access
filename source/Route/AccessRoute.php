<?php
namespace SeanMorris\Access\Route;
class AccessRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $modelClass = 'SeanMorris\Access\User'
		, $access = [
			'register' => TRUE
			, 'login' => TRUE
			, 'logout' => TRUE
			, 'view' => TRUE
		]
	;
	public
		$alias = [
			'index' => 'login'
		]
	;
	protected static
		$titleField = 'username'
		, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $menus = [
			'main' => [
				'Login' => [
					'_link'		=> 'login'
					, 'Login' => [
						'_link'		=> 'login'
					]
					, 'Register' => [
						'_link'		=> 'register'
					]
					, '_weight' => 101
				]
			]
		]
	;

	public function _init($router)
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if(!isset($session['user']))
		{
			$session['user'] = new \SeanMorris\Access\User;
		}

		parent::_init($router);
	}

	public function register($router)
	{
		$this->context['title'] = 'Register';
		$this->context['body'] = 'Register';

		$loginForm['_method'] = 'POST';

		$loginForm['username'] = [
			'_title' => 'username'
			, 'type' => 'text'
			, '_validators' => [
				'SeanMorris\Form\Validator\EmailValidator' => 
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
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

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
				$user = new \SeanMorris\Access\User;
				$user->consume($form->getValues());

				if($user->save())
				{
					$session =& \SeanMorris\Ids\Meta::staticSession(1);
					$session['user'] = $user;
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
		
		return $form->render($formTheme) . (
			'<pre>'
			. print_r($_SESSION, 1)
			. '</pre>'
		);
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
		$this->context['title'] = 'Login';
		$this->context['body'] = 'Login';

		$loginForm['_method'] = 'POST';

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
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		$loggedIn = false;

		if($_POST && $form->validate($_POST))
		{
			$user = \SeanMorris\Access\User::loadOneByUsername($_POST['username']);
			$messages = \SeanMorris\Message\MessageHandler::get();
			
			if($user)
			{
				if($user->login($_POST['password']))
				{
					$loggedIn = true;
					$session['user'] = $user;
				}
			}
			else
			{
				return 'User not found';
			}

			$redirect = $router->request()->uri();

			if($loggedIn)
			{
				if(isset($_GET['page']))
				{
					$redirect = parse_url($_GET['page'], PHP_URL_PATH);
				}

				$messages->addFlash(new \SeanMorris\Message\SuccessMessage('Logged in.'));
			}
			else
			{
				unset($session['user']);
				$messages->addFlash(new \SeanMorris\Message\ErrorMessage('Bad username/password.'));
			}



			throw new \SeanMorris\Ids\Http\Http303($redirect);
		}

		$formTheme = $this->formTheme;
		
		return $form->render($formTheme) . (
			'<pre>'
			. print_r($_SESSION, 1)
			. '</pre>'
		);
	}

	public function _menu(\SeanMorris\Ids\Router $router, $path, \SeanMorris\Ids\Routable $routable = NULL)
	{
		$session =& \SeanMorris\Ids\Meta::staticSession(1);

		if(isset($session['user']) && $session['user']->id)
		{
			static::$menus['main'] = [
				'username' => [
					'_weight' => 101
					, '_link'	=> $session['user']->publicId
					, '_title'	=> $session['user']->username
					, 'Profile' => [
						'_link'		=> $session['user']->publicId
					]
					, 'Logout' => [
						'_link'		=> 'logout?page=' . $router->request()->uri()
					]

				]
			];
		}
		else
		{
			static::$menus['main']['Login']['_link']
				.= '?page=' . $router->request()->uri();

			static::$menus['main']['Login']['Login']['_link']
				.= '?page=' . $router->request()->uri();
		}

		return parent::_menu($router, $path);
	}
}