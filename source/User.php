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
		, $roles
	;

	protected static
		$table = 'user'
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
}
