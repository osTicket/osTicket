# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.6.0 - 2016-02-04

### Added

- [zendframework/zend-config#6](https://github.com/zendframework/zend-config/pull/6) adds the ability for
  the `PhpArray` writer to optionally translate strings that evaluate to known
  classes to `ClassName::class` syntax; the feature works for both keys and
  values.
- [zendframework/zend-config#21](https://github.com/zendframework/zend-config/pull/21) adds revised
  documentation, and publishes it to https://docs.laminas.dev/laminas-config/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-config#8](https://github.com/zendframework/zend-config/pull/8),
  [zendframework/zend-config#18](https://github.com/zendframework/zend-config/pull/18), and
  [zendframework/zend-config#20](https://github.com/zendframework/zend-config/pull/20) update the
  code base to make it forwards-compatible with the v3.0 versions of
  laminas-stdlib and laminas-servicemanager. Primarily, this involved:
  - Updating the `AbstractConfigFactory` to implement the new methods in the
    v3 `AbstractFactoryInterface` definition, and updating the v2 methods to
    proxy to those.
  - Updating `ReaderPluginManager` and `WriterPluginManager` to follow the
    changes to `AbstractPluginManager`. In particular, instead of defining
    invokables, they now define a combination of aliases and factories (using
    the new `InvokableFactory`); additionally, they each now implement both
    `validatePlugin()` from v2 and `validate()` from v3.
  - Pinning to stable versions of already updated components.
  - Selectively omitting laminas-i18n-reliant tests when testing against
    laminas-servicemanager v3.
