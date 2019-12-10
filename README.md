[![Build Status](https://travis-ci.org/NanneHuiges/JSend.svg)](https://travis-ci.org/NanneHuiges/JSend)  
[![Code Climate](https://codeclimate.com/github/NanneHuiges/JSend/badges/gpa.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  
[![Issue Count](https://codeclimate.com/github/NanneHuiges/JSend/badges/issue_count.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  
  
[![Total Downloads](https://poser.pugx.org/nannehuiges/jsend/downloads)](https://packagist.org/packages/nannehuiges/jsend)  
# JSend
A simple PHP implementation of the [JSend specification](http://labs.omniti.com/labs/jsend).

## Usage
```php
use JSend\JSendResponse;
```

### New response
```php
$success = new JSendResponse('success', $data);
$fail = new JSendResponse('fail', $data);
$error = new JSendResponse('error', $data, 'Not cool.', 9001);
```

```php
$success = JSendResponse::success($data);
$fail = JSendResponse::fail($data);
$error = JSendResponse::error('Not cool.', 9001, $data);
```

**Note**: an `InvalidJSendException` is thrown if the status is invalid or if you're creating an `error` without a `message`.

### Convert JSendResponse to JSON
`__toString()` is overridden to encode JSON automatically.

```php
$json = $success->encode();
$json = (string) $success;
```

As JSendResponse is `JsonSerializable`, you can use the object directly in `json_encode`

```php
json_encode($success);
```

#### Setting flags
You can set flags if needed:

```php
$success->setEncodingOptions(\JSON_PRETTY_PRINT | \JSON_BIGINT_AS_STRING);
$json = $success->encode();
```

### Convert JSON to JSendResponse
```php
try {
    $response = JSendResponse::decode($json);
} catch (InvalidJSendException $e) {
    echo "You done gone passed me invalid JSend.";
}
```

### Send JSON as HTTP Response
This sets the `Content-Type` header to `application/json` and spits out the JSON.

```php
$jsend = new JSendResponse('success', $data);
$jsend->respond();
```

### Get info
```php
$isSuccess = $response->isSuccess();
$isError = $response->isError();
$isFail = $response->isFail();
$status = $response->getStatus();
$data = $response->getData();
$array = $response->asArray();
```

Additionally, you can call the following methods on an error. A `BadMethodCallException` is thrown if the status is not `error`, so check first.

```php
if ($response->isError()) {
    $code = $response->getErrorCode;
    $message = $response->getErrorMessage;
}
```

# Development
For your convenience, there is a dockerfile with the right dependencies (php, composer) available. Please use those
to run various things (composer, phpunit, etc). You will need `docker` and `docker-compose` installed, but you don't
need `PHP` or `composer`.

## Setting up your install
Running `./install.sh` will run composer for you in a development container. It does some magic with a `.user.env`
file that will make sure you run all the stuff as your local user. This will help with access to the generated files.

You can run `./bin/composer` if you want to do any `composer` things, like `composer update`. If that takes to long each 
time, you can jump in a shell by using `./bin/shell`. This makes sure you always run your build (or test) commands in 
the right environment.

## Testing and code quality
There are scripts in `/bin` to help you test for issues:

* codeclimate: run various codeclimate checks, like phpcodesniffer, phan, etc. See `.codeclimate.yml`
* phpunit: runs the testsuite

These tests are run on the CI as well, but please make sure they don't fail before you do a PR

## Notes
* Note that the `composer.lock` file is ignored. This is standard practice for libraries.
* The current tests are done on php 7.2, but tests are for 7.3 and 7.4 as well

# Credits
The library was written by [Jamie Schembri](https://github.com/shkm). It has been transfered to the current account [Nanne Huiges](https://github.com/NanneHuiges) in december 2015.
