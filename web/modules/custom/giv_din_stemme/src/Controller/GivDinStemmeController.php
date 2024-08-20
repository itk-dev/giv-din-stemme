<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DefaultContent\InvalidEntityException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\file\Entity\File;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drupal\giv_din_stemme\Helper\AudioHelper;
use Drupal\node\Entity\Node;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Random\RandomException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * UserBooking controller.
 */
class GivDinStemmeController extends ControllerBase {

  private const GIV_DIN_STEMME_AUDIO_FILES_SUBDIRECTORY = '/audio';

  private const NEEDED_PARAMS = [
    'text',
    'reading',
    'total',
  ];

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
  protected $session;

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
   */
  public function __construct(
    AudioHelper $audioHelper,
    Connection $connection,
    FileSystemInterface $fileSystem,
    Settings $settings,
    EntityTypeManagerInterface $entity_type_manager,
    OpenIDConnectSessionInterface $session
  ) {
    $this->audioHelper = $audioHelper;
    $this->connection = $connection;
    $this->fileSystem = $fileSystem;
    $this->settings = $settings;
    $this->entityTypeManager = $entity_type_manager;
    $this->session = $session;
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
      $container->get('openid_connect.session')
    );
  }

  /**
   * Landing page.
   */
  public function landing(Request $request): array {

    return [
      '#theme' => 'landing_page',
      '#name' => $this->t('Landing Page'),
//      '#login_url' => 'login url',
//      '#logout_url' => 'logout url',
//      '#attached' => [
//        'library' => ['giv_din_stemme/giv_din_stemme'],
//      ],
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

  public function login(Request $request): array {
    $client_name = 'connection';
    $this->session->saveOp('login');
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];
    $plugin = $client->getPlugin();
    $scopes = 'openid profile';
    $response = $plugin->authorize($scopes);

    $url = $response->getTargetUrl();
    return [
      '#theme' => 'oidc_login_page',
      '#url' => $response->getTargetUrl() ?? NULL
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
   * @throws EntityStorageException
   * @throws MissingDataException
   */
  public function startDonating(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
  {
    $text = $this->getRandomText();

    // TODO: use injection
    $uuidService = \Drupal::service('uuid');
    $collectionId = $uuidService->generate();
    $delta = 0;

    $parts = $text->get('field_text_parts');

    $numberOfParts = count($parts);

    // TODO: How do we handle this?
    if ($numberOfParts < 1) {
      throw new \Exception('A text should contain at least one part');
    }

    // Create a GivDinStemme entity per text part.
    foreach($parts as $part) {
      $entity = GivDinStemme::create();

      $entity->set('collection_id', $collectionId);
      $entity->set('collection_delta', $delta++);
      // TODO: Use unique id from user possibly also hashed
      $entity->set('user_hash', md5($this->currentUser()->getAccountName()));

      // Save these on entity as metadata.
      $partTextToRead = $part->getValue()['value'];
      $textId = $text->id();
      // TODO: Use unique id from user possibly also hashed
      $accountName = $this->currentUser()->getAccountName();

      $entity->set('metadata', json_encode([
        'text' => $partTextToRead,
        'text_id' => $textId,
        'account_name' => $accountName,
        'number_of_parts' => $numberOfParts,
      ]));

      $entity->save();
    }

    return $this->redirect('giv_din_stemme.read', [
      'collection_id' => $collectionId,
      'delta' => 0,
    ]);

  }

  /**
   * @throws \Exception
   */
  public function read(Request $request, string $collection_id, string $delta): array|Response
  {
    if ('POST' === $request->getMethod()) {
      return $this->handlePost($request, $collection_id, $delta);
    } else {
      return $this->handleGet($request, $collection_id, $delta);
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws \Exception
   */
  private function getGivDinStemmeByCollectionUuidAndDelta(string $uuid, string $delta): ?GivDinStemme {
    $result = $this->entityTypeManager->getStorage('gds')->loadByProperties([
      'collection_id' => $uuid,
      'collection_delta' => $delta,
    ]);

    if (1 !== count($result)) {
      throw new \Exception('Unique GivDinStemme not found');
    }

    return reset($result);
  }

  private function getRandomText(): Node {

    $nids = \Drupal::entityQuery('node')->condition('type','text')->accessCheck(FALSE)->execute();
    $nodes =  Node::loadMultiple($nids);

    $count  = count($nodes);
    $keys = array_keys($nodes);

    $randomKey = $keys[rand(0, $count - 1)];

    return $nodes[$randomKey];
  }

  public function thankYou(Request $request): array {
    return [
      '#theme' => 'thank_you_page',
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

    $collectionId = $request->get('collection_id');
    $delta = $request->get('delta');

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

        // Load GivDinStemme and attach file.
        $givDinStemme = $this->getGivDinStemmeByCollectionUuidAndDelta($collectionId, $delta);
        $givDinStemme->set('file', $file);
        $givDinStemme->save();
      }
      catch (FileException | EntityStorageException | RandomException | \Exception $e) {
        // @todo LOG THIS SOMEWHERE?
      }
    }

    return new JsonResponse([], Response::HTTP_CREATED);
  }

  private function handleGet(Request $request, string $collection_id, string $delta): array
  {
    $collectionId = $request->get('collection_id');
    $delta = $request->get('delta');

    $givDinStemme = $this->getGivDinStemmeByCollectionUuidAndDelta($collectionId, $delta);

    if (!$givDinStemme) {
      throw new \Exception('Invalid collection id and delta provided');
    }

    $metadata = json_decode($givDinStemme->get('metadata')->getValue()[0]['value'], TRUE);
    $textToRead = $metadata['text'];
    $count = $metadata['number_of_parts'];
    $nextReading = $delta + 1;
    $isDone = $nextReading === $count;

    return [
      '#theme' => 'read_page',
      '#textToRead' => $textToRead,
      '#totalTexts' => $count,
      '#nextUrl' => $isDone ? '/thank-you' : '/read/'. $collectionId . '/' . $nextReading ,
      '#hasNext' => !$isDone,
      '#attached' => [
        'library' => ['giv_din_stemme/giv_din_stemme'],
      ],
    ];
  }

  private function handlePost(Request $request, string $collection_id, string $delta): JsonResponse
  {
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

        // Load GivDinStemme and attach file.
        $givDinStemme = $this->getGivDinStemmeByCollectionUuidAndDelta($collection_id, $delta);
        $givDinStemme->set('file', $file);
        $givDinStemme->save();
      }
      catch (FileException | EntityStorageException | RandomException | \Exception $e) {
        // @todo LOG THIS SOMEWHERE?
      }
    }

    return new JsonResponse([], Response::HTTP_CREATED);
  }

}
