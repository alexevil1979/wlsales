<?php

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml): bool
    {
        $from = Env::get('MAIL_FROM', 'noreply@localhost');
        $fromName = Env::get('MAIL_FROM_NAME', 'WL Sales');
        $driver = Env::get('MAIL_DRIVER', 'mail');

        if ($driver === 'phpmailer' && class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return self::sendPhpMailer($to, $subject, $bodyHtml, $from, $fromName);
        }

        return self::sendNative($to, $subject, $bodyHtml, $from, $fromName);
    }

    private static function sendNative(string $to, string $subject, string $bodyHtml, string $from, string $fromName): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . self::encodeAddress($fromName, $from),
            'X-Mailer: WLSales',
        ];
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, implode("\r\n", $headers));
    }

    private static function encodeAddress(string $name, string $email): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }

    private static function sendPhpMailer(string $to, string $subject, string $bodyHtml, string $from, string $fromName): bool
    {
        // Если установлен PHPMailer — используем SMTP из .env
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) Env::get('MAIL_SMTP_HOST');
            $mail->Port = (int) Env::get('MAIL_SMTP_PORT', '587');
            $mail->SMTPAuth = true;
            $mail->Username = (string) Env::get('MAIL_SMTP_USER');
            $mail->Password = (string) Env::get('MAIL_SMTP_PASS');
            $secure = Env::get('MAIL_SMTP_SECURE', 'tls');
            if ($secure) {
                $mail->SMTPSecure = $secure;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
