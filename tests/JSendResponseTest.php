<?php

use JSend\JSendResponse;
use PHPUnit\Framework\TestCase;

/**
 * Using static factory methods is / should be allowed.
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class JSendResponseTest extends TestCase
{
    private const SUCCESS = 'success';
    private const FAIL = 'fail';
    private const ERROR = 'error';

    protected array $data;
    protected string $errorMessage;
    protected int $errorCode;

    protected JSendResponse $success;
    protected JSendResponse $fail;
    protected JSendResponse $error;

    protected JSendResponse $successWithData;
    protected JSendResponse $failWithData;
    protected JSendResponse $errorWithData;
    protected JSendResponse $errorWithDataCodeString;

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
        $this->errorWithDataCodeString  = JSendResponse::error(
            $this->errorMessage,
            (string) $this->errorCode,
            $this->data
        );
    }

    /**
     * Trying to create an error without a message should throw an exception
     */
    public function testCreatingErrorWithoutErrorMessageThrowsException(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        new JSendResponse('error', array());
    }

    /**
     * expected Exception: Status does not conform to JSend spec.
     */
    public function testThrowsExceptionIfStatusInvalid(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        $this->expectExceptionMessage('Status does not conform to JSend spec.');
        new JSendResponse('');
    }

    public function testSuccessHasCorrectStatus(): void
    {
        self::assertEquals(self::SUCCESS, $this->success->getStatus());
        self::assertTrue($this->success->isSuccess());
    }

    public function testFailHasCorrectStatus(): void
    {
        self::assertEquals(self::FAIL, $this->fail->getStatus());
        self::assertTrue($this->fail->isFail());
    }

    public function testErrorHasCorrectStatus(): void
    {
        self::assertEquals(self::ERROR, $this->error->getStatus());
        self::assertTrue($this->error->isError());
    }

    public function testErrorHasCorrectMessage(): void
    {
        self::assertEquals($this->errorMessage, $this->error->getErrorMessage());
    }

    public function testErrorHasCorrectCode(): void
    {
        self::assertNull($this->error->getErrorCode());
        self::assertEquals($this->errorCode, $this->errorWithData->getErrorCode());
        self::assertEquals($this->errorCode, $this->errorWithDataCodeString->getErrorCode());
    }

    /**
     * expected Exception Message Only responses with a status of error may have an error message.
     */
    public function testGetErrorMessageThrowsExceptionIfStatusNotError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Only responses with a status of error may have an error message.');
        $this->success->getErrorMessage();
    }

    /**
     * expected Exception Message Only responses with a status of error may have an error code.
     */
    public function testGetErrorCodeThrowsExceptionIfStatusNotError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Only responses with a status of error may have an error code.');
        $this->fail->getErrorCode();
    }

    public function testResponseHasCorrectData(): void
    {
        self::assertEquals($this->data, $this->successWithData->getData());
        self::assertEquals($this->data, $this->failWithData->getData());
        self::assertEquals($this->data, $this->errorWithData->getData());
        self::assertEquals($this->data, $this->errorWithDataCodeString->getData());
    }

    public function testResponseEncodesValidJson(): void
    {
        self::assertNotNull($this->encodeAndDecode($this->success));
        self::assertNotNull($this->encodeAndDecode($this->fail));
        self::assertNotNull($this->encodeAndDecode($this->error));

        self::assertNotNull($this->encodeAndDecode($this->successWithData));
        self::assertNotNull($this->encodeAndDecode($this->failWithData));
        self::assertNotNull($this->encodeAndDecode($this->errorWithData));
        self::assertNotNull($this->encodeAndDecode($this->errorWithDataCodeString));
    }

    public function testAsArrayHasCorrectData(): void
    {
        $successAsArray = $this->success->asArray();
        self::assertEquals(
            $this->success->getStatus(),
            $successAsArray['status']
        );
        self::assertEquals(
            $this->success->getData(),
            $successAsArray['data']
        );

        $successWithDataAsArray = $this->successWithData->asArray();
        self::assertEquals(
            $this->successWithData->getStatus(),
            $successWithDataAsArray['status']
        );
        self::assertEquals(
            $this->successWithData->getData(),
            $successWithDataAsArray['data']
        );

        $successAsArray = $this->successWithData->asArray();
        self::assertEquals(
            $this->successWithData->getStatus(),
            $successAsArray['status']
        );
        self::assertEquals(
            $this->successWithData->getData(),
            $successAsArray['data']
        );

        $errorAsArray = $this->errorWithData->asArray();
        self::assertEquals(
            $this->errorWithData->getStatus(),
            $errorAsArray['status']
        );
        self::assertEquals(
            $this->errorWithData->getErrorMessage(),
            $errorAsArray['message']
        );
        self::assertEquals(
            $this->errorWithData->getErrorCode(),
            $errorAsArray['code']
        );
        self::assertIsInt($errorAsArray['code']);

        $errorAsArray = $this->errorWithDataCodeString->asArray();
        self::assertEquals(
            $this->errorWithData->getStatus(),
            $errorAsArray['status']
        );
        self::assertEquals(
            $this->errorWithData->getErrorMessage(),
            $errorAsArray['message']
        );
        self::assertEquals(
            $this->errorWithData->getErrorCode(),
            $errorAsArray['code']
        );
        self::assertIsInt($errorAsArray['code']); // we decode as an int, even if we provide a string
    }

    public function testSuccessEncodesIdenticalJson(): void
    {
        // without data
        $decoded = $this->encodeAndDecode($this->success);
        self::assertEquals(self::SUCCESS, $decoded['status']);
        self::assertEquals(null, $decoded['data']);
        self::assertArrayNotHasKey('message', $decoded);
        self::assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->successWithData);
        self::assertEquals($this->data, $decoded['data']);
    }

    public function test__toString(): void
    {
        $success = new JSendResponse('success', $this->data);
        self::assertEquals($success->encode(), (string)$success);
    }

    public function testJsonSerializable(): void
    {
        $success = new JSendResponse('success', $this->data);
        self::assertEquals($success->encode(), json_encode($success));
    }

    public function testFailEncodesIdenticalJson(): void
    {
        // without data
        $decoded = $this->encodeAndDecode($this->fail);
        self::assertEquals(self::FAIL, $decoded['status']);
        self::assertEquals(null, $decoded['data']);
        self::assertArrayNotHasKey('message', $decoded);
        self::assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->failWithData);
        self::assertEquals($this->data, $decoded['data']);
    }

    public function testErrorEncodesIdenticalJson(): void
    {
        // without data
        $decoded = $this->encodeAndDecode($this->error);
        self::assertEquals(self::ERROR, $decoded['status']);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertArrayNotHasKey('code', $decoded);

        // with data
        $decoded = $this->encodeAndDecode($this->errorWithData);

        self::assertEquals($this->data, $decoded['data']);
        self::assertEquals($this->errorMessage, $decoded['message']);
        self::assertEquals($this->errorCode, $decoded['code']);
    }

    protected function encodeAndDecode(JsendResponse $response)
    {
        $decodeToAssocArrays = true;
        $encoded = $response->encode();
        return json_decode($encoded, $decodeToAssocArrays);
    }

    public function testEncodingResponseToJsonAndBackToResponseReturnsIdenticalClass(): void
    {
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->success));
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->fail));
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->error));

        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->successWithData));
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->failWithData));
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->errorWithData));
        self::assertTrue($this->isEncodedAndDecodedBackIdentical($this->errorWithDataCodeString));

    }

    protected function isEncodedAndDecodedBackIdentical(JSendResponse $jsend): bool
    {
        $json = $jsend->encode();
        $recoded = JSendResponse::decode($json);
        return $jsend == $recoded;
    }

    /**
     * Test that we throw an invalid value exception on invalid json
     */
    public function testDecodeInvalidJsonThrowsException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        JSendResponse::decode('This is not valid JSON.');
    }

    /**
     * The `""` string is valid JSON, but not valid for JSend.
     * It should not throw the same exception as invalid JSON.
     */
    public function testDecodeEmptyStringThrowsRightException(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        JSendResponse::decode('""');
    }

    /**
     * JSend must be an object with a valid status.
     */
    public function testDecodeMissingStatusKeyThrowsException(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        JSendResponse::decode('{ "not-status": "Status A OK!" }');
    }

    /**
     * JSend must contain data unless it is an error.
     */
    public function testDecodeDataKeyMustExistIfNotError(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        JSendResponse::decode('{ "status": "success" }');
    }

    /**
     * JSend errors must contain a message.
     */
    public function testDecodeErrorMustHaveMessage(): void
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
    public function testDecodeErrorAnEmptyStringShouldNotBeValid(): void
    {
        $this->expectException(\JSend\InvalidJSendException::class);
        JSendResponse::decode('{ "status": "error", "message": "" }');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRespondSendsJson(): void
    {
        $this->expectOutputString($this->success->encode());
        $this->success->respond();
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testRespondHasCorrectContentType(): void
    {
        $this->expectOutputString($this->success->encode());
        $this->success->respond();
        $headers = xdebug_get_headers();

        self::assertNotEmpty($headers);
        self::assertContains('Content-Type: application/json', $headers);
    }

    public function testExtending(): void
    {
        $extended = Extended::success();
        self::assertInstanceOf('Extended', $extended);
    }

    public function testAddingEncodeOptions(): void
    {
        $success = JSendResponse::success(array('some' => 'data'));
        $success->setEncodingOptions(\JSON_PRETTY_PRINT);
        $result = $success->encode();
        $pretty = json_encode($success->asArray(), \JSON_PRETTY_PRINT);
        self::assertEquals($pretty, $result);
    }

}

class Extended extends JSendResponse
{

}