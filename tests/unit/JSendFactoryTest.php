<?php

declare(strict_types=1);

namespace MarcusIrgens\JSendFactory;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @covers \MarcusIrgens\JSendFactory\JSendFactory
 */
class JSendFactoryTest extends TestCase
{
    private function getStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    private function getTestSubject(): JSendFactory
    {
        return new JSendFactory($this->getResponseFactory(), $this->getStreamFactory());
    }

    /**
     * @return array
     */
    private function getResponseContents(ResponseInterface $response): array
    {
        $type = $response->getHeaderLine("content-type");
        if (strpos($type, "application/json") === false) {
            throw new \Exception("Invalid response type");
        }
        $body = $response->getBody();
        return json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
    }


    public function testSuccessExpectsAWellFormattedObject()
    {
        $obj = new class () {
        };

        $this->expectException(\TypeError::class);

        $this->getTestSubject()->getSuccess($obj);
    }

    public function testFailExpectsAWellFormattedObject()
    {
        $obj = new class () {
        };

        $this->expectException(\TypeError::class);

        $this->getTestSubject()->getFail($obj);
    }

    public function testErrorExpectsAWellFormattedObject()
    {
        $obj = new class () {
        };

        $this->expectException(\TypeError::class);

        $this->getTestSubject()->getError("Test", 0, $obj);
    }

    public function testErrorMessageMustBeANonEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $err = $this->getTestSubject()->getError(" ");
    }

    public function testErrorWithData()
    {
        $error = $this->getTestSubject()->getError("something is wrong", 0, ["data" => true]);
        $contents = $this->getResponseContents($error);
        $this->assertJSend($contents);
    }

    public function testStatusCodes()
    {
        $success = $this->getTestSubject()->getSuccess([]);
        $error = $this->getTestSubject()->getError("error message");
        $fail = $this->getTestSubject()->getFail([]);

        $successConts = $this->getResponseContents($success);
        $errorConts = $this->getResponseContents($error);
        $failConts = $this->getResponseContents($fail);

        $this->assertJSend($successConts);
        $this->assertJSend($errorConts);
        $this->assertJSend($failConts);

        $this->assertEquals("success", $successConts["status"]);
        $this->assertEquals("error", $errorConts["status"]);
        $this->assertEquals("fail", $failConts["status"]);
    }

    public function testHandlesAJsonSerializableObject()
    {
        $obj = new class () implements \JsonSerializable {
            public function jsonSerialize()
            {
                return ["message" => "success message"];
            }
        };

        $response = $this->getTestSubject()->getSuccess($obj);
        $contents = $this->getResponseContents($response);

        $this->assertJSend($contents);
        $this->assertArrayHasKey("message", $contents["data"]);
        $this->assertEquals("success message", $contents["data"]["message"]);
    }

    public function testCanUseAnExceptionAsError()
    {
        $exception = new \LogicException("Something went wrong", 5);

        $err = $this->getTestSubject()->getErrorFromThrowable($exception);
        $contents = $this->getResponseContents($err);
        $this->assertJSend($contents);
    }

    public function testRefusesNonArrayJsonSerializables()
    {
        $obj = new class () implements \JsonSerializable {
            public function jsonSerialize()
            {
                return "this should fail";
            }
        };

        $this->expectException(\TypeError::class);

        $send = $this->getTestSubject()->getSuccess($obj);
    }

    /**
     * @param array $contents
     * @psalm-assert array{status: string, data: mixed[]}
     *               |array{status: string, message: string, code?: int, data?: mixed[]}
     */
    private function assertJSend(array $contents): void
    {
        $this->assertIsArray($contents);
        $this->assertArrayHasKey("status", $contents);
        $this->assertIsString($contents["status"]);
        $this->assertThat($contents["status"], $this->logicalOr(
            $this->equalTo("success"),
            $this->equalTo("fail"),
            $this->equalTo("error")
        ));

        if ($contents["status"] === "error") {
            if (array_key_exists("code", $contents)) {
                $this->assertIsInt($contents["code"]);
            }
            $this->assertArrayHasKey("message", $contents);
            $this->assertIsString($contents["message"]);
            $this->assertThat(trim($contents["message"]), $this->logicalNot($this->equalTo("")));
        }

        if ($contents["status"] !== "error" || array_key_exists("data", $contents)) {
            $this->assertArrayHasKey("data", $contents);
            $this->assertIsArray($contents["data"]);
        }
    }
}
