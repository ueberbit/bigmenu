<?php
/**
 * @file
 * Contains \Drupal\bigmenu\Routing\RouteSubscriber.
 */

namespace Drupal\bigmenu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Reroute the menu edit form to one using bigmenu.
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'entity.menu.edit_form':
        case 'entity.menu.add_link_form':
          $route->setPath('/admin/structure/menu/manage/{menu}/bigmenu');
          if (\Drupal::moduleHandler()->moduleExists('menu_admin_per_menu')) {
            $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuAccess']);
          }
          break;

        case 'bigmenu.menu':
        case 'bigmenu.menu_link':
          if (\Drupal::moduleHandler()->moduleExists('menu_admin_per_menu')) {
            $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuAccess']);
          }
          break;
      }
    }
  }

}
