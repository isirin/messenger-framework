<?php
/**
 * LineBotを定義
 *
 * @copyright Genies, Inc. All Rights Reserved
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author Rintaro Ishikawa
 */

namespace MessengerFramework;

require_once realpath(dirname(__FILE__)) . '/Curl.php';
require_once realpath(dirname(__FILE__)) . '/Event.php';
require_once realpath(dirname(__FILE__)) . '/Config.php';

/**
 * [API] LineのMessengerのAPIを扱うためのクラス
 *
 * 引数として受けるEventや結果として返すファイルの配列は各プラットフォームのものが統一されている
 *
 * @access public
 * @package MessengerFramework
 */
class LineBot {

  // MARK : Constructor

  /**
   * LineBot constructor
   *
   * @param Curl $httpClient
   * @param Config $config
   */
  public function __construct(Curl $httpClient, Config $config) {
    self::$_LINE_CHANNEL_SECRET = $config->LINE_CHANNEL_SECRET;
    self::$_LINE_ACCESS_TOKEN = $config->LINE_ACCESS_TOKEN;
    $this->_httpClient = $httpClient;
  }

  // MARK : Bot Interface の実装

  /**
   * Lineで送信予定のメッセージを返信する
   *
   * @param String $to
   * @return String APIからのレスポンスやCurlのエラーをまとめた配列のJSON
   */
  public function replyMessage(String $to) {
    return $this->_sendMessage($this->_getReplyEndpoint(), [ 'replyToken' => $to ]);
  }

  /**
   * Lineで送信予定のメッセージを送信する
   *
   * @param String $to
   * @return String APIからのレスポンスやCurlのエラーをまとめた配列のJSON
   */
  public function pushMessage(String $to) {
    return $this->_sendMessage($this->_getPushEndpoint(), [ 'to' => $to ]);
  }

  /**
   * LineのEvent(メッセージ)中に含まれるファイルを取得する
   *
   * @param String $requestBody
   * @return Event|null[] Eventクラスの配列
   */
  public function parseEvents(String $requestBody) {
    return self::_convertLineEvents(\json_decode($requestBody));
  }

  /**
   * LineからのWebhookリクエストかどうかを確認する
   *
   * @param String $requestBody
   * @param String $signature
   * @return Bool 正しい送信元からのリクエストかどうか
   */
  public function testSignature(String $requestBody, String $signature) {
    $sample = hash_hmac('sha256', $requestBody, self::$_LINE_CHANNEL_SECRET, true);
    return hash_equals(base64_encode($sample), $signature);
  }

  /**
   * Lineのユーザーのプロフィールを差異を吸収したものへ変換する
   *
   * @param String $userId
   * @return Array プラットフォームの差異が吸収されたプロフィールを表す連想配列
   */
  public function getProfile(String $userId) {
    $res = $this->_httpClient->get(
      $this->_getProfileEndpoint($userId),
      ['Authorization' => 'Bearer ' . self::$_LINE_ACCESS_TOKEN]
    );
    $profile = json_decode($res);
    if (!isset($profile->displayName)) {
      throw new \UnexpectedValueException('プロフィールが取得できませんでした。');
    }
    return [
      'name' => $profile->displayName,
      'profilePic' => $profile->pictureUrl,
      'rawProfile' => $profile
    ];
  }

  /**
   * LineのEvent(メッセージ)中に含まれるファイルを取得する
   *
   * @param Event $event
   * @return Array ファイル名 => バイナリ文字列 の連想配列
   */
  public function getFiles(Event $event) {
    $rawEvent = $event->rawData;
    // Lineのメッセージについてきたファイルのファイル名はわからない
    // なのでメッセージID.拡張子の形で取り扱う(Lineはメッセージとファイルが1:1なのでこれでok)
    switch ($rawEvent->message->type) {
      case 'image' :
      $ext = '.jpg';
      break;
      case 'video' :
      $ext = '.mp4';
      break;
      case 'audio' :
      $ext = '.m4a';
      break;
      default :
      break;
    }
    $file = $this->_httpClient->get(
      $this->_getContentEndpoint($rawEvent->message->id),
      [ 'Authorization' => 'Bearer ' . self::$_LINE_ACCESS_TOKEN ]
    );
    return [ $rawEvent->message->id . $ext => $file ];
  }

  // MARK : Public LineBotのメソッド

  /**
   * テキストメッセージを送信予定に追加する
   *
   * @param String $message
   */
  public function addText(String $message) {
    array_push($this->_templates, [
      'type' => 'text',
      'text' => $message
    ]);
  }

  /**
   * 画像を送信予定に追加する
   *
   * @param String $url
   * @param String $previewUrl
   */
  public function addImage(String $url, String $previewUrl) {
    array_push($this->_templates, [
      'type' => 'image',
      'originalContentUrl' => $url,
      'previewImageUrl' => $previewUrl,
    ]);
  }

  /**
   * 動画を送信予定に追加する
   *
   * @param String $url
   * @param String $previewUrl
   */
  public function addVideo(String $url, String $previewUrl) {
    array_push($this->_templates, [
      'type' => 'video',
      'originalContentUrl' => $url,
      'previewImageUrl' => $previewUrl,
    ]);
  }

  /**
   * 音声を送信予定に追加する
   *
   * @param String $url
   * @param Int $duration
   */
  public function addAudio(String $url, Int $duration) {
    array_push($this->_templates, [
      'type' => 'audio',
      'originalContentUrl' => $url,
      'duration' => $duration,
    ]);
  }

  /**
   * Carouselメッセージを送信予定に追加する
   *
   * @param Array $columns
   */
  public function addCarousel(Array $columns) {
    array_push($this->_templates, $this->_buildTemplate(
      'alt text for carousel',
      $this->_buildCarousel($columns)
    ));
  }

  /**
   * Confirmメッセージを送信予定に追加する
   *
   * @param String $text
   * @param Array $buttons
   */
  public function addConfirm(String $text, Array $buttons) {
    array_push($this->_templates, $this->_buildTemplate(
      'alt text for confirm',
      $this->_buildConfirm($text, $buttons)
    ));
  }

  // MARK : Private

  private static $_LINE_CHANNEL_SECRET;

  private static $_LINE_ACCESS_TOKEN;

  private $_httpClient;

  private $_endpoint = 'https://api.line.me/';

  private $_templates = [];

  private static function _buildCurlErrorResponse(\Exception $e) {
    $err = new \stdClass();
    $err->message = $e->getMessage();
    $err->code = $e->getCode();
    return $err;
  }

  private static function _convertLineEvents($rawEvents) {
    $events = [];

    // 最下層まで展開してイベントとしての判断ができない時はからの配列を返す
    if (!isset($rawEvents->events) || !is_array($rawEvents->events)) {
      throw new \UnexpectedValueException('Eventsがない、またはEventsがサポートされていない形式です。');
    }

    foreach ($rawEvents->events as $rawEvent) {
      try {
        $event = self::_parseEvent($rawEvent);
        array_push($events, $event);
      } catch (\InvalidArgumentException $e) {
        array_push($events, null);
      }
    }

    return $events;
  }

  private static function _isFileEvent(String $type) {
    return $type === 'image' || $type === 'video' || $type === 'audio';
  }

  private static function _parseEvent($event) {
    if (!isset($event->type)) {
      return null;
    }

    $userId = $event->source->userId;
    $replyToken = $event->replyToken;
    $rawData = $event;

    switch ($event->type) {
      case 'postback' :
      $type = 'Postback';
      $postbackData = $event->postback->data;
      return new Event($replyToken, $userId, $type, $rawData, [ 'postback' => $postbackData ]);
      case 'beacon' :
      $type = 'Beacon';
      $beaconData = [];
      return new Event($replyToken, $userId, $type, $rawData, null, [ 'hwid' => $event->beacon->hwid, 'type' => $event->beacon->type ]);
      case 'message' :
      switch ($event->message->type) {
        case 'text' :
        $type = 'Message.Text';
        $text = $event->message->text;
        return new Event($replyToken, $userId, $type, $rawData, [ 'text' => $text ]);
        case 'location' :
        $type = 'Message.Location';
        $location = [
          'lat' => $event->message->latitude,
          'long' => $event->message->longitude
        ];
        return new Event($replyToken, $userId, $type, $rawData, [ 'location' => $location ]);
        case 'image' :
        case 'video' :
        case 'audio' :
        $type = 'Message.File';
        return new Event($replyToken, $userId, $type, $rawData);
        case 'sticker' :
        $type = 'Message.Sticker';
        $stickerId = $event->message->packageId . ',' .$event->message->stickerId;
        return new Event($replyToken, $userId, $type, $rawData, [ 'sticker' => $stickerId ]);
      }
    }
    return null;
  }

  private function _sendMessage(String $endpoint, Array $options) {
    try {
      $res = $this->_httpClient->post($endpoint, [
        'Authorization' => 'Bearer ' . self::$_LINE_ACCESS_TOKEN
      ], \array_merge($options, [ 'messages' => $this->_templates ]), true);
    } catch (\RuntimeException $e) {
      $res = self::_buildCurlErrorResponse($e);
    }
    $this->_templates = [];
    return json_encode($res);
  }

  private function _buildTemplate(String $altText, Array $template) {
    return [
      'type' => 'template',
      'altText' => $altText,
      'template' => $template
    ];
  }

  private function _buildCarousel($source) {
    $columns = [];
    foreach ($source as $column) {
      array_push($columns, $this->_buildColumn($column));
    }
    return [
      'type' => 'carousel',
      'columns' => $columns,
    ];
  }

  private function _buildConfirm(String $text, Array $buttons) {
    $actions = [];
    foreach ($buttons as $button) {
      array_push($actions, $this->_buildAction($button));
    }
    return [
      'type' => 'confirm',
      'text' => $text,
      'actions' => $actions
    ];
  }

  private function _buildColumn($source) {
    $actions = [];
    foreach ($source[3] as $button) {
      array_push($actions, $this->_buildAction($button));
    }
    return [
      'thumbnailImageUrl' => $source[2],
      'title' => $source[0],
      'text' => $source[1],
      'actions' => $actions,
    ];
  }

  private function _buildAction($source) {
    $action = [
      'type' => $source['action'],
      'label' => $source['title']
    ];
    switch ($source['action']) {
      case 'postback' :
      $action['data'] = $source['data'];
      break;
      case 'url' :
      $action['uri'] = $source['url'];
      $action['type'] = 'uri';
      break;
      default :
    }
    return $action;
  }

  private function _getReplyEndpoint() {
    return $this->_endpoint . 'v2/bot/message/reply';
  }

  private function _getPushEndpoint() {
    return $this->_endpoint . 'v2/bot/message/push';
  }

  private function _getProfileEndpoint($userId) {
    return $this->_endpoint . 'v2/bot/profile/' . $userId;
  }

  private function _getContentEndpoint($messageId) {
    return 'https://api.line.me/v2/bot/message/' . $messageId . '/content';
  }

}
