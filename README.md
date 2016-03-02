# Laravel-SubMail

```
composer require bingooo/laravel-submail
```

config/app.php

```php
'providers' => [
    Bingooo\Mail\SubMailServiceProvider::class,
];
```

.env

```ini
MAIL_DRIVER=submail

SUBMAIL_APPID=
SUBMAIL_APPKEY=
```
### Normal

```php
Mail::send('emails.welcome', $data, function ($message) {
    $message->from('foo@example.com', 'XXXXX');
    $message->to('foo@example.com')->cc('bar@example.com');
});
```
### Use template:

```php
$vars = ['name' => 'hi','link_text' => 'http://example.com'];
$links = ['link' => 'http://example.com'];
$template = new Bingooo\Mail\SubMailTemplate('XXXXX', $vars, $links);
\Mail::raw($template,function($message){
  $message->from('foo@example.com', 'XXXXX');
  $message->to('foo@example.com');
});
```
