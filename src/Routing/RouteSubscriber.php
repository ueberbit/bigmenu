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
    if ($route = $collection->get('entity.menu.edit_form')) {
      $route->setPath('/admin/structure/menu/manage/{menu}/bigmenu');
    }
  }

}
