<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\giv_din_stemme\Helper\AudioHelper;
use Drupal\giv_din_stemme\Helper\Helper;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Givdinstemme controller.
 */
class GivDinStemmeController extends ControllerBase {
  private const GIV_DIN_STEMME_AUDIO_FILES_SUBDIRECTORY = '/audio';

  /**
   * Givdinstemme constructor.
   *
   * @param \Drupal\giv_din_stemme\Helper\Helper $helper
   *   The helper.
   * @param \Drupal\giv_din_stemme\Helper\AudioHelper $audioHelper
   *   The audio helper.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The account interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected Helper $helper,
    protected AudioHelper $audioHelper,
    protected Connection $connection,
    protected FileSystemInterface $fileSystem,
    protected Settings $settings,
    protected $entityTypeManager,
    protected OpenIDConnectSessionInterface $session,
    protected $currentUser,
    protected RequestStack $requestStack,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GivDinStemmeController {
    return new static(
      $container->get(Helper::class),
      $container->get('giv_din_stemme.audio_helper'),
      $container->get('database'),
      $container->get('file_system'),
      $container->get('settings'),
      $container->get('entity_type.manager'),
      $container->get('openid_connect.session'),
      $container->get('current_user'),
      $container->get('request_stack'),
    );
  }

  /**
   * Landing page.
   */
  public function landing(Request $request): array {
    return [
      '#theme' => 'landing_page',
      '#values' => $this->helper->getFrontpageValues(),
    ];
  }

  /**
   * Consent page.
   */
  public function consent(Request $request): array {
    return [
      '#theme' => 'consent_page',
    ];
  }

  /**
   * Login through OIDC if consent is given.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function login(Request $request): TrustedRedirectResponse|RedirectResponse {
    // Get login URL.
    $client_name = 'generic';
    $this->session->saveOp('login');
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];
    $plugin = $client->getPlugin();
    $scopes = 'openid profile';
    $response = $plugin->authorize($scopes);
    $url = $response->getTargetUrl();

    // Go to login or front page if no consent.
    if ('consent_given' === $request->get('consent') && isset($url)) {
      return new TrustedRedirectResponse($url);
    }
    else {
      return $this->redirect('giv_din_stemme.landing');
    }
  }

  /**
   * Profile page.
   */
  public function givDinStemmeProfile(Request $request): array {
    return [
      '#theme' => 'giv_din_stemme_profile_form',
    ];
  }

  /**
   * Permissions page.
   */
  public function permissions(Request $request): array {
    return [
      '#theme' => 'permissions_page',
    ];
  }

  /**
   * Test page.
   */
  public function test(Request $request): array {
    return [
      '#theme' => 'test_page',
    ];
  }

  /**
   * Donate page.
   */
  public function donate(Request $request): array {
    return [
      '#theme' => 'donate_page',
    ];
  }

  /**
   * Show.
   */
  public function show(Request $request): array {

    return [
      '#theme' => 'giv_din_stemme',
      '#attached' => [
        'library' => ['giv_din_stemme/giv_din_stemme'],
      ],
    ];
  }

  /**
   * Process.
   */
  public function process(Request $request): Response {
    $privateFileDirectory = $this->settings->get('file_private_path');
    $directory = $privateFileDirectory . self::GIV_DIN_STEMME_AUDIO_FILES_SUBDIRECTORY;
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    foreach ($request->files->all() as /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */ $file) {
      try {
        // Copy audio file to private files.
        // @todo Figure out why $file->getClientOriginalName() is strange.
        $destination = $directory . '/' . $file->getFilename() . '_' . bin2hex(random_bytes(10)) . '.' . $file->guessExtension();
        $this->fileSystem->copy($file->getPathname(), $destination, FileSystemInterface::EXISTS_ERROR);
        $file = File::create([
          'filename' => basename($destination),
          'uri' => 'private://audio/' . basename($destination),
          // Make file permanent.
          'status' => 1,
        ]);
        $file->save();

        // Create submission with references to file and webform submission.
        $this->connection->upsert('giv_din_stemme_submission')
          ->key('job_id')
          ->fields([
            'id' => 1,
            'webform_submission_id' => 1,
            'webform_id' => 1,
            'recording_file_id' => $file->id(),
          ])
          ->execute();
      }
      catch (FileException | EntityStorageException | RandomException | \Exception $e) {
        // @todo LOG THIS SOMEWHERE?
      }
    }

    return new JsonResponse([], Response::HTTP_CREATED);

  }

}
