<?php

namespace Framework\FacebookBot;

use Framework\MessageBuilder;

class TextMessageBuilder implements MessageBuilder {

  private $text;

  public function __construct(String $message) {
    $this->text = $message;
  }

  public function buildMessage() {
    return [
      'text' => $this->text
    ];
  }

}