<?php
/**
 * @file
 * Contains \Drupal\bigmenu\BigmenuPermissions.
 */

namespace Drupal\bigmenu;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the bigmenu module.
 */
class BigmenuPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new BigmenuPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of bigmenu permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    $menus = menu_ui_get_menus();

    foreach ($menus as $name => $title) {
      $permissions['use bigmenu for ' . $name . ' menu'] = array(
        'title' => t('Use big menu for the %menu menu', array('%menu' => $title)),
        'description' => array(
          '#prefix' => '<em>',
          '#markup' => $this->t('Use big menu if a menu contains many items.'),
          '#suffix' => '</em>'
        )
      );
    }
    return $permissions;

  }

}
