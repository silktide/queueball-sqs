<?php

namespace Silktide\QueueBall\Sqs\Test\Middleware;

use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Silktide\QueueBall\Sqs\Middleware\JsonMiddleware;
use Silktide\QueueBall\Sqs\Middleware\LargeFileMiddleware;

class JsonMiddlewareTest extends TestCase
{
    /**
     * @var JsonMiddleware
     */
    protected $jsonMiddleware;

    public function setUp()
    {
        $this->jsonMiddleware = new JsonMiddleware();
    }

    public function testRequest()
    {
        $this->assertEquals(
            '{"This is an array":"of stuff"}',
            $this->jsonMiddleware->request(["This is an array" => "of stuff"])
        );
    }

    public function testResponse()
    {
        $this->assertEquals(
            ["This is an array" => "of stuff"],
            $this->jsonMiddleware->response('{"This is an array":"of stuff"}')
        );
    }

    public function testInvalidResponse()
    {
        $this->expectException(\Exception::class);
        $this->jsonMiddleware->response('{"This is an invalid JSON string"}');
    }
}