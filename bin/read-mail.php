<?php

require __DIR__ . '/../config/bootstrap.php';
if (PHP_SAPI !== 'cli' || env('APP_ENV') !== 'local') {
    exit('Local CLI only.');
}
foreach (app\Services\FileStore::read('mail_outbox') as $mail) {
    echo 'To: ' . $mail['to'] . "\n" . $mail['message'] . "\n\n";
}
