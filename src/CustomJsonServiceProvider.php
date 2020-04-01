<?php

namespace Drupal\custom_json;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class ServiceDefinitionAlter
 *
 * @package Drupal\custom_json
 */
class CustomJsonServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
// Blueprint
    $definition = $container->getDefinition('subrequests.blueprint_manager');
// use  own class
    $definition->setClass('Drupal\custom_json\Service\SubrequestsModuleFix');
  }

}