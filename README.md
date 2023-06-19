#### Task
In the Drupal 7 site the data is stored as a string in the format \d{10}. This format is a
shorthand version sometimes used. As part of the upgrade to Drupal 9 the client wants to
use the full version instead, \d{8}\-\d{4}.

### Solutio

Happiness Migrate module contains migration process
plugin to transform Sweden Social Security Number(SSN)
to the Personal Identification Number(PIN).

Note: You need to add `personnummer/personnummer`
library using `composer require personnummer/personnummer`.

#### Configuration
`skip_invalid` set true to do not migrate the nodes
with the invalid SSNs.

`third_party_lib` set true to use
`personnummer/personnummer` library for validation and
transformation of the PIN from the SSN.
