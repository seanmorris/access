<?php
namespace SeanMorris\Access;
class Role extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $class
		, $publicId
		, $assigned
		, $grantedBy
		, $_titleField = 'class'
	;
	protected static
		$table = 'AccessRole'
		, $createColumns = [
			'assigned' => 'UNIX_TIMESTAMP()'
			, 'publicId' => 'UNHEX(REPLACE(UUID(), "-", ""))'
		]
		, $readColumns = [
			'publicId' => 'HEX(%s)'
		]
		, $updateColumns = [
			'publicId' => 'UNHEX(%s)'
		]
		, $byPublicId = [
			'where' => [['publicId' => '?']]
		]
	;

	public function create()
	{
		$this->class = get_called_class();
		$this->grantedBy = 1;

		return parent::create();
	}

	public function can($action, $point = NULL)
	{
		if($action == 'read')
		{
			return true;
		}

		return parent::can($action, $point);
	}
}