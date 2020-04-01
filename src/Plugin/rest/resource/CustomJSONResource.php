<?php

namespace Drupal\custom_json\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom json resource
 *
 * This resource needed to json export main menu
 *
 * @RestResource(
 *   id = "custom_json:custom_json",
 *   label = @Translation("Custom JSON Blocks Settings Export"),
 *   uri_paths = {
 *     "canonical" = "/api/cj-module/data"
 *   }
 * )
 */
class CustomJSONResource extends ResourceBase {


  /**
   * Language Manager.
   *
   * @var LanguageManagerInterface
   */
  private $languageManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('custom_json'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // no explicit permissions are required.
    return [];
  }

  /**
   * Gets methods to call.
   *
   * @return array
   */
  public function methodIncludeMap() {
    $map = [
      'card-block_settings' => 'includeCardBlock',
      'export-main-navigation' => 'includeExportMainNavigation',
      'export-sub-navigation' => 'includeExportSubNavigation',

    ];
    return $map;
  }

  /**
   * Responds to GET requests.
   *
   * @return ResourceResponse;
   */
  public function get() {

    $include_query = \Drupal::request()->query->get('include');

    $include = [];

    $methods = $this->methodIncludeMap();
    $result = [];
    if ($include_query) {
      $items = explode(',', $include_query);

      foreach ($items as $item) {
        $item_trimmed = trim($item);
        if (isset($methods[$item_trimmed])) {
          $data_item = $this->{$methods[$item_trimmed]}();
          if ($data_item) {
            $result[$item_trimmed] = $data_item;
          }
          $include[] = $item_trimmed;
        }
      }

    } else {
      foreach ($methods as $key => $item) {
        $data_item = $this->{$item}();
        if ($data_item) {
          $result[$key] = $data_item;
        }

        $include[] = $key;
      }
    }

    $response_arr['langcode'] = $this->languageManager->getCurrentLanguage()->getId();

    if ($result) {
      $response_arr['result'] = $result;
    }
    if ($include) {
      $response_arr['result_contains'] = $include;
    }


    $resourceResponse = new ResourceResponse($response_arr);
    // Add default cache parameters.
    $cache = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['url.query_args'],
      ],
    ]);

    return $resourceResponse->addCacheableDependency($cache);
  }

  /**
   * Returns Card Block Settings.
   */
  public function includeCardBlock() {
    return $this->getConfigData('custom_json.card_block.settings');
  }

  /**
   * Returns Data of the specified config.
   *
   * @return array
   */
  public function getConfigData($config_name) {
    $lang_code = $this->languageManager->getCurrentLanguage()->getId();
    $raw_data_translated = [];
    if ($lang_code == 'is') {
      $config_lang = $this->languageManager->getLanguageConfigOverride('is', $config_name);
      $translated = $config_lang->get();
      if ($translated) {
        $raw_data_translated = $translated;
      }
    }
    $config = \Drupal::config($config_name);
    $raw_data = $config->getRawData();

    unset($raw_data['_core'], $raw_data['langcode']);
    $raw_data_translated += $raw_data;


    return $raw_data_translated;
  }

  /**
   * Returns Main Menu.
   */
  function includeExportMainNavigation() {
    return $this->getMenuData('export-main-navigation', TRUE);
  }

  /**
   * Returns Sub Menu.
   */
  function includeExportSubNavigation() {
    return $this->getMenuData('export-sub-navigation', TRUE);
  }

  /**
   * Returns Data Menu to json export.
   */
  function getMenuData($menu_name, $node_uuid = FALSE) {
    $menu_tree_service = \Drupal::menuTree();
    $tree = $menu_tree_service->load($menu_name, new \Drupal\Core\Menu\MenuTreeParameters());
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree_service->transform($tree, $manipulators);
    $menu = [];
    $aliasManager = \Drupal::service('path.alias_manager');
    foreach ($tree as $item) {
      $link = [];
      $link['title'] = $item->link->getTitle();
      $link['url'] = $item->link->getUrlObject()->toString(TRUE)->getGeneratedUrl();
      $link['description'] = $item->link->getDescription();

      if ($node_uuid) {
        if (strpos($link['url'], '/is') === 0) {
          $test_link = substr($link['url'], 3);
        } else {
          $test_link = $link['url'];
        }

        $path = $aliasManager->getPathByAlias($test_link);
        $matches = [];
        if (preg_match('/node\/(\d+)/', $path, $matches)) {
          $node = \Drupal\node\Entity\Node::load($matches[1]);
          if ($node) {
            $link['node-uuid'] = $node->uuid();
            $link['bundle'] = $node->bundle();
          }
        }
      }

      $menu[] = $link;
    }

    return $menu;
  }

}
