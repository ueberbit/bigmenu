<?php

/**
 * @file
 * Contains \Drupal\bigmenu\MenuFormController.
 */

namespace Drupal\bigmenu;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\menu_ui\MenuForm as DefaultMenuFormController;

class MenuFormController extends DefaultMenuFormController {

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function buildOverviewFormStub(array &$form, FormStateInterface $form_state) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    $form['#tree'] = TRUE;
    $form['#theme'] = 'menu_overview_form';
//    $form_state += array('menu_overview_form_parents' => array());

    $form['#attached']['css'][] = drupal_get_path('module', 'menu') . '/css/menu.admin.css';
    $form['#attached']['css'][] = drupal_get_path('module', 'bigmenu') . '/bigmenu.css';
    $form['#attached']['js'][] = drupal_get_path('module', 'bigmenu') . '/bigmenu.js';
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param int $depth
   * @return array
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state, $depth = 1) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    $tree_params = new MenuTreeParameters();
    $tree_params->setMaxDepth($depth);
    $tree = $this->menuTree->load($this->entity->id(), $tree_params);

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    // Determine the delta; the number of weights to be made available.
    $count = function(array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };

    // Tree maximum or 50.
    $delta = max($count($tree), 50);

    $form['links'] = array(
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => array(
        $this->t('Menu link'),
        array(
          'data' => $this->t('Enabled'),
          'class' => array('checkbox'),
        ),
        $this->t('Weight'),
        array(
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ),
      ),
      '#attributes' => array(
        'id' => 'menu-overview',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );

    // No Links available (Empty menu)
    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->url('entity.menu.add_link_form', ['menu' => $this->entity->id()], [
        'query' => ['destination' => $this->entity->url('edit-form')],
      ]),
    ]);

    $links = $this->buildOverviewTreeForm($tree, $delta);
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = array('menu-parent');
        $element['weight']['#attributes']['class'] = array('menu-weight');
        $element['id']['#attributes']['class'] = array('menu-id');

        $form['links'][$id]['title'] = array(
          array(
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ),
          $element['title'],
        );

        /*
         * TODO:
         */

        // 'below' contains both immediate children and something else
        $strings = array(
          '!show_children' => t('Show children'),
          '%count' => count($data['below']),
          '!tooltip' => t('Click to expand and show child items'),
        );
        $text = strtr('+ !show_children (%count)', $strings);
        $mlid = (int)$links[$id]['#item']->link->getMetaData()['entity_id'];
        $url = Url::fromRoute(
          'bigmenu.menu_link',
          array('menu' => 'main', 'menu_link' => $mlid)
        );
        $indicator = \Drupal::l(
          $text,
          $url
        );

        $form[$id]['title']['#markup'] .= ' ' . $indicator->getGeneratedLink();

        /*
         * / End Todo
         */
        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = array('checkbox', 'menu-enabled');

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];

        $form['links'][$id]['title'][] = array('#markup'=>$indicator);
      }
    }

    return $form;
  }

}
