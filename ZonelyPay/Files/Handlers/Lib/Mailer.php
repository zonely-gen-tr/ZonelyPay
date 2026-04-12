<?php

namespace Handlers\Lib;

class Mailer
{
    private $cfg;
    public function __construct($cfg)
    {
        $this->cfg = $cfg;
    }

    public function send($to, $subject, $html, $fromEmail = null, $fromName = null)
    {
        $smtp = $this->cfg['smtp'] ?? [];
        $fromEmail = $fromEmail ?: ($smtp['from_email'] ?? 'no-reply@example.com');
        $fromName  = $fromName  ?: ($smtp['from_name']  ?? 'ZonelyPay');

        if (!empty($smtp['enabled']) && !empty($smtp['host'])) {
            try {
                return $this->sendSmtp($to, $subject, $html, $fromEmail, $fromName, $smtp);
            } catch (\Throwable $e) {
            }
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . addslashes($fromName) . " <{$fromEmail}>\r\n";
        return @mail($to, $subject, $html, $headers);
    }

    private function sendSmtp($to, $subject, $html, $fromEmail, $fromName, $smtp)
    {
        $host = (string)($smtp['host'] ?? 'localhost');
        $port = (int)($smtp['port'] ?? 25);
        $enc  = strtolower((string)($smtp['encryption'] ?? 'none')); 
        $user = (string)($smtp['username'] ?? '');
        $pass = (string)($smtp['password'] ?? '');
        $auth = isset($smtp['auth']) ? (bool)$smtp['auth'] : true;

        $remote = ($enc === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $ctx = stream_context_create();
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \Exception("SMTP connect fail: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 15);

        $this->expect($fp, 220);
        $ehloHost = 'localhost';

        $this->write($fp, "EHLO {$ehloHost}\r\n");
        $code = $this->expect($fp, 250, true);

        if ($enc === 'tls') {
            $this->write($fp, "STARTTLS\r\n");
            $this->expect($fp, 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \Exception("STARTTLS failed");
            }
            $this->write($fp, "EHLO {$ehloHost}\r\n");
            $this->expect($fp, 250, true);
        }

        if ($auth && $user !== '' && $pass !== '') {
            $this->write($fp, "AUTH LOGIN\r\n");
            $this->expect($fp, 334);
            $this->write($fp, base64_encode($user) . "\r\n");
            $this->expect($fp, 334);
            $this->write($fp, base64_encode($pass) . "\r\n");
            $this->expect($fp, 235);
        }

        $this->write($fp, "MAIL FROM:<{$fromEmail}>\r\n");
        $this->expect($fp, 250);
        $this->write($fp, "RCPT TO:<{$to}>\r\n");
        $this->expect($fp, 250);
        $this->write($fp, "DATA\r\n");
        $this->expect($fp, 354);

        $boundary = "b_" . bin2hex(random_bytes(8));
        $headers  = "From: " . addslashes($fromName) . " <{$fromEmail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";

        $data  = $headers . "\r\n" . $html . "\r\n.\r\n";
        $this->writeRaw($fp, $data);
        $this->expect($fp, 250);
        $this->write($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    }

    private function write($fp, $line)
    {
        $ok = fwrite($fp, $line);
        if ($ok === false) throw new \Exception("SMTP write failed");
    }
    private function writeRaw($fp, $buf)
    {
        $len = strlen($buf);
        $wr = 0;
        while ($wr < $len) {
            $n = fwrite($fp, substr($buf, $wr));
            if ($n === false) throw new \Exception("SMTP writeRaw failed");
            $wr += $n;
        }
    }
    private function expect($fp, $wantCode, $multiline = false)
    {
        $resp = '';
        while (($line = fgets($fp, 512)) !== false) {
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
            if (!$multiline) break;
        }
        $code = (int)substr($resp, 0, 3);
        if ($code !== (int)$wantCode) {
            throw new \Exception("SMTP expect {$wantCode}, got {$code}: {$resp}");
        }
        return $code;
    }
    private function encodeHeader($str)
    {
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }
}
