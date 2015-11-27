<?php

namespace Silktide\QueueBall\Sqs;

use Silktide\QueueBall\Exception\QueueException;

/**
 *
 */
class MessageFactory implements QueueMessageFactoryInterface
{

    /**
     * {@inheritDoc}
     * @return null|QueueMessage
     * @throws QueueException
     */
    public function createMessage($message, $queueId)
    {
        if (!is_array($message) || !isset($message["Messages"][0])) {
            return null;
        }
        $message = $message["Messages"][0];

        if (empty($message["MessageId"]) || empty($message["Body"]) || empty($message["ReceiptHandle"])) {
            throw new QueueException("SQS message has missing information");
        }

        $queueMessage = new QueueMessage();
        $queueMessage->setId($message["MessageId"]);
        $queueMessage->setMessage(json_decode($message["Body"], true));
        $queueMessage->setReceiptId($message["ReceiptHandle"]);
        $queueMessage->setQueueId($queueId);

        if (!empty($message["Attributes"]) || !empty($message["MessageAttributes"])) {
            $attributes = empty($message["Attributes"])? []: $message["Attributes"];
            $attributes = array_merge($attributes, empty($message["MessageAttributes"])? []: $message["MessageAttributes"]);
            $queueMessage->setAttributes($attributes);
        }

        return $queueMessage;
    }

} 