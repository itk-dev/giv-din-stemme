<?php

namespace Drupal\dokk_export_events\Plugin\rest\resource;

use Drupal\giv_din_stemme\Helper\Helper;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides events endpoint.
 *
 * @RestResource(
 *   id = "export_gds",
 *   label = @Translation("Export gds"),
 *   uri_paths = {
 *     "canonical" = "/giv_din_stemme/export_gds"
 *   }
 * )
 */
final class GivDinStemmeExportGds extends ResourceBase {

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\giv_din_stemme\Helper\Helper $helper
   *   The events export helper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected Helper $helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get(Helper::class),
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   A ModifiedResourceResponse disables caching for this response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get(): ModifiedResourceResponse {
    $gds = $this->helper->getGds();

    return new ModifiedResourceResponse($gds);
  }

}
