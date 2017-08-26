<?php
namespace SeanMorris\Access;
class User extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $publicId
		, $created
		, $facebookId
		, $username
		, $email
		, $password
		, $avatar
		, $roles
		, $state
	;

	protected static
		$table = 'AccessUser'
		, $hasOne = [
			'avatar' => 'SeanMorris\PressKit\Image'
			, 'state' => 'SeanMorris\Access\State\UserState'
		]
		, $hasMany = [
			'roles' => 'SeanMorris\Access\Role'
		]
		, $createColumns = [
			'publicId' => 'UNHEX(REPLACE(UUID(), "-", ""))'
			, 'created' => 'NOW()'
			, 'role' => '0'
		]
		, $readColumns = [
			'publicId' => 'HEX(%s)'
		]
		, $updateColumns = [
			'publicId' => 'UNHEX(%s)'
		]
		, $byPublicId = [
			'where' => [['publicId' => 'UNHEX(?)']]
		]
		, $byUsername = [
			'where' => [['username' => '?']]
		]
		, $byEmail = [
			'where' => [['email' => '?']]
		]
		, $byFacebookId = [
			'where' => [['facebookId' => '?']]
		]
		, $ignore = ['class']
	;

	public function login($password)
	{
		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);
		return $passwordHasher->CheckPassword($password, $this->password);
	}
	/*
	public function register($username, $password, $email)
	{
		$this->username = $username;
		$this->email    = $email;

		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

		$this->password = $passwordHasher->HashPassword($password);

		return $this->create();
	}
	*/

	protected static function beforeConsume($instance, &$skeleton)
	{
		if(!isset($skeleton['password']) || !$skeleton['password'])
		{
			$skeleton['password'] = $instance->password;
		}
		else
		{
			$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

			$skeleton['password'] = $passwordHasher->HashPassword($skeleton['password']);
		}
	}

	public function hasRole($checkRole)
	{
		static $roles;

		if($this->id == 1)
		{
			return true;
		}

		if($this->id && $checkRole == 'SeanMorris\Access\Role\User')
		{
			return true;	
		}

		if(!$roles)
		{
			$roles = $this->getSubjects('roles');
		}

		foreach($roles as $role)
		{
			if(is_a($role, $checkRole, TRUE))
			{
				return true;
			}
		}

		return false;
	}

	public function isSame($user)
	{
		return $user->id && $this->id && $user->id === $this->id;
	}

	protected function ensureState()
	{
		if(!$this->id)
		{
			//return;
		}
		
		$state = parent::ensureState();

		if($state && !$this->id)
		{
			$state->change(-1, TRUE);
			$this->state = $state;
		}

		return $state;
	}
}
