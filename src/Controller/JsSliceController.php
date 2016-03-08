<?php

namespace Drupal\bigmenu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTree;

/**
 * Created by PhpStorm.
 * User: bernhard
 * Date: 08.03.16
 * Time: 10:25
 */
class JsSliceController extends ControllerBase {
    public function getChildren($menu, $menu_link) {
        $build = array();
        $build['#markup'] = 'Hund';
    }
}