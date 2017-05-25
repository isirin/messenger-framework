<?php

namespace Framework;

use Framework\Event;

class FacebookEvent extends Event {

  // [ attachment#type => ファイルのURL ]
  private $fileUrls = null;

  public function __construct($messaging) {
    $this->userId = $messaging->sender->id ?? null;
    $this->replyToken = $messaging->sender->id ?? null;
    $this->rawData = $messaging;
    if (isset($messaging->message)) {
      if (isset($messaging->message->attachments)) {
        $this->type = 'Message.File';
        foreach ($messaging->message->attachments as $attachment) {
          $this->fileUrls[$attachment->type] = $attachment->payload->url;
        }
      } elseif (isset($messaging->message->text)) {
        $this->type = 'Message.Text';
        $this->text = $messaging->message->text;
      } else {
        throw new \InvalidArgumentException('サポートされていない形式のMessaging#Messageです。');
      }
    } elseif (isset($messaging->postback)) {
      $this->type = 'Postback';
      $this->postbackData = $messaging->postback->payload;
    } else {
      throw new \InvalidArgumentException('サポートされていない形式のMessagingです。');
    }
  }

  public function getFiles() {
    if (is_null($this->fileUrls)) {
      return null;
    }
    $files = [];
    foreach ($this->fileUrls as $url) {
      $filename = $this->getKey($url);
      $files[$filename] = file_get_contents();
    }
    return $files;
  }

  private function getKey($url) {
    preg_match('/(.*\/)+([^¥?]+)\?*/', $url, $result);
    return $result[2];
  }

}