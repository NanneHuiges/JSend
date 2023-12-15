![Build Status](https://github.com/NanneHuiges/JSend/actions/workflows/ci_phpunit_php81.yml/badge.svg)
![Build Status](https://github.com/NanneHuiges/JSend/actions/workflows/ci_phpunit_php82.yml/badge.svg)
![Build Status](https://github.com/NanneHuiges/JSend/actions/workflows/ci_phpunit_php83.yml/badge.svg)
![Build Status](https://github.com/NanneHuiges/JSend/actions/workflows/ci_phpmd.yml/badge.svg)

[![Code Climate](https://codeclimate.com/github/NanneHuiges/JSend/badges/gpa.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  
[![Issue Count](https://codeclimate.com/github/NanneHuiges/JSend/badges/issue_count.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  
  
[![Total Downloads](https://poser.pugx.org/nannehuiges/jsend/downloads)](https://packagist.org/packages/nannehuiges/jsend)  
# JSend
A simple PHP implementation of the [JSend specification](https://github.com/omniti-labs/jsend).

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
to run various things (composer, phpunit, etc). You will need `docker` installed, but you don't
need `PHP` or `composer` or any of the other dependencies. 

## Setting up and using a local environment

To start using the local environment for testing and debugging all you have to is open a shell in the root folder of where this project is checked out. Then run the following command.

```bash
make build install
```
This command should be run occasionally to keep the local environment up to date. For instance when the composer dependencies are changed.

### Using the shell

To open a shell in the docker container run the following command.
```bash
make shell
```
Available commands are in `/bin`

### Running the code quality tools locally

We use a variety of tools to keep the code quality of the library high. To run one the tools you only need to run

```bash
make <tool_name>
```
Available tools:
- `phpstan` [PHPStan](https://phpstan.org/) is static analyser tool that can detect various code issues.
- `phpunit` [PHPUnit](https://phpunit.de/) is the unit testing framework we use for this library.
- `codeclimate` [CodeClimate](https://codeclimate.com/github/NanneHuiges/JSend)

## Notes
* Note that the `composer.lock` file is ignored. 

# Credits
The library was written by [Jamie Schembri](https://github.com/shkm). It has been transfered to the current account [Nanne Huiges](https://github.com/NanneHuiges) in december 2015.
