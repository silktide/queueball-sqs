<?php

namespace Silktide\QueueBall\Sqs;

use Aws\AwsClient;
use Aws\Sqs\SqsClient;
use Silktide\QueueBall\Message\QueueMessage;
use Silktide\QueueBall\Queue\AbstractQueue;
use Silktide\QueueBall\Exception\QueueException;
use Silktide\QueueBall\Message\QueueMessageFactoryInterface;

/**
 *
 */
class Queue extends AbstractQueue
{

    /**
     * @var string
     */
    protected $queueUrl;

    /**
     * @var SqsClient
     */
    protected $queueClient;

    /**
     * @var QueueMessageFactoryInterface
     */
    protected $messageFactory;

    /**
     * @var int
     */
    protected $waitTime = 20;

    /**
     * @param SqsClient $sqsClient
     * @param QueueMessageFactoryInterface $messageFactory
     * @param string|null $queueId
     */
    public function __construct(SqsClient $sqsClient, QueueMessageFactoryInterface $messageFactory, $queueId = null)
    {
        parent::__construct($queueId);
        $this->queueClient = $sqsClient;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function setQueueId($queueId)
    {
        parent::setQueueId($queueId);
        $this->queueUrl = null;
    }

    /**
     * @param int $seconds
     * @throws \Exception
     */
    public function setWaitTime($seconds)
    {
        $seconds = (int) $seconds;
        if ($seconds < 0 || 20 < $seconds) {
            throw new \Exception("WaitTime must be a period between 0-20 seconds");
        }
        $this->waitTime = $seconds;
    }

    /**
     * @param string $queueId
     * @return string
     */
    protected function getQueueUrl($queueId)
    {
        if (empty($this->queueUrl)) {
            if (empty($queueId)) {
                $queueId = $this->getQueueId();
            }
            $response = $this->queueClient->getQueueUrl(["QueueName" => $queueId]);
            $this->queueUrl = $response->get("QueueUrl");
        }
        return $this->queueUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($queueId, $messageLockTimeout = 0, $options = [])
    {
        $timeout = (int) $messageLockTimeout;
        $attributes = [
            "VisibilityTimeout" => empty($timeout)? self::DEFAULT_MESSAGE_LOCK_TIMEOUT: $timeout
        ];
        $this->queueClient->createQueue([
            "QueueName" => $queueId,
            "Attributes" => $attributes
        ]);
        $this->setQueueId($queueId);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteQueue($queueId = null)
    {
        $queueUrl = $this->getQueueUrl($queueId);
        $this->queueClient->deleteQueue(["QueueUrl" => $queueUrl]);
    }

    /**
     * {@inheritDoc}
     */
    public function sendMessage($messageBody, $queueId = null)
    {
        $queueUrl = $this->getQueueUrl($queueId);
        $this->queueClient->sendMessage([
            "QueueUrl" => $queueUrl,
            "MessageBody" => json_encode($messageBody)
        ]);
    }

    /**
     * This can be used to send up to 10 entries and up to a total of 264kb
     *
     * {@inheritDoc}
     */
    public function sendMessageBatch($messageBodies=[], $queueId=null)
    {
        if (!is_array($messageBodies)) {
            throw new \Exception("MessageBodies must be an array");
        }

        $queueUrl = $this->getQueueUrl($queueId);
        $entries = [];
        foreach ($messageBodies as $key => $body) {
            $entries[] = [
                "Id" => $key,
                "MessageBody" => json_encode($body)
            ];
        }

        $this->queueClient->sendMessageBatch([
            "QueueUrl" => $queueUrl,
            "Entries" => $entries
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function receiveMessage($queueId = null, $waitTime = null)
    {
        if (empty($queueId)) {
            // have to do this here as we need the ID later in this method
            $queueId = $this->getQueueId();
        }
        $queueUrl = $this->getQueueUrl($queueId);
        $message = $this->queueClient->receiveMessage([
            "QueueUrl" => $queueUrl,
            "WaitTimeSeconds" => (isset($waitTime) ? $waitTime : $this->waitTime)
        ]);
        return $this->messageFactory->createMessage($message->toArray(), $queueId);

    }

    /**
     * {@inheritDoc}
     */
    public function completeMessage(QueueMessage $message)
    {
        $queueUrl = $this->getQueueUrl($message->getQueueId());
        $this->queueClient->deleteMessage([
            "QueueUrl" => $queueUrl,
            "ReceiptHandle" => $message->getReceiptId()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function returnMessage(QueueMessage $message)
    {
        $queueUrl = $this->getQueueUrl($message->getQueueId());
        $this->queueClient->changeMessageVisibility([
            "QueueUrl" => $queueUrl,
            "ReceiptHandle" => $message->getReceiptId(),
            "VisibilityTimeout" => 0
        ]);
    }

} 