<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\giv_din_stemme\Helper\AudioHelper;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * UserBooking controller.
 */
class GivDinStemmeController extends ControllerBase {
  private const GIV_DIN_STEMME_AUDIO_FILES_SUBDIRECTORY = '/audio';

  /**
   * The audio helper.
   *
   * @var \Drupal\giv_din_stemme\Helper\AudioHelper
   */
  protected AudioHelper $audioHelper;


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The audio helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected OpenIDConnectSessionInterface $session;

  /**
   * The account interface
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $current_user;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * UserBookingsController constructor.
   *
   * @param \Drupal\giv_din_stemme\Helper\AudioHelper $audioHelper
   *   The audio helper.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Site\Settings $settings
   *   Settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The account interface
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    AudioHelper $audioHelper,
    Connection $connection,
    FileSystemInterface $fileSystem,
    Settings $settings,
    EntityTypeManagerInterface $entity_type_manager,
    OpenIDConnectSessionInterface $session,
    AccountInterface $current_user,
    RequestStack $requestStack
  ) {
    $this->audioHelper = $audioHelper;
    $this->connection = $connection;
    $this->fileSystem = $fileSystem;
    $this->settings = $settings;
    $this->entityTypeManager = $entity_type_manager;
    $this->session = $session;
    $this->currentUser = $current_user;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GivDinStemmeController {
    return new static(
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
      '#name' => $this->t('Landing Page'),
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

  public function login(Request $request): TrustedRedirectResponse|RedirectResponse {
    $client_name = 'connection';
    $this->session->saveOp('login');
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];
    $plugin = $client->getPlugin();
    $scopes = 'openid profile';
    $response = $plugin->authorize($scopes);

    $url = $response->getTargetUrl();
    if (isset($_REQUEST['consent']) && $_REQUEST['consent'] == 'consent_given' && isset($url)) {
      return new TrustedRedirectResponse($url);
    }
    else {
      return $this->redirect('giv_din_stemme.landing');
    }
  }

  public function givDinStemmeProfile(Request $request): array {
    return [
      '#theme' => 'giv_din_stemme_profile_form',
    ];
  }

  public function permissions(Request $request): array {
    return [
      '#theme' => 'permissions_page',
    ];
  }

  public function test(Request $request): array {

    return [
      '#theme' => 'test_page',
    ];
  }

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
    // '#name' => 'name',
    //      '#login_url' => 'loigin ur',
    //      '#logout_url' => 'logout url',
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
