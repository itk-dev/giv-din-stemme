<?php

namespace Drupal\giv_din_stemme\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the GivDinStemmeText entity.
 *
 * @ingroup advertiser
 *
 * @ContentEntityType(
 *   id = "giv_din_stemme_text",
 *   label = @Translation("Giv din stemme text"),
 *   base_table = "giv_din_stemme_text",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   links = {
 *     "canonical" = "/giv-din-stemme-text/{giv_din_stemme_text}",
 *     "add-page" = "/giv-din-stemme-text/add",
 *     "add-form" = "/giv-din-stemme-text/add/{giv_din_stemme_text_type}",
 *     "edit-form" = "/giv-din-stemme-text/{giv_din_stemme_text}/edit",
 *     "duplicate-form" = "/giv-din-stemme-text/{giv_din_stemme_text}/duplicate",
 *     "delete-form" = "/giv-din-stemme-text/{giv_din_stemme_text}/delete",
 *     "delete-multiple-form" = "/admin/content/giv-din-stemme-text-items/delete",
 *     "collection" = "/admin/content/giv-din-stemme-text-items",
 *   }
 * )
 */
class GivDinStemmeText extends ContentEntityBase implements ContentEntityInterface {

  /**
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the GivDinStemmeText entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the GivDinStemmeText entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
