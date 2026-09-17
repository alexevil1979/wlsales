<?php

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $bodyHtml): bool
    {
        $from = mail_cfg('mail_from', 'MAIL_FROM', 'noreply@localhost');
        $fromName = mail_cfg('mail_from_name', 'MAIL_FROM_NAME', 'WL Sales');
        $smtpOn = setting('mail_smtp_on', '') === '1';
        if (setting('mail_smtp_on', '') === '' && mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST') !== '') {
            // ещё не сохраняли флаг в админке — включаем SMTP, если host уже в .env
            $smtpOn = true;
        }

        if ($smtpOn && mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST') !== '') {
            if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                return self::sendPhpMailer($to, $subject, $bodyHtml, $from, $fromName);
            }
            return self::sendSmtp($to, $subject, $bodyHtml, $from, $fromName);
        }

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
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST');
            $mail->Port = (int) mail_cfg('mail_smtp_port', 'MAIL_SMTP_PORT', '587');
            $mail->SMTPAuth = true;
            $mail->Username = mail_cfg('mail_smtp_user', 'MAIL_SMTP_USER');
            $mail->Password = mail_cfg('mail_smtp_pass', 'MAIL_SMTP_PASS');
            $secure = mail_cfg('mail_smtp_secure', 'MAIL_SMTP_SECURE', 'tls');
            if ($secure !== '') {
                $mail->SMTPSecure = $secure;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('WLSales Mailer PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Минимальный SMTP (AUTH LOGIN) без PHPMailer — как fallback для админ-SMTP.
     */
    private static function sendSmtp(string $to, string $subject, string $bodyHtml, string $from, string $fromName): bool
    {
        $host = mail_cfg('mail_smtp_host', 'MAIL_SMTP_HOST');
        $port = (int) mail_cfg('mail_smtp_port', 'MAIL_SMTP_PORT', '587');
        $secure = strtolower(mail_cfg('mail_smtp_secure', 'MAIL_SMTP_SECURE', 'tls'));
        $user = mail_cfg('mail_smtp_user', 'MAIL_SMTP_USER');
        $pass = mail_cfg('mail_smtp_pass', 'MAIL_SMTP_PASS');

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            error_log("WLSales Mailer SMTP connect: {$errstr} ({$errno})");
            return false;
        }
        stream_set_timeout($fp, 20);

        try {
            self::smtpExpect($fp, [220]);
            self::smtpCmd($fp, 'EHLO wlsales.local', [250]);
            if ($secure === 'tls') {
                self::smtpCmd($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS failed');
                }
                self::smtpCmd($fp, 'EHLO wlsales.local', [250]);
            }
            if ($user !== '') {
                self::smtpCmd($fp, 'AUTH LOGIN', [334]);
                self::smtpCmd($fp, base64_encode($user), [334]);
                self::smtpCmd($fp, base64_encode($pass), [235]);
            }
            self::smtpCmd($fp, 'MAIL FROM:<' . $from . '>', [250]);
            self::smtpCmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::smtpCmd($fp, 'DATA', [354]);

            $headers = [
                'Date: ' . date('r'),
                'From: ' . self::encodeAddress($fromName, $from),
                'To: <' . $to . '>',
                'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'X-Mailer: WLSales',
            ];
            $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($bodyHtml));
            $data = preg_replace('/^\./m', '..', $data) ?? $data;
            fwrite($fp, $data . "\r\n.\r\n");
            self::smtpExpect($fp, [250]);
            self::smtpCmd($fp, 'QUIT', [221]);
            fclose($fp);
            return true;
        } catch (\Throwable $e) {
            error_log('WLSales Mailer SMTP: ' . $e->getMessage());
            fclose($fp);
            return false;
        }
    }

    /** @param list<int> $ok */
    private static function smtpCmd($fp, string $cmd, array $ok): void
    {
        fwrite($fp, $cmd . "\r\n");
        self::smtpExpect($fp, $ok);
    }

    /** @param resource $fp @param list<int> $ok */
    private static function smtpExpect($fp, array $ok): void
    {
        $line = '';
        while (($buf = fgets($fp, 515)) !== false) {
            $line = $buf;
            if (isset($buf[3]) && $buf[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($line, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new \RuntimeException('SMTP unexpected: ' . trim($line));
        }
    }
}
