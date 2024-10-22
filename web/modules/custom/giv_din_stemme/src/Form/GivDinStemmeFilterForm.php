<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter form.
 */
class GivDinStemmeFilterForm extends FormBase {

  public const IS_VALIDATED = 'is_validated';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'giv_din_stemme_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ];

    $form['filter']['is_validated'] = [
      '#type' => 'select',
      '#title' => $this->t('Is validated'),
      '#options' => [
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
      ],
      '#empty_option' => $this->t('All'),
      '#default_value' => $request->query->get(self::IS_VALIDATED),
    ];

    $form['actions']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-item']],
    ];

    $form['actions']['wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Filter',
    ];

    if ($request->getQueryString()) {
      $form['actions']['wrapper']['reset'] = [
        '#type' => 'submit',
        '#value' => 'Reset',
        '#submit' => ['::resetForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];

    $isValidated = $form_state->getValue(self::IS_VALIDATED) ?? NULL;
    if (NULL !== $isValidated) {
      $query[self::IS_VALIDATED] = $isValidated;
    }

    $form_state->setRedirect('entity.gds.collection', $query);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.gds.collection');
  }

}
