<?php
namespace SeanMorris\Access\Form;
class RoleForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct($skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$skeleton += static::skeleton($skeleton);

		parent::__construct($skeleton);
	}

	protected static function skeleton($skeleton = [])
	{
		$skeleton = [
			'id' => [
				'_title' => 'Id'
				, 'type' => 'hidden'
			]

			, 'keyword' => [
				'_title'         => 'keyword'
				, 'type'         => 'text'
				, 'autocomplete' => 'off'
			]
		];

		return $skeleton;
	}
}
