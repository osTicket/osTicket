# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.10.1 - 2020-04-21

### Added

- [#81](https://github.com/laminas/laminas-mail/pull/81) adds PHP 7.3 and 7.4 support.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#83](https://github.com/laminas/laminas-mail/pull/83) fixes PHPDocs in `Transport\InMemory` (last message can be `null`).

- [#84](https://github.com/laminas/laminas-mail/pull/84) fixes PHP 7.4 compatibility.

- [#82](https://github.com/laminas/laminas-mail/pull/82) fixes numerous issues in `Storage\Maildir`. This storage adapter was not working before and unit tests were disabled. 

- [#75](https://github.com/laminas/laminas-mail/pull/75) fixes how `Laminas\Mail\Header\ListParser::parse()` parses the string with quotes.

- [#88](https://github.com/laminas/laminas-mail/pull/88) fixes recognising encoding of `Subject` and `GenericHeader` headers.

## 2.10.0 - 2018-06-07

### Added

- [zendframework/zend-mail#213](https://github.com/zendframework/zend-mail/pull/213) re-adds support for PHP 5.6 and 7.0; Laminas policy is never
  to bump the major version of a PHP requirement unless the package is bumping major version.

- [zendframework/zend-mail#172](https://github.com/zendframework/zend-mail/pull/172) adds the flag `connection_time_limit` to the possible `Laminas\Mail\Transport\Smtp` options.
  This flag, when provided as a positive integer, and in conjunction with the `use_complete_quit` flag, will
  reconnect to the server after the specified interval.

- [zendframework/zend-mail#166](https://github.com/zendframework/zend-mail/pull/166) adds functionality for handling `References` and `In-Reply-To` headers.

- [zendframework/zend-mail#148](https://github.com/zendframework/zend-mail/pull/148) adds the optional constructor argument `$comment` and the method `getComment()` to the class
  `Laminas\Mail\Address`. When a comment is present, `toString()` will include it in the representation.

- [zendframework/zend-mail#148](https://github.com/zendframework/zend-mail/pull/148) adds the method `Laminas\Mail\Address::fromString(string $address, $comment = null) : Address`.
  The method can be used to generate an instance from a string containing a `(name)?<email>` value.
  The `$comment` argument can be used to associate a comment with the address.

### Changed

- [zendframework/zend-mail#196](https://github.com/zendframework/zend-mail/pull/196) updates how the `Headers::fromString()` handles header line continuations
  that include a single empty line, ensuring they are concatenated to the
  header value.

- [zendframework/zend-mail#165](https://github.com/zendframework/zend-mail/pull/165) changes the `AbstractAddressList` IDN&lt;-&gt;ASCII conversion; it now no longer requires
  ext-intl, but instead uses a bundled true/punycode library to accomplish it. This also means that
  the conversions will work on any PHP installation.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#211](https://github.com/zendframework/zend-mail/pull/211) fixes how the `ContentType` header class parses the value it receives. Previously,
  it was incorrectly splitting the value on semi-colons that were inside quotes; in now correctly
  ignores them.

- [zendframework/zend-mail#204](https://github.com/zendframework/zend-mail/pull/204) fixes `HeaderWrap::mimeDecodeValue()` behavior when handling a multiline UTF-8
  header split across a character. The fix will only work when ext-imap is present, however.

- [zendframework/zend-mail#164](https://github.com/zendframework/zend-mail/pull/164) fixes the return value from `Laminas\Mail\Protocol\Imap::capability()` when no response is
  returned from the server; previously, it returned `false`, but now correctly returns an empty array.

- [zendframework/zend-mail#148](https://github.com/zendframework/zend-mail/pull/148) fixes how `Laminas\Mail\Header\AbstractAddressList` parses address values, ensuring
  that they now retain any address comment discovered to include in the generated `Laminas\Mail\Address` instances.

- [zendframework/zend-mail#147](https://github.com/zendframework/zend-mail/pull/147) fixes how address lists are parsed, expanding the functionality to allow either
  `,` or `;` delimiters (or both in combination).

## 2.9.0 - 2017-03-01

### Added

- [zendframework/zend-mail#177](https://github.com/zendframework/zend-mail/issues/177)
  [zendframework/zend-mail#181](https://github.com/zendframework/zend-mail/pull/181)
  [zendframework/zend-mail#192](https://github.com/zendframework/zend-mail/pull/192)
  [zendframework/zend-mail#189](https://github.com/zendframework/zend-mail/pull/189) PHP 7.2 support
- [zendframework/zend-mail#73](https://github.com/zendframework/zend-mail/issues/73)
  [zendframework/zend-mail#160](https://github.com/zendframework/zend-mail/pull/160) Support for
  mails that don't have a `To`, as long as `Cc` or `Bcc` are set.
- [zendframework/zend-mail#161](https://github.com/zendframework/zend-mail/issues/161) removed
  useless try-catch that just re-throws.
- [zendframework/zend-mail#134](https://github.com/zendframework/zend-mail/issues/134) simplified
  checks for the existence of some string sub-sequences, which were
  needlessly performed via regular expressions

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#188](https://github.com/zendframework/zend-mail/pull/188) split strings
  before calling `iconv_mime_decode()`, which destroys newlines, rendering
  DKIM parsing useless.
- [zendframework/zend-mail#156](https://github.com/zendframework/zend-mail/pull/156) fixed a
  regression in which `<` and `>` would appear doubled in message
  identifiers.
- [zendframework/zend-mail#143](https://github.com/zendframework/zend-mail/pull/143) fixed parsing
  of `<` and `>` being part of the email address comment.

## 2.8.0 - 2017-06-08

### Added

- [zendframework/zend-mail#117](https://github.com/zendframework/zend-mail/pull/117) adds support
  configuring whether or not an SMTP transport should issue a `QUIT` at
  `__destruct()` and/or end of script execution. Use the `use_complete_quit`
  configuration flag and/or the `setuseCompleteQuit($flag)` method to change
  the setting (default is to enable this behavior, which was the previous
  behavior).
- [zendframework/zend-mail#128](https://github.com/zendframework/zend-mail/pull/128) adds a
  requirement on ext/iconv, as it is used internally.
- [zendframework/zend-mail#132](https://github.com/zendframework/zend-mail/pull/132) bumps minimum
  php version to 5.6
- [zendframework/zend-mail#144](https://github.com/zendframework/zend-mail/pull/144) adds support
  for TLS versions 1.1 and 1.2 for all protocols supporting TLS operations.

### Changed

- [zendframework/zend-mail#140](https://github.com/zendframework/zend-mail/pull/140) updates the
  `Sendmail` transport such that `From` and `Sender` addresses are passed to
  `escapeshellarg()` when forming the `-f` argument for the `sendmail` binary.
  While malformed addresses should never reach this class, this extra hardening
  helps ensure safety in cases where a developer codes their own
  `AddressInterface` implementations for these types of addresses.
- [zendframework/zend-mail#141](https://github.com/zendframework/zend-mail/pull/141) updates
  `Laminas\Mail\Message::getHeaders()` to throw an exception in a case where the
  `$headers` property is not a `Headers` instance.
- [zendframework/zend-mail#150](https://github.com/zendframework/zend-mail/pull/150) updates the
  `Smtp` protocol to allow an empty or `none` value for the SSL configuration
  value.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#151](https://github.com/zendframework/zend-mail/pull/151) fixes a condition
  in the `Sendmail` transport whereby CLI parameters were not properly trimmed.

## 2.7.3 - 2017-02-14

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#93](https://github.com/zendframework/zend-mail/pull/93) fixes a situation
  whereby `getSender()` was unintentionally creating a blank `Sender` header,
  instead of returning `null` if none exists, fixing an issue in the SMTP
  transport.
- [zendframework/zend-mail#105](https://github.com/zendframework/zend-mail/pull/105) fixes the header
  implementation to allow zero (`0`) values for header values.
- [zendframework/zend-mail#116](https://github.com/zendframework/zend-mail/pull/116) fixes how the
  `AbstractProtocol` handles `stream_socket_client()` errors, ensuring an
  exception is thrown with detailed information regarding the failure.

## 2.7.2 - 2016-12-19

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixes [ZF2016-04](https://framework.zend.com/security/advisory/ZF2016-04).

## 2.7.1 - 2016-05-09

### Added

- [zendframework/zend-mail#38](https://github.com/zendframework/zend-mail/pull/38) adds support in the
  IMAP protocol adapter for fetching items by UID.
- [zendframework/zend-mail#88](https://github.com/zendframework/zend-mail/pull/88) adds and publishes
  documentation to https://docs.laminas.dev/laminas-mail/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#9](https://github.com/zendframework/zend-mail/pull/9) fixes the
  `Laminas\Mail\Header\Sender::fromString()` implementation to more closely follow
  the ABNF defined in RFC-5322, specifically to allow addresses in the form
  `user@domain` (with no TLD).
- [zendframework/zend-mail#28](https://github.com/zendframework/zend-mail/pull/28) and
  [zendframework/zend-mail#87](https://github.com/zendframework/zend-mail/pull/87) fix header value
  validation when headers wrap using the sequence `\r\n\t`; prior to this
  release, such sequences incorrectly marked a header value invalid.
- [zendframework/zend-mail#37](https://github.com/zendframework/zend-mail/pull/37) ensures that empty
  lines do not result in PHP errors when consuming messages from a Courier IMAP
  server.
- [zendframework/zend-mail#81](https://github.com/zendframework/zend-mail/pull/81) fixes the validation
  in `Laminas\Mail\Address` to also DNS hostnames as well as local addresses.

## 2.7.0 - 2016-04-11

### Added

- [zendframework/zend-mail#41](https://github.com/zendframework/zend-mail/pull/41) adds support for
  IMAP delimiters in the IMAP storage adapter.
- [zendframework/zend-mail#80](https://github.com/zendframework/zend-mail/pull/80) adds:
  - `Laminas\Mail\Protocol\SmtpPluginManagerFactory`, for creating and returning an
    `SmtpPluginManagerFactory` instance.
  - `Laminas\Mail\ConfigProvider`, which maps the `SmtpPluginManager` to the above
    factory.
  - `Laminas\Mail\Module`, which does the same, for laminas-mvc contexts.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.2 - 2016-04-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#44](https://github.com/zendframework/zend-mail/pull/44) fixes an issue with
  decoding of addresses where the full name contains a comma (e.g., "Lastname,
  Firstname").
- [zendframework/zend-mail#45](https://github.com/zendframework/zend-mail/pull/45) ensures that the
  message parser allows deserializing message bodies containing multiple EOL
  sequences.
- [zendframework/zend-mail#78](https://github.com/zendframework/zend-mail/pull/78) fixes the logic of
  `HeaderWrap::canBeEncoded()` to ensure it returns correctly for header lines
  containing at least one multibyte character, and particularly when that
  character falls at specific locations (per a
  [reported bug at php.net](https://bugs.php.net/bug.php?id=53891)).

## 2.6.1 - 2016-02-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#72](https://github.com/zendframework/zend-mail/pull/72) re-implements
  `SmtpPluginManager` as a laminas-servicemanager `AbstractPluginManager`, after
  reports that making it standalone broke important extensibility use cases
  (specifically, replacing existing plugins and/or providing additional plugins
  could only be managed with significant code changes).

## 2.6.0 - 2016-02-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#47](https://github.com/zendframework/zend-mail/pull/47) updates the
  component to remove the (soft) dependency on laminas-servicemanager, by
  altering the `SmtpPluginManager` to implement container-interop's
  `ContainerInterface` instead of extending from `AbstractPluginManager`.
  Usage remains the same, though developers who were adding services
  to the plugin manager will need to instead extend it now.
- [zendframework/zend-mail#70](https://github.com/zendframework/zend-mail/pull/70) updates dependencies
  to stable, forwards-compatible versions, and removes unused dependencies.

## 2.5.2 - 2015-09-10

### Added

- [zendframework/zend-mail#12](https://github.com/zendframework/zend-mail/pull/12) adds support for
  simple comments in address lists.
- [zendframework/zend-mail#13](https://github.com/zendframework/zend-mail/pull/13) adds support for
  groups in address lists.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-mail#26](https://github.com/zendframework/zend-mail/pull/26) fixes the
  `ContentType` header to properly handle parameters with encoded values.
- [zendframework/zend-mail#11](https://github.com/zendframework/zend-mail/pull/11) fixes the
  behavior of the `Sender` header, ensuring it can handle domains that do not
  contain a TLD, as well as addresses referencing mailboxes (no domain).
- [zendframework/zend-mail#24](https://github.com/zendframework/zend-mail/pull/24) fixes parsing of
  mail messages that contain an initial blank line (prior to the headers), a
  situation observed in particular with GMail.
