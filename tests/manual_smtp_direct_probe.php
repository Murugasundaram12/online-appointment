<?php

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = rawurlencode((string) config('mail.mailers.smtp.username'));
$pass = rawurlencode((string) config('mail.mailers.smtp.password'));
$host = config('mail.mailers.smtp.host');
$port = config('mail.mailers.smtp.port');
$dsn = "smtp://{$user}:{$pass}@{$host}:{$port}?encryption=tls&timeout=20";

$transport = Transport::fromDsn($dsn);
$mailer = new Mailer($transport);
$email = (new Email())
    ->from(config('mail.from.address'))
    ->to('muruga12062002@gmail.com')
    ->subject('Clinic appointment email SMTP verification')
    ->text('Laravel clinic appointment email SMTP verification.');

$mailer->send($email);

echo "sent\n";
