<?php

namespace Drupal\itk_openid_connect_interpreter\Helper;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * A helper.
 */
class Helper {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * Implements hook_page_attachments().
   *
   * Injects custom CSS into CKEditor5 the Gin way (cf.
   * https://www.drupal.org/docs/contributed-themes/gin-admin-theme/custom-theming#s-module-recommended-way).
   */
  public function openidConnectUserinfoAlter(array &$userinfo, array $context) {
    $userinfo['email'] = 'yde001+1@gmail.com';
  }
}
