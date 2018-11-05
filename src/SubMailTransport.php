<?php

namespace Bingooo\Mail;

use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Psr\Http\Message\ResponseInterface;
use Swift_Mime_Message;
use Swift_Mime_SimpleMessage;

class SubMailTransport extends Transport
{
  const URL = 'https://api.submail.cn/mail/send';
  const XURL = 'https://api.submail.cn/mail/xsend';

  private $query = [];

  public function __construct($appid, $appkey)
  {
    $this->addQuery('appid', $appid);
    $this->addQuery('appkey', $appkey);
    $this->appid = $appid;
    $this->appkey = $appkey;
  }

  protected function setSign()
  {
    // $this->addQuery('timestamp', time());
    // $this->addQuery('sign_type', 'sha1');
    // ksort($this->query);
    // $sign_str = '';
    // foreach ($this->query as $q) {
    //   $sign_str .= $sign_str == '' ? $q['name'] . '=' . $q['contents'] : '&' . $q['name'] . '=' . $q['contents'];
    // }
    // $sign_str = $this->appid . $this->appkey . $sign_str . $this->appid . $this->appkey;
    // $this->addQuery('signature', sha1($sign_str));
    $this->addQuery('signature', $this->appkey);
  }


  public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
  {
    $this->addQuery('subject', $message->getSubject());
    $this->addQuery('from', $this->getAddress($message->getFrom()));
    $this->addQuery('from_name', $this->getFromName($message));
    $this->addQuery('reply', $this->getAddress($message->getReplyTo()));
    $this->addQuery('cc', $this->getAddresses($message->getCc()));
    $this->addQuery('bcc', $this->getAddresses($message->getBCc()));
    // $this->addQuery('asynchronous', true);

    if (!empty($message->getChildren())) {
      foreach ($message->getChildren() as $file) {
        $this->addQuery('attachments[]', $file->getBody(), $file->getFilename());
      }
    }
    $this->query = array_filter($this->query);
    $body = $message->getBody();
    $this->setSign();
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
    $this->addQuery('to', $this->getAddress($message->getTo()));
    $this->addQuery('project', $template->getProject());
    $this->addQuery('vars', json_encode($template->getVars()));
    $this->addQuery('links', json_encode($template->getLinks()));

    $response = $http->post(self::XURL, [
      'multipart' => $this->query,
    ]);

    return $this->response($response);
  }


  protected function response(ResponseInterface $response)
  {
    $res = json_decode($response->getBody()->getContents());

    if ($res->status == 'error') {
      throw new SubMailException($res->msg);
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
