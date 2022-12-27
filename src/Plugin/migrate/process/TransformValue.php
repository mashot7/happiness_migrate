<?php

namespace Drupal\happiness_migrate\Plugin\migrate\process;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\happiness_migrate\Services\PersonalNumberService;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "transform_value"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_text:
 *   plugin: transform_value
 *   source: text
 * @endcode
 *
 */
class TransformValue extends ProcessPluginBase implements ContainerFactoryPluginInterface {


  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var \Drupal\happiness_migrate\Services\PersonalNumberService
   */
  protected PersonalNumberService $personalNumberService;

  public function __construct(
    array                         $configuration,
                                  $plugin_id,
                                  $plugin_definition,
    LoggerChannelFactoryInterface $loggerChannel,
    PersonalNumberService $personalNumberService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerChannel->get('happiness_migrate');
    $this->personalNumberService = $personalNumberService;
  }

  public static function create(
    ContainerInterface $container,
    array              $configuration,
                       $plugin_id,
                       $plugin_definition
  ) {
    // Get the logger service from the container
    // Return a new instance of the plugin class
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('happiness_migrate.personal_number_service'),
    );
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Log an error if ssn is invalid.
    if (isset($value['value']) && $value['value']) {
      if ($this->configuration['third_party_lib']) {
        $personalNumber = $this->personalNumberService->transform_personnummer($value['value']);

      }
      else {
        $personalNumber = $this->personalNumberService->transform_ssn($value['value']);
      }


      if ($personalNumber['status']) {
        // Personal identification number is valid
        $value['value'] = $personalNumber['value'];
        return $value;
      }
      else {
        // Personal identification number is invalid
        if ($this->configuration['skip_invalid'] == TRUE) {
          // 1. Throw an exception to skip the node from the migration
          $this->logger->alert(print_r($row->get('nid'), TRUE));

          throw new MigrateException();
        }
        else {
          // 2. Set unpublished destination node
          $message = sprintf(
            'Invalid swedish social security number for node #%s',
            $row->get('nid')
          );
          $this->logger->alert($message);
          $row->setDestinationProperty('status', 0);
          return $value;
        }
      }

    }

    return $value;
  }

}
