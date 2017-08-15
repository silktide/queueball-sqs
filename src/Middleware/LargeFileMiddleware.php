<?php

namespace Silktide\QueueBall\Sqs\Middleware;

use Aws\S3\S3Client;

class LargeFileMiddleware implements MiddlewareInterface
{
    protected $s3Client;
    protected $bucket;
    protected $prefix;
    protected $sizeThreshold;

    public function __construct(S3Client $s3Client, string $bucket, string $prefix, int $sizeThreshold)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->sizeThreshold = $sizeThreshold;
    }

    public function request($body)
    {
        if (!is_string($body)) {
            throw new \Exception("Expected to receive string to LargeFileMiddleware, received '".gettype($body)."'");
        }

        if (strlen($body) <= $this->sizeThreshold) {
            return $body;
        }

        $key = $this->prefix . "/" . md5(uniqid());

        $this->s3Client->putObject([
            "Bucket" => $this->bucket,
            "Key" => $key
        ]);

        return "s3:{$this->bucket}:{$key}";
    }

    public function response($body)
    {
        if (!preg_match("/^s3:(.*):(.*)$/", $body, $matches)) {
            return $body;
        }

        list(, $bucket, $key) = $matches;

        $response = $this->s3Client->getObject([
            "Bucket" => $bucket,
            "Key" => $key
        ]);

        return (string)$response->get("Body");
    }
}
