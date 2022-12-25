<?php

namespace Drupal\happiness_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Personnummer\Personnummer;

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
class TransformValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Log an error if ssn is invalid.
    if (isset($value['value']) && $value['value']) {
      try {
        $personNumber = new Personnummer($value['value']);

      } catch (\Exception $exception) {
        \Drupal::logger('happiness_migrate')->alert($exception->getMessage());
        return $value;
      }
      $format = '%1$s%2$s%3$s%4$s%5$s%6$s%7$s';
      $parts = [
        'century' => $personNumber->century,
        'year' => $personNumber->year,
        'month' => $personNumber->month,
        'day' => $personNumber->day,
        'sep' => $personNumber->sep,
        'num' => $personNumber->num,
        'check' => $personNumber->check,
      ];

      $value['value'] = sprintf(
        $format,
        $parts['century'],
        $parts['year'],
        $parts['month'],
        $parts['day'],
        $parts['sep'],
        $parts['num'],
        $parts['check']
      );
    }

    return $value;
  }

}
