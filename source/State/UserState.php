<?php
namespace SeanMorris\Access\State;
class UserState extends \SeanMorris\PressKit\State
{
	protected static
	$states	= [
		-1 => [
			'create'	=> TRUE
			, 'read'	 => TRUE
			, 'update'	 => [TRUE, 'SeanMorris\Access\Role\Moderator']
			, 'delete'	 => [FALSE, 'SeanMorris\Access\Role\Administrator']
			, '$id'	=> [
				'write'  => FALSE
				, 'read' => TRUE
			]
			, '$created'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$username'	=> [
				'write'  => TRUE
				, 'read' => TRUE
			]
			, '$email'	=> [
				'write'  => TRUE
				, 'read' => [TRUE, 'SeanMorris\Access\Role\Moderator']
			]
			, '$password'=> [
				'write'  => TRUE
				, 'read' => FALSE
			]
			, '$avatar'	=> [
				'write'  => TRUE
				, 'read' => TRUE
			]
			, '$roles'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$state'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$class'   => FALSE
		]
		, 0 => [
			'create'	=> TRUE
			, 'read'	 => TRUE
			, 'update'	 => [TRUE, 'SeanMorris\Access\Role\Moderator']
			, 'delete'	 => [FALSE, 'SeanMorris\Access\Role\Administrator']
			, '$id'	=> [
				'write'  => FALSE
				, 'read' => TRUE
			]
			, '$created'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$username'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$email'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => [TRUE, 'SeanMorris\Access\Role\Moderator']
			]
			, '$password'=> [
				'write'  => [TRUE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => FALSE
			]
			, '$avatar'	=> [
				'write'  => [TRUE, 'SeanMorris\Access\Role\Moderator']
				, 'read' => TRUE
			]
			, '$roles'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$state'	=> [
				'write'  => [FALSE, 'SeanMorris\Access\Role\Administrator']
				, 'read' => TRUE
			]
			, '$class'   => FALSE
		]
	];
}
