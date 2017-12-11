<?php
namespace SeanMorris\Access;
class Package extends \SeanMorris\Ids\Package
{
	protected static
		$tables = [
			'main' => [
				'AccessRole'
				, 'AccessUser'
			]
		]
	;
}