<?php

namespace Silktide\QueueBall\Sqs\Middleware;

/**
 * Class MiddlewareGroup
 * @package Silktide\QueueBall\Sqs\Middleware
 *
 * In reality, the way we're expecting this to be used is like so:
 *
 * Request:
 * 1. json_encode the response
 * 2. look to see if the response is bigger than 256k, if so, upload it to s3 and change the message to link to that url
 *
 * Response:
 * 1. Look to see if it matches something like `s3:bucket:key`, if so, download the response from that
 * 2. json_decode the response
 */
class MiddlewareGroup implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    protected $middleware;

    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function request($body)
    {
        foreach ($this->middleware as $middleware) {
            $body = $middleware->request($body);
        }

        return $body;
    }

    public function response($body)
    {
        foreach (array_reverse($this->middleware) as $middleware) {
            /**
             * @var MiddlewareInterface $middleware
             */
            $body = $middleware->response($body);
        }

        return $body;
    }

}