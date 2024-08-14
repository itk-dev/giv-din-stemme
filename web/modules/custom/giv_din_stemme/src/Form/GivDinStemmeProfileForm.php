<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

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
    $form['grid-wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'grid grid-cols-2 gap-x-6',
      ],
    ];

    $form['grid-wrapper']['top'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'col-span-2',
      ],
    ];

    $form['grid-wrapper']['top']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['grid-wrapper']['col_left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'col-span-1',
      ],
    ];

    $form['grid-wrapper']['col_right'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'col-span-1',
      ],
    ];

    $form['grid-wrapper']['col_left']['zip_code_born'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip code born'),
      '#required' => TRUE,
    ];

    $form['grid-wrapper']['col_left']['zip_code_school'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip code school'),
      '#required' => TRUE,
    ];

    $form['grid-wrapper']['col_left']['dialect'] = [
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

    $form['grid-wrapper']['col_right']['gender'] = [
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => [
        'male' => $this->t('Male'),
        'female' => $this->t('Female'),
        'other' => $this->t('Other'),
      ],
      '#required' => TRUE,
    ];

    $form['grid-wrapper']['col_right']['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#attributes' => [
        'min' => 18,
        'max' => 120,
      ],
      '#required' => TRUE,
    ];

    $form['grid-wrapper']['footer'] = [
      '#type' => 'html_tag',
      '#tag' => 'footer',
      '#attributes' => [
        'class' => 'footer grid bg-gray-100 -mx-5 px-3 py-5 col-span-2 justify-end',
      ],
    ];

    $form['grid-wrapper']['footer']['actions']['#type'] = 'actions';
    $form['grid-wrapper']['footer']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' =>$this->t('Continue'),
      '#button_type' => 'primary',
      '#attributes' => [
        'fa-icon' => 'fa-solid fa-circle-arrow-right',
        'class' => ['btn-default'],
      ],
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
