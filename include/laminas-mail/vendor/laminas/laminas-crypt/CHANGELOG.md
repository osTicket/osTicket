# Changelog

All notable changes to this project will be documented in this file, in reverse
chronological order by release.

## 3.3.1 - 2019-05-14

### Added

- [zendframework/zend-crypt#60](https://github.com/zendframework/zend-crypt/pull/60) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.3.0 - 2018-04-24

### Added

- [zendframework/zend-crypt#52](https://github.com/zendframework/zend-crypt/pull/52) adds support for PHP 7.2.

### Changed

- [zendframework/zend-crypt#55](https://github.com/zendframework/zend-crypt/pull/55) updates `Laminas\Crypt\Hmac` to use `hash_hmac_algos` instead of `hmac_algos`
  when it is present.

- [zendframework/zend-crypt#50](https://github.com/zendframework/zend-crypt/pull/50) updates all classes to import functions and constants they use.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.1 - 2017-07-17

### Added

- [zendframework/zend-crypt#42](https://github.com/zendframework/zend-crypt/pull/42) Added the CTR mode
  for OpenSSL.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-crypt#48](https://github.com/zendframework/zend-crypt/pull/48) Incorrect Rsa type
  declaration in Hybrid constructor.


## 3.2.0 - 2016-12-06

### Added

- [zendframework/zend-crypt#38](https://github.com/zendframework/zend-crypt/pull/38) Support of GCM and
  CCM encryption mode for OpenSSL with PHP 7.1+

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.1.0 - 2016-08-11

### Added

- [zendframework/zend-crypt#32](https://github.com/zendframework/zend-crypt/pull/32) adds a new Hybrid
  encryption utility, to allow OpenPGP-like encryption/decryption of messages
  using OpenSSL. See the documentation for details.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.0 - 2016-06-21

### Added

- [zendframework/zend-crypt#22](https://github.com/zendframework/zend-crypt/pull/22) adds a requirement
  on `ext/mbstring` in order to install successfully.
- [zendframework/zend-crypt#25](https://github.com/zendframework/zend-crypt/pull/25) adds a new
  symmetric encryption adapter for the OpenSSL extension; this is now the
  default adapter used internally by the component when symmetric encryption is
  required.
- [zendframework/zend-crypt#25](https://github.com/zendframework/zend-crypt/pull/25) adds support for
  laminas-math v3.
- [zendframework/zend-crypt#26](https://github.com/zendframework/zend-crypt/pull/26) adds
  `Laminas\Crypt\Password\Bcrypt::benchmarkCost()`, which allows you to find the
  maximum cost value possible for your hardware within a 50ms timeframe.
- [zendframework/zend-crypt#11](https://github.com/zendframework/zend-crypt/pull/11) adds a new option
  to the `Laminas\Crypt\PublicKey\RsaOptions` class, `openssl_padding` (or
  `setOpensslPadding()`; this is now consumed in
  `Laminas\Crypt\PublicKey\Rsa::encrypt()` and
  `Laminas\Crypt\PublicKey\Rsa::decrypt()`, instead of the optional `$padding`
  argument.

### Deprecated

- [zendframework/zend-crypt#25](https://github.com/zendframework/zend-crypt/pull/25) deprecates usage of the
  mcrypt symmetric encryption adapter when used on PHP 7 versions, as PHP 7.1
  will deprecate the mcrypt extension.

### Removed

- [zendframework/zend-crypt#11](https://github.com/zendframework/zend-crypt/pull/11) removes the
  optional `$padding` argument from each of `Laminas\Crypt\PublicKey\Rsa`'s
  `encrypt()` and `decrypt()` methods; you can now specify the value via the
  `RsaOptions`.
- [zendframework/zend-crypt#25](https://github.com/zendframework/zend-crypt/pull/25) removes support for
  laminas-math v2 versions.
- [zendframework/zend-crypt#29](https://github.com/zendframework/zend-crypt/pull/29) removes support for
  PHP 5.5.

### Fixed

- [zendframework/zend-crypt#22](https://github.com/zendframework/zend-crypt/pull/22) updates all
  occurrences of `substr()` and `strlen()` to use `mb_substr()` and
  `mb_strlen()`, respectively. This provides better security with binary values.
- [zendframework/zend-crypt#25](https://github.com/zendframework/zend-crypt/pull/25) updates the
  `Laminas\Crypt\Password\Bcrypt` implementation to use `password_hash()` and
  `password_verify()` internally, as they are supported in all PHP versions we
  support.
- [zendframework/zend-crypt#19](https://github.com/zendframework/zend-crypt/pull/19) fixes the
  `DiffieHellman` publickey implementation to initialize the `BigInteger`
  adapter from laminas-math as the first operation of its constructor, fixing a
  fatal error that occurs when binary data is provided.

## 2.6.0 - 2016-02-03

### Added

- [zendframework/zend-crypt#18](https://github.com/zendframework/zend-crypt/pull/18) adds documentation,
  and publishes it to https://docs.laminas.dev/laminas-crypt/

### Deprecated

- Nothing.

### Removed

- Removes the (development) dependency on laminas-config; tests that used it
  previously have been updated to use `ArrayObject`, which implements the same
  behavior being tested.

### Fixed

- [zendframework/zend-crypt#4](https://github.com/zendframework/zend-crypt/pull/4) replaces
  the laminas-servicemanager with container-interop, and refactors the
  various plugin managers to implement that interface instead of extending the
  `AbstractPluginManager`.

## 2.5.2 - 2015-11-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- **ZF2015-10**: `Laminas\Crypt\PublicKey\Rsa\PublicKey` has a call to `openssl_public_encrypt()`
  which used PHP's default `$padding` argument, which specifies
  `OPENSSL_PKCS1_PADDING`, indicating usage of PKCS1v1.5 padding. This padding
  has a known vulnerability, the
  [Bleichenbacher's chosen-ciphertext attack](http://crypto.stackexchange.com/questions/12688/can-you-explain-bleichenbachers-cca-attack-on-pkcs1-v1-5),
  which can be used to recover an RSA private key. This release contains a patch
  that changes the padding argument to use `OPENSSL_PKCS1_OAEP_PADDING`.

  Users upgrading to this version may have issues decrypting previously stored
  values, due to the change in padding. If this occurs, you can pass the
  constant `OPENSSL_PKCS1_PADDING` to a new `$padding` argument in
  `Laminas\Crypt\PublicKey\Rsa::encrypt()` and `decrypt()` (though typically this
  should only apply to the latter):

  ```php
  $decrypted = $rsa->decrypt($data, $key, $mode, OPENSSL_PKCS1_PADDING);
  ```

  where `$rsa` is an instance of `Laminas\Crypt\PublicKey\Rsa`.

  (The `$key` and `$mode` argument defaults are `null` and
  `Laminas\Crypt\PublicKey\Rsa::MODE_AUTO`, if you were not using them previously.)

  We recommend re-encrypting any such values using the new defaults.

## 2.4.9 - 2015-11-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- **ZF2015-10**: `Laminas\Crypt\PublicKey\Rsa\PublicKey` has a call to `openssl_public_encrypt()`
  which used PHP's default `$padding` argument, which specifies
  `OPENSSL_PKCS1_PADDING`, indicating usage of PKCS1v1.5 padding. This padding
  has a known vulnerability, the
  [Bleichenbacher's chosen-ciphertext attack](http://crypto.stackexchange.com/questions/12688/can-you-explain-bleichenbachers-cca-attack-on-pkcs1-v1-5),
  which can be used to recover an RSA private key. This release contains a patch
  that changes the padding argument to use `OPENSSL_PKCS1_OAEP_PADDING`.

  Users upgrading to this version may have issues decrypting previously stored
  values, due to the change in padding. If this occurs, you can pass the
  constant `OPENSSL_PKCS1_PADDING` to a new `$padding` argument in
  `Laminas\Crypt\PublicKey\Rsa::encrypt()` and `decrypt()` (though typically this
  should only apply to the latter):

  ```php
  $decrypted = $rsa->decrypt($data, $key, $mode, OPENSSL_PKCS1_PADDING);
  ```

  where `$rsa` is an instance of `Laminas\Crypt\PublicKey\Rsa`.

  (The `$key` and `$mode` argument defaults are `null` and
  `Laminas\Crypt\PublicKey\Rsa::MODE_AUTO`, if you were not using them previously.)

  We recommend re-encrypting any such values using the new defaults.
>>>>>>> hotfix/5
