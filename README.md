[![Build Status](https://travis-ci.org/NanneHuiges/JSend.svg)](https://travis-ci.org/NanneHuiges/JSend)  
[![Test Coverage](https://codeclimate.com/github/NanneHuiges/JSend/badges/coverage.svg)](https://codeclimate.com/github/NanneHuiges/JSend/coverage)  
[![Code Climate](https://codeclimate.com/github/NanneHuiges/JSend/badges/gpa.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  
[![Issue Count](https://codeclimate.com/github/NanneHuiges/JSend/badges/issue_count.svg)](https://codeclimate.com/github/NanneHuiges/JSend)  

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
# Credits
The library was written by [Jamie Schembri](https://github.com/shkm). It has been transfered to the current account [Nanne Huiges](https://github.com/NanneHuiges) in december 2015.
