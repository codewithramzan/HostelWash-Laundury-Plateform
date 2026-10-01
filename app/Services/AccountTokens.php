<?php

namespace app\Services;

use app\Models\DB;

final class AccountTokens
{
    public static function issue(array $user, string $purpose): void
    {
        $token = bin2hex(random_bytes(32));
        FileStore::change('tokens', function (&$data) use ($user, $purpose, $token) {
            $data = array_filter($data, fn ($row) => $row['expires'] > time() && !($row['user_id'] == $user['id'] && $row['purpose'] === $purpose));
            $data[hash('sha256', $token)] = ['user_id' => $user['id'], 'purpose' => $purpose, 'expires' => time() + 3600];
        });
        $link = url($purpose === 'reset' ? '/reset?token=' . $token : '/verify?token=' . $token);
        $message = "Hello {$user['name']},\n\nOpen this link within one hour:\n$link\n\nIf you did not request this, ignore this message.\nHostelWash";
        if (env('MAIL_ENABLED') === 'true') {
            if (!mail($user['email'], 'HostelWash account ' . $purpose, $message, 'From: ' . env('MAIL_FROM'))) {
                throw new \RuntimeException('Email delivery failed.');
            }
        } elseif (env('APP_ENV', 'local') === 'local') {
            FileStore::change('mail_outbox', function (&$outbox) use ($user, $purpose, $message) {
                $outbox[] = ['to' => $user['email'], 'purpose' => $purpose, 'message' => $message, 'created_at' => date(DATE_ATOM)];
            });
        } else {
            throw new \RuntimeException('Mail must be configured in production.');
        }
    }

    public static function consume(string $token, string $purpose, ?string $password = null): void
    {
        FileStore::change('tokens', function (&$data) use ($token, $purpose, $password) {
            $hash = hash('sha256', $token);
            $row = $data[$hash] ?? null;
            if (!$row || $row['expires'] < time() || $row['purpose'] !== $purpose) {
                throw new \DomainException('This link has expired or was already used. Request a new link.');
            }
            if ($purpose === 'reset') {
                DB::update('users', (int) $row['user_id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
            } else {
                FileStore::change('verified', function (&$verified) use ($row) {
                    $verified[$row['user_id']] = true;
                });
            }
            unset($data[$hash]);
        });
    }
}
