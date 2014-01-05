<?php
namespace JSend;

class JSendResponse
{
    const SUCCESS = 'success';
    const FAIL = 'fail';
    const ERROR = 'error';

    protected $status;
    protected $data;
    protected $errorCode;
    protected $errorMessage;

    public static function success(array $data = null)
    {
        return new self(self::SUCCESS, $data);
    }

    public static function fail(array $data = null)
    {
        return new self(self::FAIL, $data);
    }

    public static function error($errorMessage, $errorCode = null, array $data = null)
    {
        return new self(self::ERROR, $data, $errorMessage, $errorCode);
    }

    public function __construct($status, array $data = null, $errorMessage = null, $errorCode = null)
    {
        if (! $this->isStatusValid($status)) {
            throw new InvalidJSendException('Status does not conform to JSend spec.');
        }
        $this->status = $status;

        if ($status === self::ERROR)
        {
            if (empty($errorMessage))
            {
                throw new InvalidJSendException('Errors must contain a message.');
            }
            $this->errorMessage = $errorMessage;
            $this->errorCode = $errorCode;
        }

        $this->data = $data;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrorMessage()
    {
        if ($this->isError()) {
            return $this->errorMessage;
        }

        throw new \BadMethodCallException(
            'Only responses with a status of error may have an error message.');
    }

    public function getErrorCode()
    {
        if ($this->isError()) {
            return $this->errorCode;
        }

        throw new \BadMethodCallException(
            'Only responses with a status of error may have an error code.');
    }

    protected function isStatusValid($status)
    {
        $validStatuses = array(self::SUCCESS, self::FAIL, self::ERROR);

        return in_array($status, $validStatuses);
    }

    public function isSuccess()
    {
        return $this->status === self::SUCCESS;
    }

    public function isFail()
    {
        return $this->status === self::FAIL;
    }

    public function isError()
    {
        return $this->status === self::ERROR;
    }

    /**
     * Serializes the class into an array
     * @return array the serialized array
     */
    public function asArray()
    {
        $theArray = array(
            'status' => $this->status,
        );

        if ($this->data) {
            $theArray['data'] = $this->data;
        } else {
            if (! $this->isError()) {
                // Data is optional for errors, so it should not be set
                // rather than be null.
                $theArray['data'] = null;
            }
        }

        if ($this->isError()) {
            $theArray['message'] = (string) $this->errorMessage;

            if (! empty($this->errorCode)) {
                $theArray['code'] = (int) $this->errorCode;
            }
        }

        return $theArray;
    }

    /**
     * Encodes the class into JSON
     * @return string the raw JSON
     */
    public function encode()
    {
        return json_encode($this->asArray());
    }

    /**
     * Encodes the class into JSON and sends it as a response with
     * the 'application/json' header
     */
    public function respond()
    {
        header('Content-Type: application/json');
        echo $this->encode();
    }

    public function __toString()
    {
        return $this->encode();
    }

    /**
     * Takes raw JSON (JSend) and builds it into a new JSendResponse
     * @param string $json the raw JSON (JSend) to decode
     * @see json_decode()
     * @throws UnexpectedValueException if JSON is invalid
     * @throws InvalidJSendException if JSend does not conform to spec
     * @return JSendResponse the response created from the JSON
     */
    public static function decode($json, $depth = 512, $options = 0)
    {
        $rawDecode = json_decode($json, true, $depth, $options);

        if ($rawDecode === null) {
            throw new \UnexpectedValueException('JSON is invalid.');
        }

        if ((! is_array($rawDecode)) or (! array_key_exists('status', $rawDecode))) {
            throw new InvalidJSendException(
                'JSend must be an object with a valid status.');
        }

        $status = $rawDecode['status'];
        $data = array_key_exists('data', $rawDecode) ? $rawDecode['data'] : null;
        $errorMessage = array_key_exists('message', $rawDecode) ? $rawDecode['message'] : null;
        $errorCode = array_key_exists('code', $rawDecode) ? $rawDecode['code'] : null;

        if ($status === self::ERROR) {
            if ($errorMessage === null) {
                throw new InvalidJSendException('JSend errors must contain a message.');
            }
        } elseif (! array_key_exists('data', $rawDecode)) {
            throw new InvalidJSendException('JSend must contain data unless it is an error.');
        }

        return new self($status, $data, $errorMessage, $errorCode);

    }
}

class InvalidJSendException extends \Exception { };
