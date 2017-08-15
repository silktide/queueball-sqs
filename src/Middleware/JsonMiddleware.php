<?php

namespace Silktide\QueueBall\Sqs\Middleware;

class JsonMiddleware implements MiddlewareInterface
{
    public function request($body)
    {
        return json_encode($body);
    }

    public function response($body)
    {
        $response = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Unable to decode provided JSON. '".json_last_error_msg()."'. Len: ".strlen($body));
        }

        return $response;
    }

}