<?php

namespace Bingoo\Mail;

use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Psr\Http\Message\ResponseInterface;
use Swift_Mime_Message;


class SubMailTransport extends Transport
{
  const URL     = 'https://api.submail.cn/mail/xsend';

  private $query = [];

  public function __construct($appid, $appkey)
  {
    $this->addQuery('appid', $appid);
    $this->addQuery('appkey', $appkey);
  }

  protected function setSign()
  {
    $this->addQuery('timestamp', time());
    $this->addQuery('sign_type', 'sha1');
    ksort($this->query);
    $sign_str = '';
    foreach ($this->query as $key => $val) {
      $sign_str .= $sign_str == '' ? $key . '=' . $val : '&' . $key . '=' . $val;
    }
    $sign_str = $this->query['appid'] . $this->query['appkey'] . $sign_str . $this->query['appid'] . $this->query['appkey'];
    $this->addQuery('signature', sha1($sign_str));
  }


  public function send(Swift_Mime_Message $message, &$failedRecipients = null)
  {
    $this->setSign();
    $this->addQuery('subject', $message->getSubject());
    $this->addQuery('from', $this->getAddress($message->getFrom()));
    $this->addQuery('fromname', $this->getFromName($message));
    $this->addQuery('replyto', $this->getAddress($message->getReplyTo()));
    $this->addQuery('cc', $this->getAddresses($message->getCc()));
    $this->addQuery('bcc', $this->getAddresses($message->getBCc()));

    if (!empty($message->getChildren())) {
      foreach ($message->getChildren() as $file) {
        $this->addQuery('files[]', $file->getBody(), $file->getFilename());
      }
    }

    $this->query = array_filter($this->query);

    $body = $message->getBody();

    if ($body instanceof SubMailTemplate) {
      $this->sendTemplate($message);
    } else {
      $this->sendRawMessage($message);
    }
  }

  protected function getAddress($data)
  {
    if (!$data) {
      return;
    }

    return array_get(array_keys($data), 0, null);
  }

  protected function getFromName(Swift_Mime_Message $message)
  {
    return array_get(array_values($message->getFrom()), 0);
  }


  protected function getAddresses($data)
  {
    if (!$data) {
      return;
    }
    $data = array_keys($data);

    if (is_array($data) && !empty($data)) {
      return implode(';', $data);
    }

    return;
  }


  protected function sendRawMessage(Swift_Mime_Message $message)
  {
    $http = new Client();

    $this->addQuery('html', $message->getBody() ?: '');
    $this->addQuery('to', $this->getAddress($message->getTo()));

    $response = $http->post(self::URL, [
      'multipart' => $this->query,
    ]);

    return $this->response($response);
  }

  protected function sendTemplate(Swift_Mime_Message $message)
  {
    $http = new Client();

    $template = $message->getBody();
    $this->addQuery('project', $template->getProject());
    $this->addQuery('vars', json_encode($template->getVars()));
    $this->addQuery('links', json_encode($template->getLinks()));

    $response = $http->post(self::URL, [
      'multipart' => $this->query,
    ]);

    return $this->response($response);
  }


  protected function response(ResponseInterface $response)
  {
    $res = json_decode($response->getBody()->getContents());

    if (isset($res->errors)) {
      throw new SubMailException(array_get($res->errors, 0));
    }

    return true;
  }


  public function addQuery($name, $contents, $filename = null)
  {
    $query = [
      'name'     => $name,
      'contents' => $contents,
    ];

    if ($filename) {
      $query['filename'] = $filename;
    }

    $this->query[] = $query;
  }
}
