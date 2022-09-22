# laminas-coding-standard

[![Build Status](https://github.com/laminas/laminas-coding-standard/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas/laminas-coding-standard/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
> 
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
> 
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
> 
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
> 
> ## 🇺🇸 To Citizens of Russia
> 
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
> 
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
> 
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

The coding standard ruleset for Laminas components.

This specification extends and expands [PSR-12](https://www.php-fig.org/psr/psr-12),
the extended coding style guide and requires adherence to [PSR-1](https://www.php-fig.org/psr/psr-1),
the basic coding standard. These are minimal specifications and don't address all factors, including things like:

- whitespace around operators
- alignment of array keys and operators
- alignment of object operations
- how to format multi-line conditionals
- what and what not to import, and how
- etc.

Contributors have different coding styles and so do the maintainers. During code reviews there are regularly
discussions about spaces and alignments, where and when was said that a function needs to be imported. And
that's where this coding standard comes in: To have internal consistency in a component and between components.

## Installation

1. Install the module via composer by running:

   ```bash
   composer require --dev laminas/laminas-coding-standard
   ```

2. Add composer scripts into your `composer.json`:

   ```json
   "scripts": {
     "cs-check": "phpcs",
     "cs-fix": "phpcbf"
   }
   ```

3. Create file `phpcs.xml` on base path of your repository with this content:

   ```xml
   <?xml version="1.0"?>
   <ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="vendor/squizlabs/php_codesniffer/phpcs.xsd">

       <arg name="basepath" value="."/>
       <arg name="cache" value=".phpcs-cache"/>
       <arg name="colors"/>
       <arg name="extensions" value="php"/>
       <arg name="parallel" value="80"/>

       <!-- Show progress -->
       <arg value="p"/>

       <!-- Paths to check -->
       <file>config</file>
       <file>src</file>
       <file>test</file>

       <!-- Include all rules from the Laminas Coding Standard -->
       <rule ref="LaminasCodingStandard"/>
   </ruleset>
   ```

You can add or exclude some locations in that file.
For a reference please see: https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset

## Usage

- To run checks only:

  ```bash
  composer cs-check
  ```

- To automatically fix many CS issues:

  ```bash
  composer cs-fix
  ```

## Ignoring parts of a File

> Note: Before PHP_CodeSniffer version 3.2.0, `// @codingStandardsIgnoreStart` and `// @codingStandardsIgnoreEnd` were
> used. These are deprecated and will be removed in PHP_CodeSniffer version 4.0.

Disable parts of a file:

```php
$xmlPackage = new XMLPackage;
// phpcs:disable
$xmlPackage['error_code'] = get_default_error_code_value();
$xmlPackage->send();
// phpcs:enable
```

Disable a specific rule:

```php
// phpcs:disable Generic.Commenting.Todo.Found
$xmlPackage = new XMLPackage;
$xmlPackage['error_code'] = get_default_error_code_value();
// TODO: Add an error message here.
$xmlPackage->send();
// phpcs:enable
```

Ignore a specific violation:

```php
$xmlPackage = new XMLPackage;
$xmlPackage['error_code'] = get_default_error_code_value();
// phpcs:ignore Generic.Commenting.Todo.Found
// TODO: Add an error message here.
$xmlPackage->send();
```

## Development

> **New rules or Sniffs may not be introduced in minor or bugfix releases and should always be based on the develop
branch and queued for the next major release, unless considered a bugfix for existing rules.**

If you want to test changes against Laminas components or your own projects, install your forked
laminas-coding-standard globally with composer:

```bash
$ composer global config repositories.laminas-coding-standard vcs git@github.com:<FORK_NAMESPACE>/laminas-coding-standard.git
$ composer global require --dev laminas/laminas-coding-standard:dev-<FORKED_BRANCH>

# For this to work, add this to your path: ~/.composer/vendor/bin
# Using `-s` prints the rules that triggered the errors so they can be reviewed easily. `-p` is for progress display.
$ phpcs -sp --standard=LaminasCodingStandard src test
```

Make sure you remove the global installation after testing from your global composer.json file!!!

Documentation can be previewed locally by installing [MkDocs](https://www.mkdocs.org/#installation) and run
`mkdocs serve`. This will start a server where you can read the docs.

## Reference

Rules can be added, excluded or tweaked locally, depending on your preferences. More information on how to do this can
be found here:

- [Coding Standard Tutorial](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Coding-Standard-Tutorial)
- [Configuration Options](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options)
- [Selectively Applying Rules](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset#selectively-applying-rules)
- [Customisable Sniff Properties](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Customisable-Sniff-Properties)
