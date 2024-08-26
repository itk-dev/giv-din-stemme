<?php

namespace Drupal\giv_din_stemme\Helper;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drupal\giv_din_stemme\Exception\NoTextFoundException;
use Drupal\giv_din_stemme\Exception\UniqueGivDinStemmeNotFoundException;
use Drupal\node\NodeInterface;

/**
 * A helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * Donation count state key.
   */
  const GIV_DIN_STEMME_DONATION_COUNT_STATE_KEY = 'giv_din_stemme_donation_count';

  /**
   * Total donation duration (in seconds) state key.
   */
  const GIV_DIN_STEMME_TOTAL_DONATION_DURATION_STATE_KEY = 'giv_din_stemme_total_donation_duration';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   Uuid generator.
   * @param \Drupal\Core\State\State $state
   *   State system.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Php $uuid,
    protected State $state,
  ) {
  }

  /**
   * Get frontpage values.
   *
   * @return int[]
   *   Array of frontpage values
   */
  public function getFrontpageValues(): array {
    return [
      'donations' => $this->getTotalNumberOfDonations() ?? 0,
      'minutes' => $this->getTotalDonationDuration() ? ceil($this->getTotalDonationDuration() / 60) : 0,
    ];
  }

  /**
   * Gets total donation duration.
   */
  private function getTotalDonationDuration(): ?int {
    $value = $this->state->get(self::GIV_DIN_STEMME_TOTAL_DONATION_DURATION_STATE_KEY);

    return $value ? (int) $value : NULL;
  }

  /**
   * Update total donation duration state.
   */
  public function updateTotalDonationDuration(int $duration): void {
    $currentDuration = $this->getTotalDonationDuration();

    if (is_null($currentDuration)) {
      $this->state->set(self::GIV_DIN_STEMME_TOTAL_DONATION_DURATION_STATE_KEY, $duration);
    }
    else {
      $this->state->set(self::GIV_DIN_STEMME_TOTAL_DONATION_DURATION_STATE_KEY, $currentDuration + $duration);
    }
  }

  /**
   * Gets total number of donations.
   */
  private function getTotalNumberOfDonations(): ?int {
    $value = $this->state->get(self::GIV_DIN_STEMME_DONATION_COUNT_STATE_KEY);

    return $value ? (int) $value : NULL;
  }

  /**
   * Adds one to total number of donations.
   */
  public function updateTotalNumberOfDonations(): void {
    $currentTotal = $this->getTotalNumberOfDonations();

    if (is_null($currentTotal)) {
      $this->state->set(self::GIV_DIN_STEMME_DONATION_COUNT_STATE_KEY, 1);
    }
    else {
      $this->state->set(self::GIV_DIN_STEMME_DONATION_COUNT_STATE_KEY, $currentTotal + 1);
    }
  }

  /**
   * Gets random published Text.
   */
  public function getRandomPublishedText(): NodeInterface {

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'text',
      'status' => 1,
    ]);

    if (empty($nodes)) {
      throw new NoTextFoundException('No text node found.');
    }

    return $nodes[array_rand($nodes)];
  }

  /**
   * Generate new uuid.
   */
  public function generateUuid(): string {
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

    $count = count($result);

    if (1 !== $count) {
      throw new UniqueGivDinStemmeNotFoundException(sprintf('Unique GivDinStemme entity not found. Found %d.', $count));
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
