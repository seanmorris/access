<?php
namespace SeanMorris\Access\Form;
class LoginForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct($skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$skeleton += static::skeleton($skeleton);

		// if($facebookLoginUrl = static::facebookLink($router, 'index'))
		// {
		// 	$skeleton['facebook'] = [
		// 		'type' => 'html'
		// 		//, 'value' => '<br /><br /><div class="fb-login-button" data-max-rows="1" data-size="medium" data-show-faces="false" data-auto-logout-link="false"></div>'
		// 		, 'value' => sprintf(
		// 			'<a
		// 				href   = "%s"
		// 				class  = "fbLogin"
		// 				target = "_blank"
		// 			>
		// 				<img src = "/SeanMorris/TheWhtRbt/images/facebook_login.png" style = "width:100%%;">
		// 			</a><br />

		// 			- OR -

		// 			<br />'
		// 			, $facebookLoginUrl
		// 		)
		// 	];
		// }

		$skeleton['submit'] = $skeleton['submit'] ?? [];

		$skeleton['submit'] += [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		parent::__construct($skeleton);
	}

	protected static function skeleton($skeleton = [])
	{
		$skeleton['username'] = [
			'_title' => 'username'
			, 'type' => 'text'
		];

		$skeleton['password'] = [
			'_title' => 'password'
			, 'type' => 'password'
		];

		return $skeleton;
	}
}