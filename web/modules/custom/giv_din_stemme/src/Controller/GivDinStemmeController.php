<?php

namespace Drupal\giv_din_stemme\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\file\Entity\File;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drupal\giv_din_stemme\Helper\AudioHelper;
use Drupal\node\Entity\Node;
use Drupal\giv_din_stemme\Helper\Helper;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Givdinstemme controller.
 */
class GivDinStemmeController extends ControllerBase {
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
   *   The account interface
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param
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
    protected RequestStack $requestStack
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
      '#values' => $this->helper->getFrontpageValues()
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
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
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


  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getCountOfGivDinStemmeByCollectionUuid(string $uuid): int {
    $result = $this->entityTypeManager->getStorage('gds')->loadByProperties([
      'collection_id' => $uuid,
    ]);

    return count($result);
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

    return [
      '#theme' => 'read_page',
      '#textToRead' => $textToRead,
      '#totalTexts' => $count,
      '#nextUrl' => '/read/'. $collectionId . '/' . $delta ,
      '#attached' => [
        'library' => ['giv_din_stemme/giv_din_stemme'],
      ],
    ];
  }

  private function handlePost(Request $request, string $collection_id, string $delta): RedirectResponse
  {
    $directory = 'private://audio/';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    foreach ($request->files->all() as /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */ $file) {
      try {
        // Copy audio file to private files.
        $destination = $directory . '/' . $file->getClientOriginalName();
        $newFilename = $this->fileSystem->copy($file->getPathname(), $destination, FileExists::Rename);
        $file = File::create([
          'filename' => basename($newFilename),
          'uri' => $directory . basename($newFilename),
          // Make file permanent.
          'status' => 1,
        ]);
        $file->save();

        // Load GivDinStemme and attach file.
        $givDinStemme = $this->getGivDinStemmeByCollectionUuidAndDelta($collection_id, $delta);
        $givDinStemme->set('file', $file);
        $givDinStemme->save();
      }
      catch (FileException | EntityStorageException |\Exception $e) {
        // @todo LOG THIS SOMEWHERE?
      }
    }


    // Redirect based on whether another text part exists.
    $nextDelta = ((int) $delta) + 1;
    $countOfParts = $this->getCountOfGivDinStemmeByCollectionUuid($collection_id);


    if ($nextDelta < $countOfParts) {
      return $this->redirect('giv_din_stemme.read', [
        'collection_id' => $collection_id,
        'delta' => (string) $nextDelta,
      ]);
    } else {
      return $this->redirect('giv_din_stemme.thank_you');
    }
  }

}
