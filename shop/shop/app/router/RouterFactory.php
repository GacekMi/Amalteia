<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * @return Nette\Application\IRouter
	 */
	 public static function createRouter() {
        $router = new RouteList();
        /**$router[] = new Route("<filterRenderType>/User/default/<ajax>/", array(
            'filterRenderType' => 'inner',
            'presenter' => 'Homepage',
            'action' => 'default',
            'ajax' => 'on',
        ));*/
        $router[] = new Route('index.php', 'Home:default', Route::ONE_WAY);
        $router[] = new Route('login', 'Sign:in', Route::ONE_WAY);
        $router[] = new Route('[<locale=cs cs|en>/]<presenter>/<action>[/<id>]', [
					'presenter' => 'Gallery',
					'action' => 'default',
					'id' => null
				]);
  
       
        return $router;
    }
}
