<?php
namespace SeanMorris\Access;
class Linker
{
	public static function expose()
	{
		return [
			'roles' => [
				'SeanMorris\Access\Role\Administrator'
				, 'SeanMorris\Access\Role\Anonymous'
				, 'SeanMorris\Access\Role\Moderator'
				, 'SeanMorris\Access\Role\SuperModerator'
				, 'SeanMorris\Access\Role\SuperUser'
				, 'SeanMorris\Access\Role\User'
			]
		];
	}
}