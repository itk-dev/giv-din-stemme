<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Giv din stemme profile form.
 */
class GivDinStemmeProfileForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'giv_din_stemme_profile_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['zip_code_born'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip code born'),
      '#required' => TRUE,
    ];

    $form['zip_code_school'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip code school'),
      '#required' => TRUE,
    ];

    $form['dialect'] = [
      '#type' => 'select',
      '#title' => $this->t('Dialect'),
      '#options' => [
        'vestjysk' => $this->t('Vestjysk'),
        'oestjysk' => $this->t('Østjysk'),
        'soenderjysk' => $this->t('Sønderjysk'),
        'fynsk' => $this->t('Fynsk'),
        'sjaellandsk' => $this->t('Sjællandsk'),
        'bornholmsk' => $this->t('Bornholmsk'),
        'sydoemaal' => $this->t('Sydømål'),
      ],
      '#required' => TRUE,
    ];

    $form['gender'] = [
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => [
        'male' => $this->t('Male'),
        'female' => $this->t('Female'),
        'other' => $this->t('Other'),
      ],
      '#required' => TRUE,
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('age') < 18) {
      $form_state->setErrorByName('age', $this->t('Age must be greater than 18.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo add the supplied data to the user.
    // Redirect/switch to form for donating voice.
    $form_state->setRedirect('giv_din_stemme.permissions');
  }

}
