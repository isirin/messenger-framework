<?php

namespace Framework\Test;

use Framework\FacebookBot\FacebookBot;
use Framework\FacebookBot\TextMessageBuilder;
use Framework\FacebookBot\GenericMessageBuilder;
use Framework\FacebookBot\AttachmentMessageBuilder;
use Framework\HttpClient\Curl;
use PHPUnit\Framework\TestCase;

class FacebookBotTest extends TestCase {

  private $curlMock;

  public function setUp() {
    $this->curlMock = null;
    $this->curlMock = $this->getMockBuilder(Curl::class)
      ->setMethods(['post'])
      ->getMock();
      /*
        postのモックについて
        with(
          $url,
          $headers,
          $bodyArray,
          $isJSON
        )
      */
  }

  public function testReplyTextMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'text' => 'テスト'
          ]
        ]),
        $this->equalTo(true)
      );
    $bot = new FacebookBot($this->curlMock);
    $builder = new TextMessageBuilder('テスト');
    $bot->replyMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testReplyGenericMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'attachment' => [
              'type' => 'template',
              'payload' => [
                'template_type' => 'generic',
                'elements' => [
                    [
                    'title' => 'タイトル1',
                    'subtitle' => 'サブタイトル1',
                    'buttons' => [
                      [
                        'type' => 'web_url',
                        'url' => 'https://www.sampleimage.com/sample.jpg',
                        'title' => 'URLボタン'
                      ],
                      [
                        'type' => 'postback',
                        'title' => 'Postbackボタン',
                        'payload' => 'key1=value1&key2=value2'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]),
        $this->equalTo(true)
    );

    $bot = new FacebookBot($this->curlMock);
    $builder = new GenericMessageBuilder([
      [
        'タイトル1', 'サブタイトル1', null, [
          'title' => 'URLボタン',
          'action' => 'url',
          'url' => 'https://www.sampleimage.com/sample.jpg'
        ], [
          'title' => 'Postbackボタン',
          'action' => 'postback',
          'data' => 'key1=value1&key2=value2'
        ]
      ]
    ]);
    $bot->replyMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testReplyAttachmentMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'attachment' => [
              'type' => 'image',
              'payload' => [
                'url' => 'https://www.sampleimage.com/sample.jpg'
              ]
            ]
          ]
        ]),
        $this->equalTo(true)
      );
    $bot = new FacebookBot($this->curlMock);
    $builder = new AttachmentMessageBuilder('image', 'https://www.sampleimage.com/sample.jpg');
    $bot->replyMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testPushTextMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'text' => 'テスト'
          ]
        ]),
        $this->equalTo(true)
      );
    $bot = new FacebookBot($this->curlMock);
    $builder = new TextMessageBuilder('テスト');
    $bot->pushMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testPushGenericMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'attachment' => [
              'type' => 'template',
              'payload' => [
                'template_type' => 'generic',
                'elements' => [
                    [
                    'title' => 'タイトル1',
                    'subtitle' => 'サブタイトル1',
                    'buttons' => [
                      [
                        'type' => 'web_url',
                        'url' => 'https://www.sampleimage.com/sample.jpg',
                        'title' => 'URLボタン'
                      ],
                      [
                        'type' => 'postback',
                        'title' => 'Postbackボタン',
                        'payload' => 'key1=value1&key2=value2'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]),
        $this->equalTo(true)
    );

    $bot = new FacebookBot($this->curlMock);
    $builder = new GenericMessageBuilder([
      [
        'タイトル1', 'サブタイトル1', null, [
          'title' => 'URLボタン',
          'action' => 'url',
          'url' => 'https://www.sampleimage.com/sample.jpg'
        ], [
          'title' => 'Postbackボタン',
          'action' => 'postback',
          'data' => 'key1=value1&key2=value2'
        ]
      ]
    ]);
    $bot->pushMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testPushAttachmentMessage() {
    $this->curlMock->expects($this->once())
      ->method('post')
      ->with(
        $this->equalTo('https://graph.facebook.com/v2.6/me/messages?access_token=develop'),
        $this->equalTo(null),
        $this->equalTo([
          'recipient' => [
            'id' => '1000000000000000'
          ],
          'message' => [
            'attachment' => [
              'type' => 'image',
              'payload' => [
                'url' => 'https://www.sampleimage.com/sample.jpg'
              ]
            ]
          ]
        ]),
        $this->equalTo(true)
      );
    $bot = new FacebookBot($this->curlMock);
    $builder = new AttachmentMessageBuilder('image', 'https://www.sampleimage.com/sample.jpg');
    $bot->pushMessage('1000000000000000', $builder);
    $this->addToAssertionCount(1);
  }

  public function testParseEvents() {
    $bot = new FacebookBot($this->curlMock);
    $jsonString = '{"object":"page","entry":[{"id":"000000000000000","time":1495206000000,"messaging":[{"sender":{"id":"1000000000000000"},"recipient":{"id":"200000000000000"},"timestamp":1495207800000,"message":{"mid":"mid.$cAADj4thus55iSabc123DEFghi45j","seq":1000,"text":"\u3066\u3059\u3068\u3066\u3059\u3068"}}]}]}';
    $this->assertEquals(\json_decode($jsonString), $bot->parseEvents($jsonString));
  }

  public function testTestSignature() {
    $bot = new FacebookBot($this->curlMock);
    $jsonString = '{"object":"page","entry":[{"id":"000000000000000","time":1495206000000,"messaging":[{"sender":{"id":"1000000000000000"},"recipient":{"id":"200000000000000"},"timestamp":1495207800000,"message":{"mid":"mid.$cAADj4thus55iSabc123DEFghi45j","seq":1000,"text":"\u3066\u3059\u3068\u3066\u3059\u3068"}}]}]}';
    $x_hub_signature = 'sha1=' . hash_hmac('sha1', $jsonString, 'develop');
    $this->assertTrue($bot->testSignature($jsonString, $x_hub_signature));
  }

}