<?php

namespace Drupal\giv_din_stemme\Helper;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
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
   * Lock name for updating states.
   */
  const GIV_DIN_STEMME_UPDATE_STATE_LOCK = 'giv_din_stemme_update_state_lock';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   Uuid generator.
   * @param \Drupal\Core\State\State $state
   *   State system.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Php $uuid,
    protected State $state,
    protected LockBackendInterface $lock,
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

    $this->getLock();

    $totalDonationDuration = $this->getTotalDonationDuration() ?? 0;
    $this->state->set(self::GIV_DIN_STEMME_TOTAL_DONATION_DURATION_STATE_KEY, $totalDonationDuration + $duration);

    $this->lock->release(self::GIV_DIN_STEMME_UPDATE_STATE_LOCK);
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

    $this->getLock();

    $currentTotal = $this->getTotalNumberOfDonations() ?? 0;
    $this->state->set(self::GIV_DIN_STEMME_DONATION_COUNT_STATE_KEY, $currentTotal + 1);

    $this->lock->release(self::GIV_DIN_STEMME_UPDATE_STATE_LOCK);
  }

  /**
   * Gets the GIV_DIN_STEMME_UPDATE_STATE_LOCK lock.
   */
  private function getLock(): void {
    // Attempt acquiring lock, wait if failed.
    // @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Lock%21LockBackendInterface.php/group/lock/11.x
    while (!$this->lock->acquire(self::GIV_DIN_STEMME_UPDATE_STATE_LOCK)) {
      $this->lock->wait(self::GIV_DIN_STEMME_UPDATE_STATE_LOCK);
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

  /**
   * Get all accessible gds elements and their references.
   *
   * @return array
   *   A list of gds elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGds(): array {
    $output = [];
    $ids = $this->entityTypeManager->getStorage('gds')->getQuery()
      ->accessCheck()
      ->execute();

    $gds = $this->entityTypeManager->getStorage('gds')->loadMultiple($ids);
    foreach ($gds as $element) {
      $output[$element->id()] = [
        'gds' => $element,
      ];
    }

    return $output;
  }

}
