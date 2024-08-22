<?php

namespace Drupal\giv_din_stemme\Helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Get frontpage values.
   *
   * @return int[]
   *   Array of frontpage values
   */
  public function getFrontpageValues(): array {
    // @todo when the entity is settled.
    return [
      'donations' => 15,
      'minutes' => 43
    ];
  }
}
