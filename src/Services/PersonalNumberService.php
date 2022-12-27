<?php

namespace Drupal\happiness_migrate\Services;

use DateTime;
use Personnummer\Personnummer;
use Personnummer\PersonnummerException;

/**
 * Class PersonalNumberService
 *
 * @package Drupal\sagicor_blocks\Services
 */
class PersonalNumberService {

  /**
   * Transform YYMMDDNNNC to YYYYMMDD-NNC
   *
   * @throws \Exception
   */
  public function transform_ssn(string $ssn) {
    $parts = $this->separate_ssn_parts($ssn);
    if ($this->validate_ssn($ssn)) {
      return [
        'status' => TRUE,
        'value' => $this->generate_long_ssn($parts),
      ];
    }
    else {
      return [
        'status' => FALSE,
        'value' => $ssn,
      ];
    }
  }

  /**
   * Separates the SSN parts.
   *
   * @param string $ssn
   *
   * @return array
   *
   */
  public function separate_ssn_parts(string $ssn): array {

    // Extract separator.
    $separator = $this->extract_separator($ssn);

    // Remove separators.
    $ssn = str_replace(['-', '+'], '', $ssn);

    // Extract the year, month, day, serial number, and check digit from the SSN.
    $year = substr($ssn, 0, 2);
    $month = substr($ssn, 2, 2);
    $day = substr($ssn, 4, 2);
    $serial = substr($ssn, 6, 3);
    $check_digit = substr($ssn, 9, 1);

    return [
      'year' => $year,
      'month' => $month,
      'day' => $day,
      'separator' => $separator,
      'serial' => $serial,
      'check_digit' => $check_digit,
    ];
  }

  /**
   * Extract separator("-" or "+") from SSN
   *
   * @param string $ssn
   *
   * @return string
   */
  public function extract_separator(string $ssn): string {
    $separator = substr($ssn, 6, 1);

    if ($separator == '+' || $separator == '-') {
      return $separator;
    }

    return '-';
  }

  /**
   * Validate SSN using Luhn algorithm.
   *
   * @param string $ssn
   *
   * @return bool
   */
  public function validate_ssn(string $ssn): bool {
    // Remove any hyphens or other separators.
    $ssn = str_replace(['-', '+'], '', $ssn);

    $sum = 0;
    $flag = 0;

    for ($i = strlen($ssn) - 1; $i >= 0; $i--) {
      $add = $flag++ & 1 ? $ssn[$i] * 2 : $ssn[$i];
      $sum += $add > 9 ? $add - 9 : $add;
    }

    return $sum % 10 === 0;
  }

  /**
   * Generates a long-form Swedish personal identification number from the parts
   *
   * @param $parts
   *
   * @return string
   *
   * @throws \Exception
   */
  public function generate_long_ssn($parts): string {

    // Create a DateTime object for the current date.
    $current_date = new DateTime();

    $century = substr($current_date->format('Y'), 0, 2);

    $fullYear = $century . $parts['year'];

    // Create a DateTime object for the person's birthdate.
    $birthdate = new DateTime(
      sprintf(
        '%s-%s-%s',
        $fullYear,
        $parts['month'],
        $parts['day'],
      )
    );

    // If person birthdate is greater than current year that means he/she was
    // born on previous century
    if ($birthdate->format('Y-m-d') > $current_date->format('Y-m-d')) {
      $century -= 1;
    }

    // If person age is above 100 then he was born in previous century
    if ($parts['separator'] == '+') {
      $century -= 1;
    }

    // Output: YYYYMMDD-NNNC
    $format = '%1$s%2$s%3$s%4$s%5$s%6$s%7$s';

    return sprintf(
      $format,
      $century,
      $parts['year'],
      $parts['month'],
      $parts['day'],
      $parts['separator'],
      $parts['serial'],
      $parts['check_digit'],
    );
  }


  /**
   * Transform SSN to PIN using third party lib.
   *
   * @param $ssn
   *
   * @return array
   */
  public function transform_personnummer($ssn): array {
    // Validate the personal identification number using the Luhn algorithm.
    try {
      // Personal identification number is valid.
      $personalNumber = new Personnummer($ssn);
      $format = '%1$s%2$s%3$s%4$s%5$s%6$s%7$s';
      $parts = [
        'century' => $personalNumber->century,
        'year' => $personalNumber->year,
        'month' => $personalNumber->month,
        'day' => $personalNumber->day,
        'sep' => $personalNumber->sep,
        'num' => $personalNumber->num,
        'check' => $personalNumber->check,
      ];

      return [
        'status' => TRUE,
        'value' => sprintf(
          $format,
          $parts['century'],
          $parts['year'],
          $parts['month'],
          $parts['day'],
          $parts['sep'],
          $parts['num'],
          $parts['check']
        ),
      ];

    } catch (PersonnummerException $exception) {
      // Personal identification number is invalid.
      return [
        'status' => FALSE,
        'value' => $ssn,
      ];
    }
  }

}
