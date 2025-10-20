# psr-log-aware-trait

Trait to allow support of different psr/log versions. 

By including this PsrLogAwareTrait, you can allow composer to resolve your PsrLogger version for you. 

## Use

Require the trait. 

        composer require chromatic/psr-log-aware-trait


In your code, you no longer have to set a $logger property on your classes, since that comes with the trait, and you do not need to implement the `function setLogger()` method, since that also comes along with the trait. 

```php
use PsrLogAwareTrait;
```
        
Will allow you to call `setLogger()` in your classes and fulfil the requirements of the PsrLoggerAwareInterface implementation.
