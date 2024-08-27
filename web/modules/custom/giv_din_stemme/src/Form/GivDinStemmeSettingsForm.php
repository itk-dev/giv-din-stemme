<?php

namespace Drupal\giv_din_stemme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Giv din stemme settings form.
 *
 * @package Drupal\itk_admin\Form
 */
class GivDinStemmeSettingsForm extends FormBase {

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The object state.
   */
  public function __construct(protected State $state) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GivDinStemmeSettingsForm|static {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'giv_din_stemme_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['general_settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-footer',
    ];

    $form['texts'] = [
      '#title' => $this->t('Texts'),
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => '0',
      '#group' => 'general_settings',
    ];

    $form['texts']['front_page_text'] = [
      '#title' => $this->t('Front page text (Step one)'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.front_page_text'),
      '#weight' => '1',
    ];

    $form['texts']['competition_header_text'] = [
      '#title' => $this->t('Competition header text'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.competition_header_text'),
      '#weight' => '1',
    ];

    $form['texts']['donate_page_text'] = [
      '#title' => $this->t('Donate page text (Step five)'),
      '#type' => 'text_format',
      '#format' => 'simpel_html',
      '#default_value' => $this->state->get('giv_din_stemme.donate_page_text'),
      '#weight' => '1',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save changes'),
      '#weight' => '6',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus('Settings saved');
    $this->state->setMultiple([
      'giv_din_stemme.front_page_text' => $form_state->getValue('front_page_text')['value'],
      'giv_din_stemme.competition_header_text' => $form_state->getValue('competition_header_text')['value'],
      'giv_din_stemme.donate_page_text' => $form_state->getValue('donate_page_text')['value'],
    ]);

    drupal_flush_all_caches();
  }

}
