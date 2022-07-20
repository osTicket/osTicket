{
    "name": "laminas/laminas-validator",
    "description": "Validation classes for a wide range of domains, and the ability to chain validators to create complex validation criteria",
    "license": "BSD-3-Clause",
    "keywords": [
        "laminas",
        "validator"
    ],
    "homepage": "https://laminas.dev",
    "support": {
        "docs": "https://docs.laminas.dev/laminas-validator/",
        "issues": "https://github.com/laminas/laminas-validator/issues",
        "source": "https://github.com/laminas/laminas-validator",
        "rss": "https://github.com/laminas/laminas-validator/releases.atom",
        "chat": "https://laminas.dev/chat",
        "forum": "https://discourse.laminas.dev"
    },
    "config": {
        "sort-packages": true
    },
    "extra": {
        "branch-alias": {
            "dev-master": "2.13.x-dev",
            "dev-develop": "2.14.x-dev"
        },
        "laminas": {
            "component": "Laminas\\Validator",
            "config-provider": "Laminas\\Validator\\ConfigProvider"
        }
    },
    "require": {
        "php": "^7.1",
        "container-interop/container-interop": "^1.1",
        "laminas/laminas-stdlib": "^3.2.1",
        "laminas/laminas-zendframework-bridge": "^1.0"
    },
    "require-dev": {
        "laminas/laminas-cache": "^2.6.1",
        "laminas/laminas-coding-standard": "~1.0.0",
        "laminas/laminas-config": "^2.6",
        "laminas/laminas-db": "^2.7",
        "laminas/laminas-filter": "^2.6",
        "laminas/laminas-http": "^2.5.4",
        "laminas/laminas-i18n": "^2.6",
        "laminas/laminas-math": "^2.6",
        "laminas/laminas-servicemanager": "^2.7.5 || ^3.0.3",
        "laminas/laminas-session": "^2.8",
        "laminas/laminas-uri": "^2.5",
        "phpunit/phpunit": "^7.5.20 || ^8.5.2",
        "psr/http-client": "^1.0",
        "psr/http-factory": "^1.0",
        "psr/http-message": "^1.0"
    },
    "suggest": {
        "laminas/laminas-db": "Laminas\\Db component, required by the (No)RecordExists validator",
        "laminas/laminas-filter": "Laminas\\Filter component, required by the Digits validator",
        "laminas/laminas-i18n": "Laminas\\I18n component to allow translation of validation error messages",
        "laminas/laminas-i18n-resources": "Translations of validator messages",
        "laminas/laminas-math": "Laminas\\Math component, required by the Csrf validator",
        "laminas/laminas-servicemanager": "Laminas\\ServiceManager component to allow using the ValidatorPluginManager and validator chains",
        "laminas/laminas-session": "Laminas\\Session component, ^2.8; required by the Csrf validator",
        "laminas/laminas-uri": "Laminas\\Uri component, required by the Uri and Sitemap\\Loc validators",
        "psr/http-message": "psr/http-message, required when validating PSR-7 UploadedFileInterface instances via the Upload and UploadFile validators"
    },
    "autoload": {
        "psr-4": {
            "Laminas\\Validator\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "LaminasTest\\Validator\\": "test/"
        }
    },
    "scripts": {
        "check": [
            "@cs-check",
            "@test"
        ],
        "cs-check": "phpcs",
        "cs-fix": "phpcbf",
        "test": "phpunit --colors=always",
        "test-coverage": "phpunit --colors=always --coverage-clover clover.xml"
    },
    "replace": {
        "zendframework/zend-validator": "^2.13.0"
    }
}
