<?php

use JSend\JSendResponse;
use PHPUnit\Framework\TestCase;

/**
 * Using static factory methods is / should be allowed.
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class JSendResponseTest extends TestCase
{
    const SUCCESS = 'success';
    const FAIL = 'fail';
    const ERROR = 'error';

    protected $data;
    protected $errorMessage;
    protected $errorCode;

    /** @var  JSendResponse */
    protected $success;
    /** @var  JSendResponse */
    protected $fail;
    /** @var  JSendResponse */
    protected $error;

    /** @var  JSendResponse */
    protected $successWithData;
    /** @var  JSendResponse */
    protected $failWithData;
    /** @var  JSendResponse */
    protected $errorWithData;

    protected function setUp(): void
    {
        $this->data = array(
            'user' => array(
                'id' => 1,
                'first_name' => 'foo',
                'posts' => array(1, 5, 8),
            ),
        );

        $this->errorMessage = 'error';
        $this->errorCode = 42;

        $this->success = JSendResponse::success();
        $this->successWithData = JSendResponse::success($this->data);

        $this->fail = JSendResponse::fail();
        $this->failWithData = JSendResponse::fail($this->data);

        $this->error = JSendResponse::error($this->errorMessage);
        $this->errorWithData = JSendResponse::error(
            $this->errorMessage,
            $this->errorCode,
            $this->data
        );
    }

    /**
	 * Trying to create an error without a message should throw an exception
     */
    public function testCreatingErrorWithoutErrorMessageThrowsException()
    {
    	$this->expectException(\JSend\InvalidJSendException::class);
        new JSendResponse('error', array());
    }

    /**
     * @expectedExceptionMessage Status does not conform to JSend spec.
     */
    public function testThrowsExceptionIfStatusInvalid()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
        new JSendResponse('');
    }

    public function testSuccessHasCorrectStatus()
    {
        $this->assertEquals(self::SUCCESS, $this->success->getStatus());
        $this->assertTrue($this->success->isSuccess());
    }

    public function testFailHasCorrectStatus()
    {
        $this->assertEquals(self::FAIL, $this->fail->getStatus());
        $this->assertTrue($this->fail->isFail());
    }

    public function testErrorHasCorrectStatus()
    {
        $this->assertEquals(self::ERROR, $this->error->getStatus());
        $this->assertTrue($this->error->isError());
    }

    public function testErrorHasCorrectMessage()
    {
        $this->assertEquals($this->errorMessage, $this->error->getErrorMessage());
    }

    public function testErrorHasCorrectCode()
    {
        $this->assertNull($this->error->getErrorCode());
        $this->assertEquals($this->errorCode, $this->errorWithData->getErrorCode());
    }

    /**
     * @expectedExceptionMessage Only responses with a status of error may have an error message.
     */
    public function testGetErrorMessageThrowsExceptionIfStatusNotError()
    {
		$this->expectException(BadMethodCallException::class);
		$this->success->getErrorMessage();
    }

    /**
     * @expectedExceptionMessage Only responses with a status of error may have an error code.
     */
    public function testGetErrorCodeThrowsExceptionIfStatusNotError()
    {
		$this->expectException(BadMethodCallException::class);
		$this->fail->getErrorCode();
    }

    public function testResponseHasCorrectData()
    {
        $this->assertEquals($this->data, $this->successWithData->getData());
        $this->assertEquals($this->data, $this->failWithData->getData());
        $this->assertEquals($this->data, $this->errorWithData->getData());
    }

    public function testResponseEncodesValidJson()
    {
        $this->assertNotNull($this->encodeAndDecode($this->success));
        $this->assertNotNull($this->encodeAndDecode($this->fail));
        $this->assertNotNull($this->encodeAndDecode($this->error));

        $this->assertNotNull($this->encodeAndDecode($this->successWithData));
        $this->assertNotNull($this->encodeAndDecode($this->failWithData));
        $this->assertNotNull($this->encodeAndDecode($this->errorWithData));
    }

    public function testAsArrayHasCorrectData()
    {
        $successAsArray = $this->success->asArray();
        $this->assertEquals(
            $this->success->getStatus(),
            $successAsArray['status']
        );
        $this->assertEquals(
            $this->success->getData(),
            $successAsArray['data']
        );

        $successWithDataAsArray = $this->successWithData->asArray();
        $this->assertEquals(
            $this->successWithData->getStatus(),
            $successWithDataAsArray['status']
        );
        $this->assertEquals(
            $this->successWithData->getData(),
            $successWithDataAsArray['data']
        );

        $successAsArray = $this->successWithData->asArray();
        $this->assertEquals(
            $this->successWithData->getStatus(),
            $successAsArray['status']
        );
        $this->assertEquals(
            $this->successWithData->getData(),
            $successAsArray['data']
        );

        $errorAsArray = $this->errorWithData->asArray();
        $this->assertEquals(
            $this->errorWithData->getStatus(),
            $errorAsArray['status']
        );
        $this->assertEquals(
            $this->errorWithData->getErrorMessage(),
            $errorAsArray['message']
        );
        $this->assertEquals(
            $this->errorWithData->getErrorCode(),
            $errorAsArray['code']
        );
        $this->assertIsInt($errorAsArray['code']);
    }

    public function testSuccessEncodesIdenticalJson()
    {
        // without data
        $decoded = $this->encodeAndDecode($this->success);
        $this->assertEquals(self::SUCCESS, $decoded['status']);
        $this->assertEquals(null, $decoded['data']);
        $this->assertArrayNotHasKey('message', $decoded);
        $this->assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->successWithData);
        $this->assertEquals($this->data, $decoded['data']);
    }

    public function test__toString()
    {
        $success = new JSendResponse('success', $this->data);
        $this->assertEquals($success->encode(), (string)$success);
    }

    public function testJsonSerializable()
    {
        $success = new JSendResponse('success', $this->data);
        $this->assertEquals($success->encode(), json_encode($success));
    }

    public function testFailEncodesIdenticalJson()
    {
        // without data
        $decoded = $this->encodeAndDecode($this->fail);
        $this->assertEquals(self::FAIL, $decoded['status']);
        $this->assertEquals(null, $decoded['data']);
        $this->assertArrayNotHasKey('message', $decoded);
        $this->assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->failWithData);
        $this->assertEquals($this->data, $decoded['data']);
    }

    public function testErrorEncodesIdenticalJson()
    {
        // without data
        $decoded = $this->encodeAndDecode($this->error);
        $this->assertEquals(self::ERROR, $decoded['status']);
        $this->assertArrayNotHasKey('data', $decoded);
        $this->assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->errorWithData);

        $this->assertEquals($this->data, $decoded['data']);
        $this->assertEquals($this->errorMessage, $decoded['message']);
        $this->assertEquals($this->errorCode, $decoded['code']);
    }

    protected function encodeAndDecode(JsendResponse $response)
    {
        $decodeToAssocArrays = true;
        $encoded = $response->encode();
        return json_decode($encoded, $decodeToAssocArrays);
    }

    public function testEncodingResponseToJsonAndBackToResponseReturnsIdenticalClass()
    {
        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->success));
        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->fail));
        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->error));

        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->successWithData));
        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->failWithData));
        $this->assertTrue($this->isEncodedAndDecodedBackIdentical($this->errorWithData));
    }

    protected function isEncodedAndDecodedBackIdentical(JSendResponse $jsend)
    {
        $json = $jsend->encode();
        $recoded = JSendResponse::decode($json);
        return $jsend == $recoded;
    }

    /**
	 * Test that we throw an invalid value exception on invalid json
     */
    public function testDecodeInvalidJsonThrowsException()
    {
		$this->expectException(\UnexpectedValueException::class);
		JSendResponse::decode('This is not valid JSON.');
    }

    /**
     * The `""` string is valid JSON, but not valid for JSend.
     * It should not throw the same exception as invalid JSON.
     */
    public function testDecodeEmptyStringThrowsRightException()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
		JSendResponse::decode('""');
    }

    /**
     * JSend must be an object with a valid status.
     */
    public function testDecodeMissingStatusKeyThrowsException()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
		JSendResponse::decode('{ "not-status": "Status A OK!" }');
    }

    /**
     * JSend must contain data unless it is an error.
     */
    public function testDecodeDataKeyMustExistIfNotError()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
		JSendResponse::decode('{ "status": "success" }');
    }

    /**
     * JSend errors must contain a message.
     */
    public function testDecodeErrorMustHaveMessage()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
		JSendResponse::decode('{ "status": "error" }');
    }

    /**
     * This test exposes some inconsistency in the library
     * There are double 'safety' tests for wrong input, in this case checking if we do have a message.
     * An empty message will NOT fail in the decode function but will fail in the constructor.
     * Currently this constructor has a different Exception message, that we test for here
     *
     * This is probably a hint that the 2 different spots that actually test for this need to
     * become a single line of code.
     *
     */
    public function testDecodeErrorAnEmptyStringShouldNotBeValid()
    {
		$this->expectException(\JSend\InvalidJSendException::class);
		JSendResponse::decode('{ "status": "error", "message": "" }');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRespondSendsJson()
    {
       $this->expectOutputString($this->success->encode());
       $this->success->respond();
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testRespondHasCorrectContentType()
    {
        $this->expectOutputString($this->success->encode());
        $this->success->respond();
        $headers = xdebug_get_headers();

        $this->assertNotEmpty($headers);
        $this->assertContains('Content-Type: application/json', $headers);
    }

    public function testExtending()
    {
        $extended = Extended::success();
        $this->assertInstanceOf('Extended', $extended);
    }

    public function testAddingEncodeOptions(){
        $success = JSendResponse::success(array('some'=>'data'));
        $success->setEncodingOptions(\JSON_PRETTY_PRINT);
        $result = $success->encode();
        $pretty = json_encode($success->asArray(), \JSON_PRETTY_PRINT);
        $this->assertEquals($pretty, $result);
    }

}

class Extended extends JSendResponse
{

}