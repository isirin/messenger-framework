<?php

namespace MessengerFramework\HttpClient;

// TODO: ヘッダ情報も含めて返すようにする
class Curl {

  private const AWAIT_SECOND = 12;

  public function get(String $url, Array $headers = null, Array $queryArray = null) {
    if (!is_null($queryArray)) {
      $query = http_build_query($queryArray);
      $url .= "?{$query}";
    }
    $ch = curl_init($url);

    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => $this->toHeaderArray($headers ?? []),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPGET => true,
      CURLOPT_TIMEOUT => self::AWAIT_SECOND
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

  public function post(String $url, Array $headers = null, Array $bodyArray = null, Bool $isJSON = false) {
    $ch = curl_init($url);
    if ($isJSON) {
      $headers = $headers ?? [];
      $headers['Content-Type'] = 'application/json';
    }

    curl_setopt_array($ch, [
      CURLOPT_HTTPHEADER => $this->toHeaderArray($headers ?? []),
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $isJSON ? json_encode($bodyArray ?? []) : \http_build_query($bodyArray ?? []),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => self::AWAIT_SECOND
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

  private function toHeaderArray(Array $from) {
    $header = [];
    foreach ($from as $key => $value) {
      array_push($header, join(': ', [$key, $value]));
    }
    return $header;
  }

}
