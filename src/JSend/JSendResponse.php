<?php
namespace JSend;

class JSendResponse implements \JsonSerializable
{
    const SUCCESS = 'success';
    const FAIL = 'fail';
    const ERROR = 'error';

    protected $status;
    protected $data;
    protected $errorCode;
    protected $errorMessage;

    /** @var int  */
    protected $json_encode_options = 0;

    /**
     * From the spec:
     * Description: All went well, and (usually) some data was returned.
     * Required   :  data
     *
     * @param array|null $data
     *
     * @return static
     */
    public static function success(array $data = null)
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
     * @return static
     */
    public static function fail(array $data = null)
    {
        return new static(static::FAIL, $data);
    }

    /**
     * From the spec:
     * Description: An error occurred in processing the request, i.e. an exception was thrown
     * Required   : errorMessage
     * Optional   : errorCode, data
     *
     * @param            $errorMessage
     * @param null       $errorCode
     * @param array|null $data
     *
     * @return static
     *
     * @throws InvalidJSendException if empty($errorMessage) is true
     */
    public static function error($errorMessage, $errorCode = null, array $data = null)
    {
        return new static(static::ERROR, $data, $errorMessage, $errorCode);
    }

    /**
     * JSendResponse constructor.
     *
     * @param string      $status       one of static::SUCCESS, static::FAIL, static::ERROR
     * @param array|null  $data
     * @param string|null $errorMessage mandatory for errors
     * @param string|null $errorCode
     *
     * @throws InvalidJSendException if status is not valid or status is error and empty($errorMessage) is true
     */
    public function __construct($status, array $data = null, $errorMessage = null, $errorCode = null)
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

        throw new \BadMethodCallException('Only responses with a status of error may have an error message.');
    }

    public function getErrorCode()
    {
        if ($this->isError()) {
            return $this->errorCode;
        }

        throw new \BadMethodCallException('Only responses with a status of error may have an error code.');
    }

    protected function isStatusValid($status)
    {
        $validStatuses = array(static::SUCCESS, static::FAIL, static::ERROR);

        return in_array($status, $validStatuses);
    }

    public function isSuccess()
    {
        return $this->status === static::SUCCESS;
    }

    public function isFail()
    {
        return $this->status === static::FAIL;
    }

    public function isError()
    {
        return $this->status === static::ERROR;
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
        }
        if (!$this->data && !$this->isError()) {
            // Data is optional for errors, so it should not be set
            // rather than be null.
            $theArray['data'] = null;
        }

        if ($this->isError()) {
            $theArray['message'] = (string)$this->errorMessage;

            if (!empty($this->errorCode)) {
                $theArray['code'] = (int)$this->errorCode;
            }
        }

        return $theArray;
    }

    public function setEncodingOptions($options)
    {
        $this->json_encode_options = $options;
    }


    /**
     * Encodes the class into JSON
     * @return string the raw JSON
     */
    public function encode()
    {
        return json_encode($this, $this->json_encode_options);
    }

    /**
     * Implements JsonSerializable interface
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->asArray();
    }

    public function __toString()
    {
        return $this->encode();
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

    /**
     * Takes raw JSON (JSend) and builds it into a new JSendResponse
     *
     * @param string $json    the raw JSON (JSend) to decode
     * @param int    $depth   User specified recursion depth, defaults to 512
     * @param int    $options Bitmask of JSON decode options.
     *
     * @return JSendResponse if JSON is invalid
     * @throws InvalidJSendException if JSend does not conform to spec
     * @see json_decode()
     */
    public static function decode($json, $depth = 512, $options = 0)
    {
        $rawDecode = json_decode($json, true, $depth, $options);

        if ($rawDecode === null) {
            throw new \UnexpectedValueException('JSON is invalid.');
        }

        if ((!is_array($rawDecode)) or (!array_key_exists('status', $rawDecode))) {
            throw new InvalidJSendException('JSend must be an object with a valid status.');
        }

        $status = $rawDecode['status'];
        $data = array_key_exists('data', $rawDecode) ? $rawDecode['data'] : null;
        $errorMessage = array_key_exists('message', $rawDecode) ? $rawDecode['message'] : null;
        $errorCode = array_key_exists('code', $rawDecode) ? $rawDecode['code'] : null;

        if ($status === static::ERROR && $errorMessage === null) {
            throw new InvalidJSendException('JSend errors must contain a message.');
        }
        if ($status !== static::ERROR && !array_key_exists('data', $rawDecode)) {
            throw new InvalidJSendException('JSend must contain data unless it is an error.');
        }

        return new static($status, $data, $errorMessage, $errorCode);

    }
}
