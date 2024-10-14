<?php

namespace Drupal\giv_din_stemme\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\giv_din_stemme\Entity\GivDinStemme;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;

/**
 * GivDinStemme commandfile.
 */
final class GivDinStemmeCommands extends DrushCommands {

  /**
   * Whisper API endpoint.
   *
   * @var string
   */
  private string $whisperApiEndpoint;

  /**
   * Whisper API key.
   *
   * @var string
   */
  private string $whisperApiKey;

  /**
   * Automatic validation threshold.
   *
   * @var int
   */
  private int $automaticValidationThreshold;

  use AutowireTrait;

  /**
   * Constructs a GivDinStemmeCommands object.
   */
  public function __construct(
    private readonly ClientInterface $client,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
    $this->whisperApiEndpoint = Settings::get('itkdev_whisper_api_endpoint', 'https://whisper.itkdev.dk/');
    $this->whisperApiKey = Settings::get('itkdev_whisper_api_key', 'SOME_API_KEY');
    $this->automaticValidationThreshold = (int) Settings::get('itkdev_automatic_validation_threshold', 90);
  }

  /**
   * Qualify all unqualified donations.
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:all')]
  #[CLI\Option(name: 're-qualify', description: 'Re-qualify donations')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:all', description: 'Qualify all donations')]
  public function givDinStemmeQualify($options = ['re-qualify' => FALSE]): void {

    // Construct query for fetching gds/donations that should be qualified.
    $query =
<<<SQL
    SELECT
        id
    FROM
        gds
    WHERE
        file__target_id IS NOT NULL
SQL;

    // If metadata contains whisper_guess it has already been qualified.
    !$options['re-qualify'] ? $query .= ' AND metadata NOT LIKE \'%whisper_guess%\';' : $query .= ';';

    // Query donations for qualifying.
    $donations = $this->connection->query($query);

    $donationIds = array_map(static fn ($unqualifiedDonation) => $unqualifiedDonation->id, $donations->fetchAllAssoc('id'));

    if (empty($donationIds)) {
      $this->io()->success('No donations for qualification detected');
      return;
    }

    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme[] $gdsEntities */
    $gdsEntities = $this->entityTypeManager->getStorage('gds')->loadByProperties(['id' => array_values($donationIds)]);

    $numberOfGds = count($gdsEntities);

    $this->io()->writeln('Number of donations being handled:' . $numberOfGds);

    $counter = 1;

    foreach ($gdsEntities as $gds) {
      $this->io()->writeln('Handling donation ' . $counter . ' of ' . $numberOfGds);

      $this->qualifyGivDinStemme($gds);

      $counter++;
    }

    $this->io->success('Finished qualifying donations');
  }

  /**
   * Qualify donation by id.
   */
  #[CLI\Command(name: 'giv-din-stemme:qualify:donation')]
  #[CLI\Usage(name: 'giv-din-stemme:qualify:donation', description: 'Qualify donation by id')]
  public function qualifyById($id = 1): void {

    /** @var \Drupal\giv_din_stemme\Entity\GivDinStemme[] $donations */
    $donations = $this->entityTypeManager->getStorage('gds')->loadByProperties(['id' => $id]);

    if (empty($donations)) {
      $this->io()->error(sprintf('No donation with id %d found.', $id));
      return;
    }

    $this->io()->writeln('Qualifying donation with id: ' . $id);

    foreach ($donations as $donation) {

      if (!$donation->getFile()) {
        $this->io()->error(sprintf('Donation with id %d does not have an attached donation file.', $id));
        return;
      }

      $this->qualifyGivDinStemme($donation);
    }

    $this->io->success('Finished qualifying donations');
  }

  /**
   * Qualify GivDinStemme donation.
   */
  private function qualifyGivDinStemme(GivDinStemme $gds): void {
    $realpath = $this->fileSystem->realpath($gds->getFile()->getFileUri());

    $headers = [
      'x-api-key' => $this->whisperApiKey,
      'Accept' => 'application/json',
    ];

    $options = [
      'multipart' => [
        [
          'name' => 'audio_file',
          'contents' => Utils::tryFopen($realpath, 'r'),
          'filename'  => $gds->getFile()->getFilename(),
        ],
      ],
    ];

    $request = new Request('POST', $this->whisperApiEndpoint, $headers);

    /** @var \GuzzleHttp\Psr7\Response $response */
    $response = $this->client->sendAsync($request, $options)->wait();

    // Whisper tends to prefix transcribed text with a space.
    $whisperGuess = trim(json_decode($response->getBody()->getContents(), TRUE)['text']);

    $metadata = $gds->getMetadata();

    $originalText = $metadata['text'];

    similar_text($originalText, $whisperGuess, $percent);

    $metadata['whisper_guess'] = $whisperGuess;
    $metadata['whisper_guess_similar_text_score'] = (string) $percent;

    $gds->setMetadata($metadata);

    // If similar_text score is considered good enough
    // and donation is not validated, validate it.
    if ((int) $percent >= $this->automaticValidationThreshold && !$gds->getValidatedTime()) {
      $gds->setValidatedTime((new \DateTimeImmutable())->getTimestamp());
    }

    $gds->save();
  }

}
