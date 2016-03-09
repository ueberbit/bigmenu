<?php

/**
 * @file
 * Contains \Drupal\bigmenu\MenuFormController.
 */

namespace Drupal\bigmenu;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\menu_ui\MenuForm as DefaultMenuFormController;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Class MenuFormController
 * @package Drupal\bigmenu
 */
class MenuFormController extends DefaultMenuFormController
{

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param int $depth
   * @return array
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state, $depth = 1)
  {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    // Use Menu UI adminforms
    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    // 'edit_bigmenu' == $this->operation

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
    $count = function (array $tree) {
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
        $mlid = (int)$links[$id]['#item']->link->getMetaData()['entity_id'];
        $url = Url::fromRoute(
            'bigmenu.menu_link_js',
            array('menu' => 'main', 'menu_link' => $mlid)
        );

        // 'below' contains both immediate children and something else
        $strings = array(
            '!show_children' => t('Show children'),
            '%count' => 123,
            '!tooltip' => t('Click to expand and show child items'),
        );
        $text = strtr('+ !show_children (%count)', $strings);
        $mlid = (int)$links[$id]['#item']->link->getMetaData()['entity_id'];
        $url = Url::fromRoute(
            'bigmenu.menu_link_js',
            array('menu' => 'main', 'menu_link' => $mlid)
        );

        $url->setOption('attributes', array('class' => 'use-ajax'));

        $link_generator = \Drupal::service('link_generator');
        $indicator = $link_generator->generate($text, $url);


        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = array(
            'checkbox',
            'menu-enabled'
        );

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];

        $form['links'][$id]['title'][] = array(
            '#markup' => '<br />' . $indicator,
            '#ajax' => array(
              // Function to call when event on form element triggered.
                'callback' => array($this,'hund'),
              // Effect when replacing content. Options: 'none' (default), 'slide', 'fade'.
                'effect' => 'fade',
              // Javascript event to trigger Ajax. Currently for: 'onchange'.
                'event' => 'click',
                'progress' => array(
                  // Graphic shown to indicate ajax. Options: 'throbber' (default), 'bar'.
                    'type' => 'throbber',
                  // Message to show along progress graphic. Default: 'Please wait...'.
                    'message' => NULL,
                ),
            ),
        );

        $form['user_name'] = array(
            '#type' => 'big_menu_button',
            '#title' => 'Username',
            '#value' => 'Username',
            '#url' => '#',
            '#description' => 'Please enter in a username',
            '#ajax' => array(
              // Function to call when event on form element triggered.
                'callback' => array($this, 'hund'),
              // Effect when replacing content. Options: 'none' (default), 'slide', 'fade'.
                'effect' => 'fade',
              // Javascript event to trigger Ajax. Currently for: 'onchange'.
                'event' => 'click',
                'progress' => array(
                  // Graphic shown to indicate ajax. Options: 'throbber' (default), 'bar'.
                    'type' => 'throbber',
                  // Message to show along progress graphic. Default: 'Please wait...'.
                    'message' => NULL,
                ),
            ),
        );
      }
    }

    return $form;
  }

  /**
   * @param array $form
   * @param array $form_state
   * @return AjaxResponse
   */
  public function hund(array &$form, array &$form_state) {

    $elem = $form_state->getTriggeringElement();
    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    // @TODO: Cleanup the following.
    // Check if Username exists and is not Anonymous User ('').
    if (user_load_by_name($form_state->getValue('user_name')) && $form_state->getValue('user_name') != false) {
      $text = 'User Found';
      $color = 'green';
    } else {
      $text = 'No User Found';
      $color = 'red';
    }

    // Add a command to execute on form, jQuery .html() replaces content between tags.
    // In this case, we replace the desription with wheter the username was found or not.
    $ajax_response->addCommand(new HtmlCommand('form#menu-edit-bigmenu-form', $this->buildOverviewForm($form, $form_state, 15)));

    // Add a command, InvokeCommand, which allows for custom jQuery commands.
    // In this case, we alter the color of the description.
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name--description', 'css', array('color', $color)));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }
}
