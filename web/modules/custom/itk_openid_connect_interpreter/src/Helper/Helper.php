<?php

namespace Drupal\itk_openid_connect_interpreter\Helper;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * Implements hook_page_attachments().
   *
   * Injects custom CSS into CKEditor5 the Gin way (cf.
   * https://www.drupal.org/docs/contributed-themes/gin-admin-theme/custom-theming#s-module-recommended-way).
   */
  public function openidConnectUserinfoAlter(array &$userinfo, array $context) {
    $userinfo['email'] = Crypt::hashBase64('yde001+1@gmail.com') . '@itkdev.dk';
    $userinfo['name'] =  Crypt::hashBase64('name');
  }

  public function alterUserName(string &$name, AccountInterface $user) {
    // Alter citizens name.
    if (count($user->getRoles()) === 1 && $user->id() > 1) {
      $name = $this->t('Profile');
    }
  }
}
