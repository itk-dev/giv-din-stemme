<?php

namespace Drupal\giv_din_stemme\Helper;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drupal\giv_din_stemme\Exception\InvalidRequestException;

/**
 * A helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Component\Uuid\Php $uuid
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Php $uuid,
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

  /**
   * Gets random Text.
   */
  public function getRandomText(): EntityInterface
  {

    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'text'
    ]);

    $count  = count($nodes);
    $keys = array_keys($nodes);

    $randomKey = $keys[rand(0, $count - 1)];

    return $nodes[$randomKey];
  }

  /**
   * Generate new uuid.
   */
  public function generateUuid(): string
  {
    return $this->uuid->generate();
  }

  /**
   * Gets unique GivDinStemme.
   */
  public function getGivDinStemmeByCollectionIdAndDelta(string $collectionId, string $delta): GivDinStemme {
    $result = $this->entityTypeManager->getStorage('gds')->loadByProperties([
      'collection_id' => $collectionId,
      'collection_delta' => $delta,
    ]);

    if (1 !== count($result)) {
      throw new InvalidRequestException('Unique GivDinStemme not found');
    }

    return reset($result);
  }

  /**
   * Gets count of GivDinStemme in collection.
   */
  public function getCountOfGivDinStemmeByCollectionId(string $collectionId): int {
    $result = $this->entityTypeManager->getStorage('gds')->loadByProperties([
      'collection_id' => $collectionId,
    ]);

    return count($result);
  }
}
