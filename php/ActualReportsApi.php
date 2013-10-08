<?php
/**
  *  The MIT License (MIT)
  *
  *  Copyright (c) 2013 Actual Reports
  *
  *  Permission is hereby granted, free of charge, to any person obtaining a copy of
  *  this software and associated documentation files (the "Software"), to deal in
  *  the Software without restriction, including without limitation the rights to
  *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
  *  the Software, and to permit persons to whom the Software is furnished to do so,
  *  subject to the following conditions:
  *
  *  The above copyright notice and this permission notice shall be included in all
  *  copies or substantial portions of the Software.
  *
  *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
  *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
  *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
  *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
  *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
  *
  **/
class ActualReportsException extends ErrorException {}

class ActualReportsApi
{
  protected $url = 'https://dev.actualreports.com/api';
  protected $version = 'v2';
  protected $apiKey;
  protected $privateKey;
  protected $email;
  protected $data;

  public $curlopts = array(
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => NULL,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_ENCODING => '',
    CURLOPT_HTTPAUTH => CURLAUTH_ANY,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HTTPHEADER => array(
      CURLOPT_USERAGENT => 'actualreports-php/2.0.0',
      CURLOPT_HTTPHEADER => array(
        'Accept-Charset: utf-8',
        'Content-Type: application/json'
      )
    )
  );

  public function __construct($apiKey = null, $privateKey = null)
  {
    if ($apiKey)
    {
      $this->apiKey = $apiKey;
    }
    if ($privateKey)
    {
      $this->privateKey = $privateKey;
    }
  }

  /**
   * Set apikey value
   *
   * @param string $apiKey
   */
  public function setApiKey($apiKey)
  {
    $this->apiKey = $apiKey;
  }

  /**
   * Set privatekey value
   *
   * @param string $privateKey
   */
  public function setPrivateKey($privateKey)
  {
    $this->privateKey = $privateKey;
  }

  /**
   * Set api endpoint url
   *
   * @param string $url
   */
  public function setUrl($url)
  {
    $this->url = $url;
  }

  /**
   * Set user email
   *
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }

  /**
   * Set data
   *
   * @param array $data
   */
  public function setData($data)
  {
    $this->data = $data;
  }

  /**
   * Set version
   * @param string $version v1 or v2
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }

  /**
   * Returns url for editor redirect
   * @param  array $params
   *
   * @return string
   */
  public function getEditorUrl($params = array())
  {
    return $this->createUrl('editor', $params);
  }

  /**
   * Get resource
   *
   * @param string $method
   * @param string $resource path to resource
   * @param array $params query string params
   * @param boolean $stripMeta set true to remove meta data and return only content
   *
   * @return mixed
   */
  public function request($method = 'get', $resource, $params = array(), $stripMeta = true)
  {
    $response = null;
    if (isset($params['data']))
    {
      $this->data = $params['data'];
      unset($params['data']);
    }
    $response = $this->makeRequest($this->createUrl($resource, $params), $method);

    list($code, $headers, $content) = $response;
    $content = json_decode($content, true);

    if ($code !== 200)
    {
      throw new ActualReportsException($content['error'] ? $content['error'] : 'Server error');
    }

    return $stripMeta ? $content['response'] : $content;
  }

  protected function createSignature($resource, $time)
  {
    if (!$this->privateKey)
    {
      throw new ActualReportsException('Missing privatekey');
    }
    if (!$this->apiKey)
    {
      throw new ActualReportsException('Missing apikey');
    }
    if (!$this->email)
    {
      throw new ActualReportsException('Missing email');
    }

    return hash('sha256', $this->privateKey.$this->apiKey.strtolower(str_replace('/', '',$resource)).$this->email.$time);
  }

  protected function createUrl($resource, $params = array())
  {
    $time = time();
    $query = http_build_query(array_merge(array(
      'apikey' => $this->apiKey,
      'email' => $this->email,
      'signature' => $this->createSignature($resource, $time),
      'timestamp' => $time
    ), $params));

    return preg_replace('/([a-zA-Z])[\/]+/', '$1/', implode('/', array($this->url, $this->version, $resource))).'?'.$query;
  }

  /**
   * Makes request and returns the result
   *
   * Bits and pieces from  TinyHttp from https://gist.github.com/618157.
   * Copyright 2011, Neuman Vong. BSD License.
   */
  protected function makeRequest($url, $method = 'get')
  {
    $opts = $this->curlopts;
    $opts[CURLOPT_URL] = $url;

    switch (strtolower($method))
    {
      case 'post':
        $opts[CURLOPT_POSTFIELDS] = json_encode($this->data);
        break;
      case 'get':
        $opts[CURLOPT_HTTPGET] = true;
        break;
    }

    try {
      if ($curl = curl_init()) {
        if (curl_setopt_array($curl, $opts)) {
          if ($response = curl_exec($curl)) {
            $parts = explode("\r\n\r\n", $response, 3);
            list($head, $body) = ($parts[0] == 'HTTP/1.1 100 Continue')
              ? array($parts[1], $parts[2])
              : array($parts[0], $parts[1]);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $header_lines = explode("\r\n", $head);
            array_shift($header_lines);
            foreach ($header_lines as $line) {
              list($key, $value) = explode(":", $line, 2);
              $headers[$key] = trim($value);
            }
            curl_close($curl);
            return array($status, $headers, $body);
          } else {
              throw new ActualReportsException(curl_error($curl));
          }
        } else throw new ActualReportsException(curl_error($curl));
      } else throw new ActualReportsException('unable to initialize cURL');
    } catch (ErrorException $e) {
      if (is_resource($curl)) curl_close($curl);
      throw $e;
    }
  }
}