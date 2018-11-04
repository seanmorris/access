<?php
namespace SeanMorris\Access\Route;
class RoleRoute extends \SeanMorris\PressKit\Controller
{
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

		$form = new \SeanMorris\PressKit\Form\Form([
			'id' => [
				'_title' => 'Id'
				, 'type' => 'hidden'
			]

			, 'keyword' => [
				'_title' => 'keyword'
				, 'type' => 'text'
			]
		]);

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

	public function test()
	{
		$session = \SeanMorris\Ids\Meta::staticSession(1);
		$allRoles = \SeanMorris\Ids\Linker::get('roles', TRUE);
		$lines = [];
		$user = FALSE;


		if(isset($session['user']) && $session['user'])
		{
			$user = $session['user'];

			foreach($allRoles as $role)
			{
				$lines[] = sprintf(
					"%s\t%s\t%s<br />"
					, $user->username
					, $role
					, $user->hasRole($role)
						? 'YES'
						: 'NO'
				);
			}
		}

		//var_dump($session, $user);

		return "ROLE TEST<br />" . implode($lines);
	}
}
