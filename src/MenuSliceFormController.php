<?php

/**
 * @file
 * Contains \Drupal\bigmenu\MenuSliceFormController.
 */

namespace Drupal\bigmenu;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;

class MenuSliceFormController extends MenuFormController {

  /**
   * @var \Drupal\menu_link\MenuLinkInterface
   */
  protected $menuLink;


  protected function prepareEntity() {
    $this->menuLink = $this->getRequest()->attributes->get('menu_link');
  }


  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $menu = $this->entity;
    $menuLink = $this->menuLink;

    // Add menu links administration form for existing menus.
    if (!$menu->isNew() || $menu->isLocked()) {
      // Form API supports constructing and validating self-contained sections
      // within forms, but does not allow to handle the form section's submission
      // equally separated yet. Therefore, we use a $form_state key to point to
      // the parents of the form section.
      // @see self::submitOverviewForm()
//       $form_state['menu_overview_form_parents'] = array('links');
      $form['links'] = array();
      $form['links'] = $this->buildOverviewForm($form['links'], $form_state, 2);
    }

    return $form;
  }


  /**
   * @param array $form
   * @param array $form_state
   * @param int $depth
   * @return array
   */
  protected function maxDepthbuildOverviewForm(array &$form, FormStateInterface $form_state, $depth = 1) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    $form['#tree']  = TRUE;
    $form['#theme'] = 'menu_overview_form';
    //$form_state += array('menu_overview_form_parents' => array());

    $form['#attached']['css'] = array(drupal_get_path('module', 'menu') . '/css/menu.admin.css');

    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters(
      $this->entity->getOriginalId()
    );

    /*
     * STUFF
     */
    $menu_tree_parameters = new MenuTreeParameters();
    $menu_tree_parameters->minDepth = 0;
    $menu_tree_parameters->maxDepth = 1;

    $test = MenuLinkContent::load($this->menuLink)->getPluginId();
    $menu_tree_parameters->setRoot($test);

    $tree = \Drupal::menuTree()->load(
      $this->entity->getOriginalId(),
      $menu_tree_parameters
    );

    /*
     * / END-STUFF
     */

    $links = array();
//    $query = $this->entityQueryFactory->get('menu_link')
//      ->condition('menu_name', $this->entity->id());
//    for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
//      $query->sort('p' . $i, 'ASC');
//    }
//    $result = $query->execute();
//
//    if (!empty($result)) {
//      $links = $this->menuLinkStorage->loadMultiple($result);
//    }

//    $delta = max(count($links), 50);
    // We indicate that a menu administrator is running the menu access check.
//    $this->getRequest()->attributes->set('_menu_admin', TRUE);
//    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    $m_tree = $this->buildOverviewTreeForm($tree);
    $form = array_merge($form, $m_tree);
    $form = $this->buildOverviewTreeForm($tree);
    $form['#empty_text'] = t('There are no menu links yet. <a href="@link">Add link</a>.', array('@link' => url('admin/structure/menu/manage/' . $this->entity->id() . '/add')));

    return $form;
  }
}
