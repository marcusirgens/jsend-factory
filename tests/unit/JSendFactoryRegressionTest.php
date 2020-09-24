<?php

declare(strict_types=1);

namespace MarcusIrgens\JSendFactory;

use Http\Factory\Guzzle\ResponseFactory as GuzzleResponseFactory;
use Http\Factory\Guzzle\StreamFactory as GuzzleStreamFactory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactory;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Psr7\Factory\StreamFactory as SlimStreamFactory;

/**
 * None of these tests may be changed without changing the major version of the
 * package. New test may be added.
 *
 * @covers \MarcusIrgens\JSendFactory\JSendFactory
 */
class JSendFactoryRegressionTest extends TestCase
{
    public function testConstructor()
    {
        $this->expectNotToPerformAssertions();

        new JSendFactory(new NyholmFactory(), new NyholmFactory());
    }

    public function subjectProvider()
    {
        yield "nyholm/psr7" => [new JSendFactory(new NyholmFactory(), new NyholmFactory())];
        yield "slim/psr7" => [new JSendFactory(new SlimResponseFactory(), new SlimStreamFactory())];
        yield "guzzle/psr7" => [new JSendFactory(new GuzzleResponseFactory(), new GuzzleStreamFactory())];
    }

    /**
     * @dataProvider subjectProvider
     * @param JSendFactory $factory
     */
    public function testSuccessApi(JSendFactory $factory)
    {
        $success = $factory->getSuccess(
            [
                "name" => "Alice",
                "knows" => "Bob",
                "met_at" => \DateTimeImmutable::createFromFormat(
                    \DateTimeInterface::RFC3339_EXTENDED,
                    "2020-09-24T13:15:30.001+00:00"
                ),
            ]
        );

        $this->assertEquals(200, $success->getStatusCode());

        $body = $success->getBody();
        $this->assertJson((string)$body);
        $jsendBody = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey("data", $jsendBody);
        $this->assertIsArray($jsendBody);
        $this->assertArrayHasKey("met_at", $jsendBody["data"]);
        $this->assertEquals("2020-09-24T13:15:30.001+00:00", $jsendBody["data"]["met_at"]);
        $this->assertArrayHasKey("status", $jsendBody);
        $this->assertEquals("success", $jsendBody["status"]);
    }

    /**
     * @dataProvider subjectProvider
     * @param JSendFactory $factory
     */
    public function testFailApi(JSendFactory $factory)
    {
        $success = $factory->getFail(
            [
                "name" => "Alice",
                "knows" => "Bob",
                "met_at" => \DateTimeImmutable::createFromFormat(
                    \DateTimeInterface::RFC3339_EXTENDED,
                    "2020-09-24T13:15:30.001+00:00"
                ),
            ]
        );

        $this->assertEquals(400, $success->getStatusCode());

        $body = $success->getBody();
        $this->assertJson((string)$body);
        $jsendBody = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey("data", $jsendBody);
        $this->assertIsArray($jsendBody);
        $this->assertArrayHasKey("met_at", $jsendBody["data"]);
        $this->assertEquals("2020-09-24T13:15:30.001+00:00", $jsendBody["data"]["met_at"]);
        $this->assertArrayHasKey("status", $jsendBody);
        $this->assertEquals("fail", $jsendBody["status"]);
    }

    /**
     * @dataProvider subjectProvider
     * @param JSendFactory $factory
     */
    public function testErrorApi(JSendFactory $factory)
    {
        $success = $factory->getError(
            "something is wrong",
            5,
            [
                "name" => "Alice",
                "knows" => "Bob",
                "met_at" => \DateTimeImmutable::createFromFormat(
                    \DateTimeInterface::RFC3339_EXTENDED,
                    "2020-09-24T13:15:30.001+00:00"
                ),
            ]
        );

        $this->assertEquals(500, $success->getStatusCode());

        $body = $success->getBody();
        $this->assertJson((string)$body);
        $jsendBody = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey("data", $jsendBody);
        $this->assertIsArray($jsendBody);
        $this->assertArrayHasKey("met_at", $jsendBody["data"]);
        $this->assertEquals("2020-09-24T13:15:30.001+00:00", $jsendBody["data"]["met_at"]);
        $this->assertArrayHasKey("status", $jsendBody);
        $this->assertEquals("error", $jsendBody["status"]);
    }
}
