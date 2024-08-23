<?php

namespace Drupal\giv_din_stemme\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Giv Din Stemme data object.
 *
 * @ingroup gds
 *
 * @ContentEntityType(
 *   id = "gds",
 *   label = @Translation("Giv din stemme"),
 *   base_table = "gds",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class GivDinStemme extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the GivDinStemme entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the GivDinStemme entity.'))
      ->setReadOnly(TRUE);

    // Metadata should contain
    // text part being read
    // user metadata
    // id for text.
    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata'))
      ->setDescription(t('The metadata of the GivDinStemme entity.'));

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t('The recording file of the GivDinStemme entity.'))
      ->setSettings([
        'uri_scheme' => 'private',
        'file_directory' => 'audio',
      ]);

    $fields['user_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User hash'))
      ->setDescription(t('A hash of user'));

    $fields['collection_delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Delta'))
      ->setDescription(t('Delta'));

    // Standard field, unique outside of the scope of the current project.
    $fields['collection_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Collection UUID'))
      ->setDescription(t('The UUID of a collection of GivDinStemme entities.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
