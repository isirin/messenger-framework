<?php

namespace  MessengerFramework;

use  MessengerFramework\HttpClient\Curl;
use  MessengerFramework\FacebookBot\FacebookBot;
use  MessengerFramework\LineBot\LineBot;

use  MessengerFramework\FacebookBot as FB;
use  MessengerFramework\LineBot as Line;

use MessengerFramework\Event;

// MessengerBotクラスを使う側が何のBotかは最初に指定して使うのでリクエストから判別するような機能はいらない
class MessengerBot {

  private $requestBody = '';

  private $type = '';

  private $httpClient;

  public $core;

  private $messageWillSent = [];

  public function __construct($botType) {
    $this->requestBody = file_get_contents("php://input");
    $this->type = strtolower($botType);

    switch ($this->type) {
      case 'facebook' :
      $this->core = new FacebookBot(new Curl());
      break;
      case 'line' :
      $this->core = new LineBot(new Curl());
      break;
      default :
      throw new \InvalidArgumentException("指定されたプラットフォームはサポートされていません。", 1);
    }

  }

  public function getEvents() {
    if (!$this->validateSignature()) {
      throw new \UnexpectedValueException("正しい送信元からのリクエストではありません。");
    }

    $rawEvents = $this->core->parseEvents($this->requestBody);
    switch ($this->type) {
      case 'facebook':
      return self::convertFacebookEvents($rawEvents);
      case 'line':
      return self::convertLineEvents($rawEvents);
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function reply(String $replyToken) {
    switch ($this->type) {
      case 'facebook' :
      $responses = [];
      foreach ($this->messageWillSent as $message) {
        try {
          $res = $this->core->replyMessage($replyToken, $message);
          array_push($responses, json_decode($res));
        } catch (\RuntimeException $e) {
          array_push($responses, self::buildCurlErrorResponse($e));
        }
      }
      $this->messageWillSent = [];
      return \json_encode($responses);
      break;
      case 'line' :
      $multiMessage = new Line\MultiMessageBuilder();
      foreach ($this->messageWillSent as $message) {
        $multiMessage->add($message);
      }
      try {
        $res = $this->core->replyMessage($replyToken, $multiMessage);
      } catch (\RuntimeException $e) {
          $this->messageWillSent = [];
          return self::buildCurlErrorResponse($e);
      }
      $this->messageWillSent = [];
      return $res;
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function push(String $recipientId) {
    switch ($this->type) {
      case 'facebook' :
      $responses = [];
      foreach ($this->messageWillSent as $message) {
        try {
          $res = $this->core->pushMessage($recipientId, $message);
          array_push($responses, json_decode($res));
        } catch (\RuntimeException $e) {
          array_push($responses, self::buildCurlErrorResponse($e));
        }
      }
      $this->messageWillSent = [];
      return \json_encode($responses);
      break;
      case 'line' :
      $multiMessage = new Line\MultiMessageBuilder();
      foreach ($this->messageWillSent as $message) {
        $multiMessage->add($message);
      }
      try {
        $res = $this->core->replyMessage($replyToken, $multiMessage);
      } catch (\RuntimeException $e) {
          $this->messageWillSent = [];
          return self::buildCurlErrorResponse($e);
      }
      $this->messageWillSent = [];
      return $res;
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function addText(String $message) {
    switch ($this->type) {
      case 'facebook' :
      array_push($this->messageWillSent, new FB\TextMessageBuilder($message));
      break;
      case 'line' :
      array_push($this->messageWillSent, new Line\TextMessageBuilder($message));
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function addTemplate(Array $columns) {
    switch ($this->type) {
      case 'facebook' :
      array_push($this->messageWillSent, new FB\GenericMessageBuilder($columns));
      break;
      case 'line' :
      array_push($this->messageWillSent, new Line\CarouselMessageBuilder($columns));
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function addImage(String $fileUrl, String $previewUrl = null) {
    switch ($this->type) {
      case 'facebook' :
      array_push($this->messageWillSent, new FB\AttachmentMessageBuilder('image', $fileUrl));
      break;
      case 'line' :
      array_push($this->messageWillSent, new Line\FileMessageBuilder('image', $fileUrl, $previewUrl));
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function addVideo(String $fileUrl, String $previewUrl = null) {
    switch ($this->type) {
      case 'facebook' :
      array_push($this->messageWillSent, new FB\AttachmentMessageBuilder('video', $fileUrl));
      break;
      case 'line' :
      array_push($this->messageWillSent, new Line\FileMessageBuilder('video', $fileUrl, $previewUrl));
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function addAudio(String $fileUrl, String $previewUrl = null) {
    switch ($this->type) {
      case 'facebook' :
      array_push($this->messageWillSent, new FB\AttachmentMessageBuilder('audio', $fileUrl));
      break;
      case 'line' :
      array_push($this->messageWillSent, new Line\FileMessageBuilder('audio', $fileUrl, $previewUrl));
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  // どのプラットフォームのイベントかはMessengerBotの状態に依存する
  // 渡されたメッセージがFacebookのものであってもnew MessengerBot('line')だったらLineとして解釈
  public function getFilesIn($message) {
    switch ($this->type) {
      case 'facebook' :
      break;
      case 'line' :
      break;
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  public function getProfile($userId) {
    $profile = $this->core->getProfile($userId);
    switch ($this->type) {
      case 'facebook' :
      if (!isset($profile->first_name)) {
        throw new \UnexpectedValueException('プロフィールが取得できませんでした。');
      }
      return [
        'name' => $profile->first_name . ' ' . $profile->last_name,
        'profilePic' => $profile->profile_pic
      ];
      case 'line' :
      if (!isset($profile->displayName)) {
        throw new \UnexpectedValueException('プロフィールが取得できませんでした。');
      }
      return [
        'name' => $profile->displayName,
        'profilePic' => $profile->pictureUrl
      ];
      default :
      throw new \LogicException('仕様からここが実行されることはありえません。');
    }
  }

  private function validateSignature() {
    switch ($this->type) {
      case 'facebook' :
      $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? 'invalid';
      break;
      case 'line' :
      $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? 'invalid';
      break;
      default :
      break;
    }

    return $this->core->testSignature($this->requestBody, $signature);
  }

  private static function convertFacebookEvents($rawEvents) {
    $events = [];

    // 最下層まで展開してイベントとしての判断ができない時はからの配列を返す
    if (!isset($rawEvents->entry) || !is_array($rawEvents->entry)) {
      throw new \UnexpectedValueException('Entryがない、またはEntryがサポートされていない形式です。');
    }

    foreach ($rawEvents->entry as $entry) {

      // 最下層まで展開してイベントとしての判断ができない時はからの配列を返す
      if (!isset($entry->messaging) || !is_array($entry->messaging)) {
        throw new \UnexpectedValueException('Messagingがない、またはMessagingがサポートされていない形式です。');
      }

      foreach ($entry->messaging as $messaging) {
        try {
          $event = self::parseMessaging($messaging);
          array_push($events, $event);
        } catch (\InvalidArgumentException $e) {
          array_push($events, null);
        }
      }
    }

    return $events;
  }

  private static function parseMessaging($messaging) {
    $text = null;
    $postbackData = null;
    if (isset($messaging->message)) {
      if (isset($messaging->message->attachments)) {
        $type = 'Message.File';
      } elseif (isset($messaging->message->text)) {
        $type = 'Message.Text';
        $text = $messaging->message->text;
      } else {
        throw new \InvalidArgumentException('サポートされていない形式のMessaging#Messageです。');
      }
    } elseif (isset($messaging->postback)) {
      $type = 'Postback';
      $postbackData = $messaging->postback->payload;
    } else {
      throw new \InvalidArgumentException('サポートされていない形式のMessagingです。');
    }
    $userId = $messaging->sender->id;
    $replyToken = $messaging->sender->id;
    $rawData = $messaging;
    return new Event($replyToken, $userId, $type, $rawData, $text, $postbackData);
  }

  private static function convertLineEvents($rawEvents) {
    $events = [];

    // 最下層まで展開してイベントとしての判断ができない時はからの配列を返す
    if (!isset($rawEvents->events) || !is_array($rawEvents->events)) {
      throw new \UnexpectedValueException('Eventsがない、またはEventsがサポートされていない形式です。');
    }

    foreach ($rawEvents->events as $rawEvent) {
      try {
        $event = self::parseEvent($rawEvent);
        array_push($events, $event);
      } catch (\InvalidArgumentException $e) {
        array_push($events, null);
      }
    }

    return $events;

  }

  private static function parseEvent($event) {
    $text = null;
    $postbackData = null;
    if (!isset($event->type)) {
      throw new \InvalidArgumentException('このタイプのイベントには対応していません。');
    }

    switch ($event->type) {
      case 'message' :
      if ($event->message->type === 'text') {
        $type = 'Message.Text';
        $text = $event->message->text;
        break;
      }
      $type = 'Message.File';
      break;
      case 'postback' :
      $type = 'Postback';
      $postbackData = $event->postback->data;
      break;
      default :
      throw new \InvalidArgumentException('このタイプのイベントには対応していません。');
    }
    $userId = $event->source->userId;
    $replyToken = $event->replyToken;
    $rawData = $event;
    return new Event($replyToken, $userId, $type, $rawData, $text, $postbackData);
  }

  private static function buildCurlErrorResponse(\Exception $e) {
    $err = new \stdClass();
    $err->message = $e->getMessage();
    $err->code = $e->getCode();
    return $err;
  }

}
