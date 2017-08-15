<?php

namespace Silktide\QueueBall\Sqs\Test\Middleware;

use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Silktide\QueueBall\Sqs\Middleware\JsonMiddleware;
use Silktide\QueueBall\Sqs\Middleware\LargeFileMiddleware;
use Silktide\QueueBall\Sqs\Middleware\MiddlewareGroup;
use Silktide\QueueBall\Sqs\Middleware\MiddlewareInterface;

class MiddlewareGroupTest extends TestCase
{
    /**
     * @var MiddlewareGroup
     */
    protected $middleware;

    public function setUp()
    {
        // First will add the word foo to the start of a string, and remove it if it's there
        $first = new class implements MiddlewareInterface {

            public function request($body)
            {
                return "foo " . $body;
            }

            public function response($body)
            {
                if (strpos($body, "foo ")===0) {
                    return substr($body, 4);
                }

                return $body;
            }

        };

        // First will add the word bar to the start of a string, and remove it if it's there
        $second = new class implements MiddlewareInterface {
            public function request($body)
            {
                return "bar " . $body;
            }

            public function response($body)
            {
                if (strpos($body, "bar ")===0) {
                    return substr($body, 4);
                }

                return $body;
            }

        };

        $this->middleware = new MiddlewareGroup([$first, $second]);
    }

    public function testRequest()
    {
        $this->assertEquals(
            "bar foo sandwich",
            $this->middleware->request("sandwich")
        );
    }

    public function testResponse()
    {
        $this->assertEquals(
            "sandwich",
            $this->middleware->response("bar foo sandwich")
        );
    }
}