# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.4 - 2020-03-29

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed `replace` version constraint in composer.json so repository can be used as replacement of `zendframework/zend-mime:^2.7.2`.

## 2.7.3 - 2020-03-06

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/laminas/laminas-mime/pull/10) improves implementation of `Mime::encodeQuotedPrintable()` for big strings by avoiding copying of the whole string in the loop.

## 2.7.2 - 2019-10-16

### Added

- [zendframework/zend-mime#37](https://github.com/zendframework/zend-mime/pull/37) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mime#36](https://github.com/zendframework/zend-mime/pull/36) fixes
  `Laminas\Mime\Decode::splitMessage` to set `Laminas\Mail\Headers`
  instance always for `$headers` parameter. Before, when messages
  without headers was provided, `$headers` was an empty array.

## 2.7.1 - 2018-05-14

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mime#32](https://github.com/zendframework/zend-mime/pull/32) corrects a potential infinite loop when parsing lines consisting of only spaces and dots.

## 2.7.0 - 2017-11-28

### Added

- [zendframework/zend-mime#27](https://github.com/zendframework/zend-mime/pull/27) adds a fluent
  interface to the various setters in `Laminas\Mime\Message`.

- [zendframework/zend-mime#28](https://github.com/zendframework/zend-mime/pull/28) adds support for PHP
  versions 7.1 and 7.2.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-mime#28](https://github.com/zendframework/zend-mime/pull/28) removes support for
  PHP 5.5.

- [zendframework/zend-mime#28](https://github.com/zendframework/zend-mime/pull/28) removes support for
  HHVM.

### Fixed

- [zendframework/zend-mime#26](https://github.com/zendframework/zend-mime/pull/26) ensures commas
  included within list data items are ASCII encoded, ensuring that the items
  will split on commas correctly (instead of splitting within an item).

- [zendframework/zend-mime#30](https://github.com/zendframework/zend-mime/pull/30) fixes how EOL
  characters are detected, to ensure that mail using `\r\n` as an EOL sequence
  (including mail emitted by Cyrus and Dovecot) will be properly parsed.

## 2.6.1 - 2017-01-16

### Added

- [zendframework/zend-mime#22](https://github.com/zendframework/zend-mime/pull/22) adds the ability to
  decode a single-part MIME message via `Laminas\Mime\Message::createFromMessage()`
  by omitting the `$boundary` argument.

### Changes

- [zendframework/zend-mime#14](https://github.com/zendframework/zend-mime/pull/14) adds checks for
  duplicate parts when adding them to a MIME message, and now throws an
  `InvalidArgumentException` when detected.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mime#13](https://github.com/zendframework/zend-mime/pull/13) fixes issues with
  qp-octets produced by Outlook.
- [zendframework/zend-mime#17](https://github.com/zendframework/zend-mime/pull/17) fixes a syntax error
  in how are thrown by `Laminas\Mime\Part::setContent()`.
- [zendframework/zend-mime#18](https://github.com/zendframework/zend-mime/pull/18) fixes how non-ASCII
  header values are encoded, ensuring that it allows the first word to be of
  arbitrary length.

## 2.6.0 - 2016-04-20

### Added

- [zendframework/zend-mime#6](https://github.com/zendframework/zend-mime/pull/6) adds
  `Mime::mimeDetectCharset()`, which can be used to detect the charset
  of a given string (usually a header) according to the rules specified in
  RFC-2047.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.2 - 2016-04-20

### Added

- [zendframework/zend-mime#8](https://github.com/zendframework/zend-mime/pull/8) and
  [zendframework/zend-mime#11](https://github.com/zendframework/zend-mime/pull/11) port documentation
  from the api-tools-documentation repo, and publish it to
  https://docs.laminas.dev/laminas-mime/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mime#2](https://github.com/zendframework/zend-mime/pull/2) fixes
  `Mime::encodeBase64()`'s behavior when presented with lines of invalid
  lengths (not multiples of 4).
- [zendframework/zend-mime#4](https://github.com/zendframework/zend-mime/pull/4) modifies
  `Mime::encodeQuotedPrintable()` to ensure it never creates a header line
  consisting of only a dot (concatenation character), a situation that can break
  parsing by Outlook.
- [zendframework/zend-mime#7](https://github.com/zendframework/zend-mime/pull/7) provides a patch that
  allows parsing MIME parts that have no headers.
- [zendframework/zend-mime#9](https://github.com/zendframework/zend-mime/pull/9) updates the
  dependencies to:
  - allow PHP 5.5+ or PHP 7+ versions.
  - allow laminas-stdlib 2.7+ or 3.0+ verions.
