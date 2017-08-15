<?php

namespace Silktide\QueueBall\Sqs\Test;

use PHPUnit\Framework\TestCase;
use Silktide\QueueBall\Exception\QueueException;
use Silktide\QueueBall\Sqs\MessageFactory;


class MessageFactoryTest extends TestCase
{
    public function testExceptions()
    {
        $factory = new MessageFactory();

        // empty queue or malformed data
        $data = [];
        $queueId = "queue";
        $this->assertNull($factory->createMessage($data, $queueId));

        // missing data
        $messageId = "id";
        $body = "body";
        $receiptId = "receiptId";

        $data = [
            "Messages" => [
                [
                    "MessageId" => $messageId,
                    "Body" => $body,
                    "ReceiptHandle" => $receiptId
                ]
            ]
        ];
        foreach ($data["Messages"][0] as $field => $value) {
            $testData = $data;
            unset($testData["Messages"][0][$field]);
            try {
                $factory->createMessage($testData, $queueId);
                $this->fail("Should not be able to create a message without data for '$field'");
            } catch (QueueException $e) {
                $this->assertEquals("SQS message has missing information", $e->getMessage());
            }
        }

    }

    public function testRequiredDataMapping()
    {
        $factory = new MessageFactory();

        $expected = [
            "QueueId" => "queue",
            "Id" => "id",
            "Message" => "body",
            "ReceiptId" => "receiptId"
        ];

        $data = [
            "Messages" => [
                [
                    "MessageId" => $expected["Id"],
                    "Body" => json_encode($expected["Message"]),
                    "ReceiptHandle" => $expected["ReceiptId"]
                ]
            ]
        ];

        $queueMessage = $factory->createMessage($data, $expected["QueueId"]);
        $this->assertInstanceOf("Silktide\\QueueBall\\Message\\QueueMessage", $queueMessage);

        foreach ($expected as $property => $value) {
            $this->assertEquals($value, $queueMessage->{"get" . $property}());
        }
    }

    /**
     * @dataProvider attributeProvider
     *
     * @param $messageData
     * @param $expected
     */
    public function testAttributeMapping($messageData, $expected)
    {
        $factory = new MessageFactory();

        $data = [
            "Messages" => [
                [
                    "MessageId" => "blah",
                    "Body" => "\"blah\"",
                    "ReceiptHandle" => "blah"
                ]
            ]
        ];

        $data["Messages"][0] = array_merge($data["Messages"][0], $messageData);

        $queueMessage = $factory->createMessage($data, "queue");
        $this->assertEquals($expected, $queueMessage->getAttributes());

    }

    public function attributeProvider()
    {
        return [
            [
                [],
                []
            ],
            [
                [
                    "Attributes" => [1, 2, 3]
                ],
                [1, 2, 3]
            ],
            [
                [
                    "MessageAttributes" => [4, 5, 6]
                ],
                [4, 5, 6]
            ],
            [
                [
                    "Attributes" => ["one" => 1, "two" => 2, "three" => 3],
                    "MessageAttributes" => ["two" => 22, "four" => 4]
                ],
                ["one" => 1, "two" => 22, "three" => 3, "four" => 4]
            ]
        ];
    }

}
 