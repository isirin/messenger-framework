<?php
/**
 * Curlを定義
 *
 * @copyright Genies, Inc. All Rights Reserved
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @author Rintaro Ishikawa
 */

namespace MessengerFramework;

// TODO: ヘッダ情報も含めて返すようにする
// TODO: Curlの実行時エラーは例外を投げるのではなく、成功時も含めたラップをした結果クラスを用意する
// なぜならFW一番外側のMessengerBotはプラットフォームに依らない統一的な結果をユーザーに伝えたい
// またMessengerBotは例外を吸収する必要があり、実行時例外が起きうることを知っている
// なので各プラットフォームのBotの段階では例外が飛んで来る

/**
 * Curlを扱うラッパークラス
 *
 * Webhookリクエストに応答するためにタイムアウトが設定してある
 *
 * @access public
 * @package MessengerFramework
 */
class Curl {

  // MARK : Public Curlクラスのメソッド

  /**
   * getリクエストを送る
   *
   * @param String $url
   * @param Array|null $headers
   * @param Array|null $queryArray
   *
   * @return String レスポンスボディ
   */
  public function get(String $url, Array $headers = null, Array $queryArray = null) {
    if (!is_null($queryArray)) {
      $query = http_build_query($queryArray);
      $url .= "?{$query}";
    }
    $ch = curl_init($url);

    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => $this->_toHeaderArray($headers ?? []),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPGET => true,
      CURLOPT_TIMEOUT => self::$_AWAIT_SECOND
    ]);

    $response =  curl_exec($ch);
    $code = curl_errno($ch);
    if ($code !== CURLE_OK) {
      $message = curl_error($ch);
      curl_close($ch);
      throw new \RuntimeException($message, $code);
    }
    curl_close($ch);
    return $response;
  }

  /**
   * postリクエストを送る
   *
   * @param String $url
   * @param Array|null $headers
   * @param Array|null $bodyArray
   * @param Bool $isJSON
   *
   * @return String レスポンスボディ
   */
  public function post(String $url, Array $headers = null, Array $bodyArray = null, Bool $isJSON = false) {
    $ch = curl_init($url);
    if ($isJSON) {
      $headers = $headers ?? [];
      $headers['Content-Type'] = 'application/json';
    }

    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => $this->_toHeaderArray($headers ?? []),
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $isJSON ? json_encode($bodyArray ?? []) : \http_build_query($bodyArray ?? []),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => self::$_AWAIT_SECOND
    ]);
    $response = curl_exec($ch);
    $code = curl_errno($ch);
    if ($code !== CURLE_OK) {
      $message = curl_error($ch);
      curl_close($ch);
      throw new \RuntimeException($message, $code);
    }
    curl_close($ch);
    return $response;
  }

  // MARK : Private

  private static $_AWAIT_SECOND = 12;

  private function _toHeaderArray(Array $from) {
    $header = [];
    foreach ($from as $key => $value) {
      array_push($header, join(': ', [$key, $value]));
    }
    return $header;
  }

}
