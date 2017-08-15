<?php

namespace Silktide\QueueBall\Sqs\Test\Middleware;

use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Silktide\QueueBall\Sqs\Middleware\LargeFileMiddleware;

class LargeFileMiddlewareTest extends TestCase
{
    /**
     * @var MockInterface
     */
    protected $s3Client;
    /**
     * @var LargeFileMiddleware
     */
    protected $largeFileMiddleware;

    public function setUp()
    {
        $this->s3Client = \Mockery::mock(S3Client::class);

        $this->largeFileMiddleware = new LargeFileMiddleware(
            $this->s3Client,
            "fake.bucket",
            "my_prefix",
            10 // 10 bytes as we're maniacs!
        );
    }

    public function testShortRequest()
    {
        // Short strings should work
        $string = "Short";
        $response = $this->largeFileMiddleware->request($string);
        $this->assertEquals($string, $response);

        // Slightly longer strings should too
        $string = "10chars___";
        $response = $this->largeFileMiddleware->request($string);
        $this->assertEquals($string, $response);
    }

    public function testLongRequest()
    {
        $string = "Lots and lots of chars. Way over 10" ;
        $this->s3Client->shouldReceive("putObject")->atLeast()->times(1);

        $response = $this->largeFileMiddleware->request($string);
        $this->assertRegExp("/^s3:.*$/", $response);
    }

    public function testRequestMultibyte()
    {
        // If unicode is the 10th char, it should realise that it's over the size limit and try to use s3Client
        $string = "10chars__\u{4ee0}" ;
        $this->s3Client->shouldReceive("putObject")->atLeast()->times(1);

        $response = $this->largeFileMiddleware->request($string);
        $this->assertRegExp("/^s3:.*$/", $response);
    }

    public function testShortResponse()
    {
        $string = "Some Normal Looking Content";
        $response = $this->largeFileMiddleware->response($string);
        $this->assertEquals($string, $response);
    }

    public function testLongResponse()
    {
        $string = "s3:fake.bucket:my_prefix/6b7c608250caf9d85e683669da97f465";
        $contents = "Very Long Response that had to be stored in S3";

        $streamResource = fopen('php://memory','r+');
        fwrite($streamResource, $contents);
        rewind($streamResource);

        // Technically violates the unit-testing-ness, but I'll live with myself
        $stream = new Stream($streamResource);

        $fakedResponse = \Mockery::mock(Result::class)
            ->shouldReceive("get")
            ->atLeast()
            ->times(1)
            ->withArgs(["Body"])
            ->times(1)
            ->andReturn($stream)
            ->getMock();

        $this->s3Client->shouldReceive("getObject")->atLeast()->times(1)->withArgs([
           [
               "Bucket" => "fake.bucket",
               "Key" => "my_prefix/6b7c608250caf9d85e683669da97f465"
           ]
        ])->andReturn($fakedResponse);

        $response = $this->largeFileMiddleware->response($string);
        $this->assertEquals($contents, $response);
    }
}