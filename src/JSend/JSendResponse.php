<?php

namespace JSend;

use BadMethodCallException;
use JsonSerializable;
use UnexpectedValueException;

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
     * From the spec:
     * Description: All went well, and (usually) some data was returned.
     * Required   :  data
     *
     * @param array|null $data
     *
     * @return JSendResponse
     * @throws InvalidJSendException
     */
    public static function success(array $data = null): JSendResponse
    {
        return new static(static::SUCCESS, $data);
    }

    /**
     * From the spec:
     * Description: There was a problem with the data submitted, or some pre-condition of the API call wasn't satisfied
     * Required   : data
     *
     * @param array|null $data
     *
     * @return JSendResponse
     * @throws InvalidJSendException
     */
    public static function fail(array $data = null): JSendResponse
    {
        return new static(static::FAIL, $data);
    }

    /**
     * From the spec:
     * Description: An error occurred in processing the request, i.e. an exception was thrown
     * Required   : errorMessage
     * Optional   : errorCode, data
     *
     * @param string $errorMessage
     * @param string|null $errorCode
     * @param array|null $data
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
     * JSendResponse constructor.
     *
     * @param string $status one of static::SUCCESS, static::FAIL, static::ERROR
     * @param array|null $data
     * @param string|null $errorMessage mandatory for errors
     * @param string|null $errorCode
     *
     * @throws InvalidJSendException if status is not valid or status is error and empty($errorMessage) is true
     */
    public function __construct(string $status, array $data = null, $errorMessage = null, $errorCode = null)
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

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @return null|string
     */
    public function getErrorMessage(): ?string
    {
        if ($this->isError()) {
            return $this->errorMessage;
        }

        throw new BadMethodCallException('Only responses with a status of error may have an error message.');
    }

    /**
     * @return null|string
     */
    public function getErrorCode(): ?string
    {
        if ($this->isError()) {
            return $this->errorCode;
        }

        throw new BadMethodCallException('Only responses with a status of error may have an error code.');
    }

    protected function isStatusValid(string $status): bool
    {
        $validStatuses = array(static::SUCCESS, static::FAIL, static::ERROR);

        return \in_array($status, $validStatuses, true);
    }

    public function isSuccess(): bool
    {
        return $this->status === static::SUCCESS;
    }

    public function isFail(): bool
    {
        return $this->status === static::FAIL;
    }

    public function isError(): bool
    {
        return $this->status === static::ERROR;
    }

    /**
     * Serializes the class into an array
     * @return array the serialized array
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

    public function setEncodingOptions($options): void
    {
        $this->jsonEncodeOptions = $options;
    }


    /**
     * Encodes the class into JSON
     * @return string the raw JSON
     */
    public function encode(): string
    {
        return json_encode($this, $this->jsonEncodeOptions);
    }

    /**
     * Implements JsonSerializable interface
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->asArray();
    }

    /**
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
