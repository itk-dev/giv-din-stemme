<?php

namespace Drupal\itk_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin settings form.
 *
 * @package Drupal\itk_admin\Form
 */
class AdminSettingsForm extends FormBase {

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The object state.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $localTaskManager
   *   The local task manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(protected State $state, protected LocalTaskManagerInterface $localTaskManager, protected RendererInterface $renderer) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AdminSettingsForm|static {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('state'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itk_admin_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $site_name = $this->configFactory()->get('system.site')->get('name');
    // Add intro wrapper.
    $form['intro_wrapper'] = [
      '#title' => $this->t('@site_name settings.', ['@site_name' => $site_name]),
      '#type' => 'item',
      '#description' => $this->t('These pages contain @site_name specific config settings.', ['@site_name' => $site_name]),
      '#weight' => '1',
      '#open' => TRUE,
    ];

    $tasks = $this->localTaskManager->getLocalTasksForRoute('itk_admin.settings');
    unset($tasks[0]['itk_admin.admin']);
    $links = '';
    foreach ($tasks[0] as $tab) {
      $link_array = Link::createFromRoute($tab->getTitle(), $tab->getRouteName())->toRenderable();
      $links .= '<div>' . $this->renderer->render($link_array) . '</div>';
    }

    // Add menu wrapper.
    $form['menu_wrapper'] = [
      '#title' => $this->t('Configure'),
      '#type' => 'item',
      '#description' => $links,
      '#weight' => '2',
      '#open' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addMessage('Settings saved');
  }

}
