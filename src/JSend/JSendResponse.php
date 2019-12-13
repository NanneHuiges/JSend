<?php

namespace JSend;

use BadMethodCallException;
use JsonSerializable;
use UnexpectedValueException;

/**
 * Class JSendResponse
 * @package JSend
 *
 * Implementation of the JSend specification
 */
class JSendResponse implements JsonSerializable
{
    public const SUCCESS = 'success';
    public const FAIL = 'fail';
    public const ERROR = 'error';

    public const KEY_STATUS = 'status';
    public const KEY_DATA = 'data';
    public const KEY_MESSAGE = 'message';
    public const KEY_CODE = 'code';


    protected string $status;
    protected ?array $data;
    protected ?string $errorCode;
    protected ?string $errorMessage;
    protected int $jsonEncodeOptions = 0;

    /**
     * When an API call is successful, the JSend object is used as a simple envelope for the results.
     *
     * @param array|null $data Acts as the wrapper for any data returned by the API call.
     *                         If the call returns no data (e.g. with a DELETE request), data should be set to null.
     *
     * @return JSendResponse
     */
    public static function success(array $data = null): JSendResponse
    {
        return new static(static::SUCCESS, $data);
    }

    /**
     * When an API call is rejected due to invalid data or call conditions, the JSend object's data key contains an
     * object explaining what went wrong, typically a hash of validation errors.
     *
     * @param array|null $data Again, provides the wrapper for the details of why the request failed. If the reasons for
     *                         failure correspond to POST values, the response object's keys SHOULD correspond to those
     *                         POST values.
     *
     * @return JSendResponse
     */
    public static function fail(array $data = null): JSendResponse
    {
        return new static(static::FAIL, $data);
    }

    /**
     * When an API call fails due to an error on the server.
     *
     * @param string $errorMessage A meaningful, end-user-readable (or at the least log-worthy) message, explaining
     *                             what went wrong.
     * @param string|null $errorCode A numeric code corresponding to the error, if applicable
     * @param array|null $data A generic container for any other information about the error, i.e. the conditions that
     *                         caused the error, stack traces, etc.
     *
     * @return JSendResponse
     *
     * @throws InvalidJSendException if empty($errorMessage) is true
     */
    public static function error(string $errorMessage, string $errorCode = null, array $data = null): JSendResponse
    {
        return new static(static::ERROR, $data, $errorMessage, $errorCode);
    }

    /**
     * JSendResponse constructor. Please use provided functions for success, fail or error.
     *
     * @param string $status one of static::SUCCESS, static::FAIL, static::ERROR
     * @param array|null $data
     * @param string|null $errorMessage mandatory for errors
     * @param string|null $errorCode
     *
     * @throws InvalidJSendException if status is not valid or status is error and empty($errorMessage) is true
     */
    private function __construct(string $status, array $data = null, $errorMessage = null, $errorCode = null)
    {
        if (!$this->isStatusValid($status)) {
            throw new InvalidJSendException('Status does not conform to JSend spec.');
        }
        $this->status = $status;

        if ($status === static::ERROR) {
            if (empty($errorMessage)) {
                throw new InvalidJSendException('Errors must contain a message.');
            }
            $this->errorMessage = $errorMessage;
            $this->errorCode = $errorCode;
        }

        $this->data = $data;
    }

    /**
     * Getter for the status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Getter for the data
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Getter for the errormessage
     *
     * @return null|string
     * @throws BadMethodCallException when called on a non-error message
     */
    public function getErrorMessage(): ?string
    {
        if ($this->isError()) {
            return $this->errorMessage;
        }

        throw new BadMethodCallException('Only responses with a status of error may have an error message.');
    }

    /**
     * Getter for the errorcode
     *
     * @return null|string
     * @throws BadMethodCallException when called on a non-error message
     */
    public function getErrorCode(): ?string
    {
        if ($this->isError()) {
            return $this->errorCode;
        }

        throw new BadMethodCallException('Only responses with a status of error may have an error code.');
    }

    /**
     * Checks if the provided string is one of the valid statusses
     *
     * @param string $status
     * @return bool
     */
    protected function isStatusValid(string $status): bool
    {
        $validStatuses = array(static::SUCCESS, static::FAIL, static::ERROR);

        return \in_array($status, $validStatuses, true);
    }

    /**
     * Check if this is a success object
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === static::SUCCESS;
    }

    /**
     * Check if this is a fail object
     *
     * @return bool
     */
    public function isFail(): bool
    {
        return $this->status === static::FAIL;
    }

    /**
     * Check if this is an error object
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->status === static::ERROR;
    }

    /**
     * Serializes the class into an array
     *
     * @return array the object as serialized array
     */
    public function asArray(): array
    {
        $theArray = [static::KEY_STATUS => $this->status];

        if ($this->data) {
            $theArray[static::KEY_DATA] = $this->data;
        }
        if (!$this->data && !$this->isError()) {
            // Data is optional for errors, so it should not be set
            // rather than be null.
            $theArray[static::KEY_DATA] = null;
        }

        if ($this->isError()) {
            $theArray[static::KEY_MESSAGE] = (string)$this->errorMessage;

            if (!empty($this->errorCode)) {
                $theArray[static::KEY_CODE] = (int)$this->errorCode;
            }
        }

        return $theArray;
    }

    /**
     * Sets the encoding options for json_encode.
     * @see https://www.php.net/manual/en/function.json-encode.php
     *
     * @param $options
     */
    public function setEncodingOptions(int $options): void
    {
        $this->jsonEncodeOptions = $options;
    }


    /**
     * Encodes the class into JSON
     *
     * @return string the raw JSON
     */
    public function encode(): string
    {
        return json_encode($this, $this->jsonEncodeOptions);
    }

    /**
     * Implements JsonSerializable interface
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->asArray();
    }

    /**
     * Simple to-string function that returns a (json) string for this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->encode();
    }

    /**
     * Encodes the class into JSON and sends it as a response with
     * the 'application/json' header
     */
    public function respond(): void
    {
        header('Content-Type: application/json');
        echo $this->encode();
    }

    /**
     * Takes raw JSON (JSend) and builds it into a new JSendResponse
     *
     * @param string $json the raw JSON (JSend) to decode
     * @param int $depth User specified recursion depth, defaults to 512
     * @param int $options Bitmask of JSON decode options.
     *
     * @return JSendResponse if JSON is invalid
     * @throws InvalidJSendException if JSend does not conform to spec
     * @see json_decode()
     */
    public static function decode($json, $depth = 512, $options = 0): JSendResponse
    {
        $rawDecode = json_decode($json, true, $depth, $options);

        if ($rawDecode === null) {
            throw new UnexpectedValueException('JSON is invalid.');
        }

        if ((!\is_array($rawDecode)) || (!array_key_exists(static::KEY_STATUS, $rawDecode))) {
            throw new InvalidJSendException('JSend must be an object with a valid status.');
        }

        $status = $rawDecode[static::KEY_STATUS];
        $data = $rawDecode[static::KEY_DATA] ?? null;
        $errorMessage = $rawDecode[static::KEY_MESSAGE] ?? null;
        $errorCode = $rawDecode[static::KEY_CODE] ?? null;

        if ($status === static::ERROR && $errorMessage === null) {
            throw new InvalidJSendException('JSend errors must contain a message.');
        }
        if ($status !== static::ERROR && !array_key_exists(static::KEY_DATA, $rawDecode)) {
            throw new InvalidJSendException('JSend must contain data unless it is an error.');
        }

        return new static($status, $data, $errorMessage, $errorCode);
    }
}
