<?php
namespace SeanMorris\Access;
class Role extends \SeanMorris\Ids\Model
{
	protected
		$id
		, $class
		, $publicId
		, $assigned
		, $grantedBy
	;
	protected static
		$table = 'Role'
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

	
}