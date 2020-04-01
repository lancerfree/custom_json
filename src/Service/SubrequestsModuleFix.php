<?php

namespace Drupal\custom_json\Service;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\subrequests\Blueprint\BlueprintManager;
use Drupal\Core\Cache\CacheableMetadata;

class SubrequestsModuleFix extends BlueprintManager{


  /**
   * Fix for subrequests module - response is merged ugly and broke one
   *
   * @param \Symfony\Component\HttpFoundation\Response[] $responses
   *   The responses to combine.
   * @param string $format
   *   The format to combine the responses on. Default is multipart/related.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The combined response with a 207.
   */
  public function combineResponses(array $responses, $format) {
    /** @var CacheableResponse  $response*/
     $response = parent::combineResponses($responses, $format);

     // Main Changes! Disabled caching for master request
    // that broke children request cache
    $cache = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => 0,
      ],
    ]);

    return $response->addCacheableDependency($cache);
  }


}
