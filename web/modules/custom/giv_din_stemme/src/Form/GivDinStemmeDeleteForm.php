<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a gds entity.
 *
 * @ingroup content_entity_example
 */
class GivDinStemmeDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', ['%name' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the giv din stemme list.
   */
  public function getCancelUrl() {
    return new Url('entity.gds.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will also delete the attached audio file and cannot be undone.');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme $entity */
    $entity = $this->getEntity();
    $file = $entity->getFile();

    $form_state->setRedirect('entity.gds.collection');

    // Attempt deleting entity before file,
    // to avoid deleting file if entity cannot be deleted.
    try {
      $entity->delete();
    }
    catch (EntityStorageException $e) {
      $this->logger('content_entity_example')->error($e->getMessage());
      $this->messenger()->addError('Failed deleting entity. Contact administrator.');
    }

    try {
      $file->delete();
    }
    catch (EntityStorageException $e) {
      $this->logger('content_entity_example')->error($e->getMessage());
      $this->messenger()->addError(sprintf('Failed to file (%d) entity. Contact administrator.', $file->id() ?? 0));
      return;
    }

    $this->logger('content_entity_example')->notice('@type: deleted %title.',
      [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->label(),
      ]);
  }

}
