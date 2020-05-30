<?php
namespace SeanMorris\Access;
class User extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $publicId
		, $class
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
		, $byId = [
			'with'  => ['state'   => 'byNull']
			, 'where' => [['id' => '?']]
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
		, $bySearch = [
			'named' => TRUE
			, 'distinct' => TRUE
			, 'where' => [
				'OR' => [
					['id'         => '?', '=',    '%s',     'id',      FALSE]
					, ['username' => '?', 'LIKE', '%%%s%%', 'keyword', FALSE]
				]
			]
		]
	;

	public function login($password)
	{
		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

		return $passwordHasher->checkPassword($password, $this->password);
	}

	public function checkPassword($password)
	{
		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, FALSE);

		return $passwordHasher->checkPassword($password, $this->password);
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
		// \SeanMorris\Ids\Log::debug(__FUNCTION__, $checkRole, $this->id, $checkRole == 'SeanMorris\Access\Role\User');

		static $cache = [];

		if($this->id == 1)
		{
			return TRUE;
		}

		if($this->id && $checkRole == 'SeanMorris\Access\Role\User')
		{
			return TRUE;
		}

		if($this->id)
		{
			if(isset($cache[$this->id]) && array_key_exists($checkRole, $cache[$this->id]))
			{
				return $cache[$this->id][$checkRole];
			}

			$roles = $this->_getSubjects('roles');

			foreach($roles as $role)
			{
				if(is_a($role, $checkRole, TRUE))
				{
					$cache[$this->id][$checkRole] = TRUE;
					return TRUE;
				}
			}

			$cache[$this->id][$checkRole] = FALSE;

			return FALSE;
		}
	}

	public function isSame($user)
	{
		// \SeanMorris\Ids\Log::debug($user, $this);
		return $user->id && $this->id && $user->id === $this->id;
	}

	protected function ensureState($force = FALSE)
	{
		if(!$this->id)
		{
			//return;
		}

		$state = parent::ensureState($force);

		if($state && !$this->id)
		{
			$state->change(-1, TRUE);
			$this->state = $state;
		}

		return $state;
	}

	protected static function afterCreate($instance, &$skeleton)
	{
		$state = $instance->getSubject('state');

		$state->addSubject('owner', $instance, true);

		$state->save();
	}

	public function can($action, $point = NULL)
	{
		if($action == 'read' && $point == 'password')
		{
			return false;
		}

		return parent::can($action, $point);
	}
}
