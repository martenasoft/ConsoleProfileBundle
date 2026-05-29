# This is console profile bundle for symfony

## Install

```bash
composer require martenasoft/console_profile_bundle

```
### to config/bundles.php


```php
return [
    ...
    MartenaSoft\ConsoleProfileBundle\ConsoleProfileBundle::class => ['test' => true],
];
```

### optional config/packages/console_profile.yaml

Recommended safe configuration for `ms:profiler`:

```yaml
console_profile:
  enabled: true
  collectors:
    - request
    - dump
    - db
    - events
```


### then run some test

```php
php bin/phpunit SomeRequestTest.php --filter=yourTestFunction
```

### then run profile

```php
 php bin/console ms:profiler --env=test
```
