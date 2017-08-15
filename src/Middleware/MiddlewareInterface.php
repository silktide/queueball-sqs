<?php

namespace Silktide\QueueBall\Sqs\Middleware;

interface MiddlewareInterface
{
    public function request($body);

    public function response($body);
}