<?php
namespace SeanMorris\Access\Route;
class RoleRoute extends \SeanMorris\PressKit\Controller
{
	protected static $forms = [
		'edit' => 'SeanMorris\Access\Form\RoleForm'
	];

	public function index($router)
	{
		$params = $router->request()->params();
		$roles = [];

		if(isset($params['id']))
		{
			if($role = \SeanMorris\Access\Role::loadOneById($params['id']))
			{
				$roles[] = $role->unconsume();
			}
		}
		else
		{
			$roles = \SeanMorris\Ids\Linker::get('roles', TRUE);

			if(isset($params['keyword']))
			{
				$keyword = $params['keyword'];

				$roles = array_filter(
					$roles
					, function($role) use($keyword)
					{
						$role = strtolower($role);
						$keyword = strtolower($keyword);

						return strpos($role, $keyword) !== FALSE;
					}
				);
			}

			$roles = array_map(
				function($role)
				{
					return [
						'title'         => $role
						, 'class'       => $role
						, '_titleField' => 'class'
					];
				}
				, $roles
			);

			$roles = array_values($roles);
		}

		$form = new \SeanMorris\Access\Form\RoleForm;

		if(isset($params['api']))
		{
			$resource = new \SeanMorris\PressKit\Api\Resource(
				$router, ['body'=> $roles]
			);
			$resource->meta('form', $form->toStructure());
			echo $resource->encode($params['api']);
			die;
		}

		return sprintf('<pre>%s</pre>', print_r($roles, 1));
	}
}
