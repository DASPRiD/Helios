# Helios

[![Build Status](https://travis-ci.org/DASPRiD/Helios.svg?branch=master)](https://travis-ci.org/DASPRiD/Helios)
[![Coverage Status](https://coveralls.io/repos/github/DASPRiD/Helios/badge.svg?branch=master)](https://coveralls.io/github/DASPRiD/Helios?branch=master)
[![Dependency Status](https://www.versioneye.com/user/projects/57d149ff8d1bad004e51b93f/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/57d149ff8d1bad004e51b93f)
[![Reference Status](https://www.versioneye.com/php/dasprid:helios/reference_badge.svg?style=flat)](https://www.versioneye.com/php/dasprid:helios/references)
[![Latest Stable Version](https://poser.pugx.org/dasprid/helios/v/stable)](https://packagist.org/packages/dasprid/helios)
[![Total Downloads](https://poser.pugx.org/dasprid/helios/downloads)](https://packagist.org/packages/dasprid/helios)
[![License](https://poser.pugx.org/dasprid/helios/license)](https://packagist.org/packages/dasprid/helios)

Helios is an authentication middleware embracing PSR-7. It's purpose is to keep the identity completely request
dependent, as well as avoiding the use of server-side session through the use of [JSON Web Tokens](https://jwt.io/).

## Installation

Install via composer:

```bash
$ composer require dasprid/helios
```

## Getting started (for [Expressive](https://github.com/zendframework/zend-expressive))

### JWT cookies

This library uses Pikkuleipa, a cookie manager utilizing JSON Web Tokens. In order to start working with Helios, you need to [configure Pikkuleipa first](https://github.com/DASPRiD/Pikkuleipa).

### Import the factory config

Create a file named `helios.global.php` or similar in your autoloading config directory:

```php
<?php
return (new DASPRiD\Helios\ConfigProvider())->__invoke();
```

This will introduce a few factories, namely you can retrieve the following objects through that:

- `DASPRiD\Helios\IdentityCookieManager` through `DASPRiD\Helios\IdentityCookieManager`
- `DASPRiD\Helios\IdentityMiddleware` through `DASPRiD\Helios\IdentityMiddleware`

### Create an identity lookup

You'll need to implement a lookup which retrieves the user identity based on the subject stored in the token. Register
that lookup in your dependency container:

```php
<?php
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use DASPRiD\Helios\Identity\LookupResult;

class MyIdentityLookup implements IdentityLookupInterface
{
    public function lookup($subject) : LookupResult
    {
        // Pseudo-code here
        if ($this->repository->has($subject)) {
            return LookupResult::fromIdentity($this->repository->get($subject));
        }

        return LookupResult::invalid();
    }
}
```

Then wire your implementation to Helios by updating configuration file:
```php
<?php
return [
    'helios' => [
        // ...
        'identity_lookup_id' => MyIdentityLookup::class,
    ],
];
```

### Configure Helios

For Helios to function, it needs a few configuration variables. Copy the file `doc/example-config.php` and adjust the
values as needed.

### Register the identity middleware

Helios ships with an `IdentityMiddleware`, which should be registered in your middleware pipeline before the dispatch
middleware. The exact location in the stack depends on your own needs.

### Write your sign-in middleware

Helios itself does not ship with any actual logic for signing users in or out. Thus, a simple sign-in middleware may
look like this:

```php
<?php
class MySignIn
{
    /**
     * @var \DASPRiD\Helios\IdentityCookieManager
     */
    private $identityCookieManager;

    public function __invoke()
    {
        // Verify the user

        if ($userIsValid) {
            $response = new Zend\Diactoros\Response\RedirectResponse('/go/somewhere');
            return $this->identityCookieManager->injectCookie(
                $response,
                $user->getId(),
                ! $rememberMeSelected
            );
        }

        // Do some error response here
    }
}
```

### Write your sign-out middleware

Similar to the sign-in middleware, your sign-out middleware can use the `IdentityCookieManager` to invalidate the
cookie:

```php
<?php
class MySignOut
{
    /**
     * @var \DASPRiD\Helios\IdentityCookieManager
     */
    private $identityCookieManager;

    public function __invoke()
    {
        $response = new Zend\Diactoros\Response\RedirectResponse('/go/somewhere');
        return $this->identityCookieManager->expireCookie($response);
    }
}
```

### Retrieve the user identity in a middleware

Each time the user is retrieved by the `IdentityMiddleware`, it is injected into the request as an attribute. Thus when
you need the user in your middleware, you can easily get it:

```php
<?php
use DASPRiD\Helios\IdentityMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class SomeOtherMiddleware
{
    public function __invoke(ServerRequestInterface $request)
    {
        $user = $request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE);
    }
}
```

Sometimes it may be required that the identity is always available in your view, e.g. to display the username in the
layout. The proper way to handle that case is to use a specific template renderer which takes the request object, beside
the usual view parameters, and injects the user into the view variables before rendering. Try to avoid injecting the
entire request object into the view parameters though.
