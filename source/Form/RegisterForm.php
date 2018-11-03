<?php
namespace SeanMorris\Access\Form;
class RegisterForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct($skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		// if($facebookLoginUrl = static::facebookLink($router, 'index'))
		// {
		// 	$skeleton['facebook'] = [
		// 		'type' => 'html'
		// 		//, 'value' => '<br /><br /><div class="fb-login-button" data-max-rows="1" data-size="medium" data-show-faces="false" data-auto-logout-link="false"></div>'
		// 		, 'value' => sprintf(
		// 			'<a href = "%s" class = "fbLogin">
		// 				<img src = "/SeanMorris/TheWhtRbt/images/facebook_login.png" style = "width:100%%;">
		// 			</a><br />

		// 			- OR -

		// 			<br />'
		// 			, $facebookLoginUrl
		// 		)
		// 	];
		// }

		$skeleton['username'] = [
			'_title' => 'username'
			, 'type' => 'text'
		];

		$skeleton['email'] = [
			'_title' => 'email address'
			, 'type' => 'text'
			, '_validators' => [
				'SeanMorris\Form\Validator\Email' =>
					'%s must be a valid email.'
			]
		];

		$skeleton['password'] = [
			'_title' => 'password'
			, 'type' => 'password'
		];

		$skeleton['confirmPassword'] = [
			'_title' => 'confirm password'
			, 'type' => 'password'
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		parent::__construct($skeleton);
	}
}
