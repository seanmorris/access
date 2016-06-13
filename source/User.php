<?php
namespace SeanMorris\Access;
class User extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $publicId
		, $created
		, $username
		, $email
		, $password
		, $avatar
		, $roles
		, $state
	;

	protected static
		$table = 'user'
		, $hasOne = [
			'avatar' => 'SeanMorris\PressKit\Image'
			, 'state' => 'SeanMorris\Access\State\UserState'
		]
		, $hasMany = [
			'roles' => 'SeanMorris\Access\Role'
		]
		, $createColumns = [
			'publicId' => 'UNHEX(REPLACE(UUID(), "-", ""))'
			, 'written' => 'UNIX_TIMESTAMP()'
		]
		, $readColumns = [
			'publicId' => 'HEX(%s)'
		]
		, $updateColumns = [
			'publicId' => 'UNHEX(%s)'
			, 'edited' => 'UNIX_TIMESTAMP()'
		]
		, $byPublicId = [
			'where' => [['publicId' => 'UNHEX(?)']]
		]
		, $byUsername = [
			'where' => [['username' => '?']]
		]
		, $ignore = [
			'class'
		]
	;

	public function login($password)
	{
		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);
		return $passwordHasher->CheckPassword($password, $this->password);
	}


	public function register($username, $password, $email)
	{
		$this->username = $username;
		$this->email    = $email;

		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

		$this->password = $passwordHasher->HashPassword($password);

		return $this->create();
	}

	protected static function beforeConsume($instance, &$skeleton)
	{
		/*
		if(!isset($skeleton['password']) || !$skeleton['password'])
		{
			$skeleton['password'] = $instance->password;
		}
		else
		{
			$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

			$skeleton['password'] = $passwordHasher->HashPassword($skeleton['password']);
		}
		*/
	}

	public function hasRole($checkRole)
	{
		static $roles;

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
		return $user->id == $this->id;
	}

	protected function ensureState()
	{
		if(isset(static::$hasOne['state'])
			&& static::$hasOne['state']
			&& !$this->state
			&& $owner = \SeanMorris\Access\Route\AccessRoute::_currentUser()
		){
			\SeanMorris\Ids\Log::debug(
				'Creating new state '
				. static::$hasOne['state']
				. ' for '
				. get_class($this)
			);
			$stateClass = static::$hasOne['state'];
			$state = new $stateClass;
			$state->consume([
				'owner' => $owner->id
			]);
			$state->save();
			$this->state = $state->id;
			$this->id && $this->forceSave();

			return $state;
		}	

		return $this->state;
	}
}