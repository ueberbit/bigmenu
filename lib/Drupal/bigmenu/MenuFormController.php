<?php

/**
 * @file
 * Contains \Drupal\bigmenu\MenuFormController.
 */

namespace Drupal\bigmenu;

use Drupal\menu_ui\MenuFormController as DefaultMenuFormController;

class MenuFormController extends DefaultMenuFormController {

  protected function buildOverviewFormStub(array &$form, array &$form_state, $depth = 1) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    $form['#tree'] = TRUE;
    $form['#theme'] = 'menu_overview_form';
    $form_state += array('menu_overview_form_parents' => array());

    $form['#attached']['css'][] = drupal_get_path('module', 'menu') . '/css/menu.admin.css';
    $form['#attached']['css'][] = drupal_get_path('module', 'bigmenu') . '/bigmenu.css';
    $form['#attached']['js'][] = drupal_get_path('module', 'bigmenu') . '/bigmenu.js';
  }

  protected function buildOverviewForm(array &$form, array &$form_state, $depth = 1) {
    $this->buildOverviewFormStub($form, $form_state);
    $depth = (int) $depth + 3;

    $links = array();
    $query = $this->entityQueryFactory->get('menu_link')
      ->condition('menu_name', $this->entity->id())
      ->condition('p' . $depth, 0, '=');
    for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
      $query->sort('p' . $i, 'ASC');
    }
    $result = $query->execute();

    if (!empty($result)) {
      $links = $this->menuLinkStorage->loadMultiple($result);
    }

    $delta = max(count($links), 50);
    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $tree = $this->menuTree->buildTreeData($links);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    $form = array_merge($form, $this->buildOverviewTreeForm($tree, $delta));
    $form['#empty_text'] = t('There are no menu links yet. <a href="@link">Add link</a>.', array('@link' => url('admin/structure/menu/manage/' . $this->entity->id() .'/add')));

    return $form;
  }

}
