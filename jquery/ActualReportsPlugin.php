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
class ActualReportsPlugin
{
  private $client;
  private $data;
  private $email;

  private $key;
  private $secret;
  private $url;

  public function __construct($key, $secret, $url = null)
  {
    $this->key = $key;
    $this->secret = $secret;
    $this->url = $url;
  }

  public function templates($params = array())
  {
    $client = $this->getClient();
    $client->setEmail($this->email);
    if (isset($_REQUEST['actual_reports_breakcache']) && intval($_REQUEST['actual_reports_breakcache']))
    {
      $params['breakcache'] = 1;
    }
    self::response($client->request('get', 'template', $params));
  }

  public function editor($params = array())
  {
    $params['iframe'] = 1;
    $client = $this->getClient();
    $client->setEmail($this->email);
    self::response(array(
      'url' => $client->getEditorUrl($params),
      'params' => array(
        'data' => $this->getRequestData()
      )
    ));
  }

  public function inline($params = array())
  {
    $response = $this->output($_REQUEST['actual_reports_template'], $_REQUEST['actual_reports_format'], $params);

    header('Content-type: ', $response['meta']['content-type']);
    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
    header('Pragma: public');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Content-Disposition: inline; filename="Preview.pdf"');
    die(base64_decode($response['response']));
  }

  public function printout($params = array())
  {
    $this->inline(array_merge($params, array('print' => 1)));
  }

  public function download($params = array())
  {
    $response = $this->output($_REQUEST['actual_reports_template'], $_REQUEST['actual_reports_format'], $params);
    header('Content-type: ', $response['meta']['content-type']);
    header("Content-Description: File Transfer");
    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    header('Content-Disposition: attachment; filename="'.uniqid().'.pdf"');
    header('Content-Transfer-Encoding: binary');
    die(base64_decode($response['response']));
  }

  private function output($template, $format = 'pdf', $params = array())
  {
    if (!$template)
    {
      throw new Exception('ActionPreview: template missing');
    }

    $client = $this->getClient();
    $client->setData($this->data);
    $client->setEmail($this->email);
    return $client->request('post', 'template/'.$template.'/output', array_merge($params, array(
      'output' => 'base64',
      'format' => $format
    )), false);
  }

  /**
   * Set data
   *
   * @param  array $data
   */
  public function setData($data)
  {
    $this->data = $data;
  }

  /**
   * Set user email
   *
   * @param  string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }

  private static function response($response)
  {
    die(json_encode($response));
  }

  private function getClient()
  {
    if (!$this->client)
    {
      $this->client = new ActualReportsApi($this->key, $this->secret);
      if ($this->url)
      {
        $this->client->setUrl($this->url);
      }
    }

    return $this->client;
  }
}
?>