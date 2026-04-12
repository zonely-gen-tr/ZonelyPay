<?php

use Handlers\Lib\Config;
use Handlers\Lib\Util;
use Handlers\Lib\Mailer;

require_once __DIR__ . '/../Handlers/Lib/Config.php';
require_once __DIR__ . '/../Handlers/Lib/Util.php';
require_once __DIR__ . '/../Handlers/Lib/Mailer.php';

$CFG   = Config::load(dirname(__DIR__, 2) . '/config.yml');
$STORE = dirname(__DIR__, 2) . '/Files/Handlers/Storage';

$action = $_GET['action'] ?? '';


if ($action === 'callback') {
    $tid    = (string)($_POST['tid'] ?? '');
    $status = (string)($_POST['status'] ?? 'failed');
    $amount = (string)($_POST['amount'] ?? '0.00');

    if ($tid === '') {
        Util::jsonResponse(['status' => 'error', 'message' => 'TID eksik'], 400);
    }
    $txnPath = $STORE . '/transactions_' . $tid . '.json';
    $txn     = Util::readJson($txnPath);
    if (!$txn) {
        Util::jsonResponse(['status' => 'error', 'message' => 'İşlem bulunamadı'], 404);
    }

    $txn['status']      = $status;
    $txn['paid_amount'] = Util::money($amount);
    Util::writeJson($txnPath, $txn);

    $mailer     = new Mailer($CFG);
    $siteName   = (string)($CFG['site']['name'] ?? 'ZonelyPay');
    $contract   = (string)($CFG['site']['sales_contract_url'] ?? '');
    $base       = rtrim((string)($CFG['site']['base_url'] ?? ''), '/');
    if ($base === '') {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base  = $proto . '://' . $host;
    }
    $disputeUrl = $base . '/Files/Src/Views/dispute.php?tid=' . urlencode($tid);

    $userEmail = (string)($txn['origin']['user_email'] ?? '');
    if ($userEmail !== '') {
        if ($status === 'success') {
            $html = "<p><strong>{$siteName}</strong> üzerinde <strong>{$txn['paid_amount']} TL</strong> kredi yüklemesi yaptınız.</p>";
            $html .= "<p>Yüklediğiniz krediyi ilgili X sitede kullanabilirsiniz. En az <strong>3 gün</strong> içerisinde itiraz hakkınız mevcuttur.</p>";
            $html .= "<p>İtiraz bağlantısı: <a href="{$disputeUrl}">{$disputeUrl}</a></p>";
            if ($contract) $html .= "<p>Mesafeli Satış Sözleşmesi: <a href="{$contract}">Görüntüle</a></p>";
            $mailer->send($userEmail, "Ödeme Başarılı • {$txn['paid_amount']} TL", $html);
        } else {
            $html = "<p>Ödemeniz tamamlanamadı. Lütfen tekrar deneyin.</p>";
            $mailer->send($userEmail, "Ödeme Başarısız", $html);
        }
    }

    $domain     = (string)($txn['origin']['domain'] ?? '');
    $notifyPath = (string)($CFG['site']['notify_path'] ?? '/Files/Api/process.php?api=zonelypay');
    $notifyUrl  = Util::normUrl($domain) . $notifyPath;

    $secret = (string)($CFG['security']['shared_secret'] ?? 'change-me-secret');
    $payload = [
        'tid'     => $tid,
        'user_id' => (int)($txn['origin']['user_id'] ?? 0),
        'amount'  => (string)($txn['paid_amount'] ?? $amount),
        'status'  => $status,
        'sig'     => Util::hmac(((int)($txn['origin']['user_id'] ?? 0)) . '|' . ((string)($txn['paid_amount'] ?? $amount)) . '|' . $tid . '|' . $status, $secret),
    ];

    $ch = curl_init($notifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload, '', '&'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
    ]);
    $resp = curl_exec($ch);
    $err  = curl_errno($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $merchantMail = (string)($CFG['site']['merchant_email'] ?? ($CFG['smtp']['notify_email'] ?? ''));
    $siteLink     = Util::normUrl($domain);

    if ($merchantMail !== '') {
        if ($err !== 0 || $code < 200 || $code >= 300) {
            $html = "<p><strong>{$siteName}</strong> üzerinde <strong>{$txn['paid_amount']} TL</strong> ödeme denendi.</p>";
            $html .= "<p><strong>{$siteLink}</strong> adresindeki hedef sistem geri bildirimi <em>ALAMADI</em> (HTTP {$code}).</p>";
            $html .= "<p>TID: <code>{$tid}</code>, Kullanıcı ID: <strong>".htmlspecialchars((string)($txn['origin']['user_id'] ?? '0'))."</strong></p>";
            $mailer->send($merchantMail, "ZonelyPay • Bildirim Hatası", $html);
        } else {
            $html = "<p><strong>{$siteName}</strong> üzerinde <strong>{$txn['paid_amount']} TL</strong> ödeme başarılı.</p>";
            $html .= "<p>Hedef: <strong>{$siteLink}</strong> (callback OK).</p>";
            $html .= "<p>TID: <code>{$tid}</code>, Kullanıcı ID: <strong>".htmlspecialchars((string)($txn['origin']['user_id'] ?? '0'))."</strong></p>";
            $mailer->send($merchantMail, "ZonelyPay • Ödeme Başarılı", $html);
        }
    }

    Util::jsonResponse(['status' => 'ok']);
}

if ($action === 'dispute') {
    $tid = (string)($_POST['tid'] ?? '');
    $email = (string)($_POST['email'] ?? '');
    $msg = (string)($_POST['message'] ?? '');
    if ($tid === '' || $email === '' || $msg === '') {
        Util::jsonResponse(['status' => 'error', 'message' => 'Eksik alan'], 400);
    }
    $ticketId = 'dsp_' . bin2hex(random_bytes(6));
    $ticket = [
        'id' => $ticketId,
        'tid' => $tid,
        'email' => $email,
        'message' => $msg,
        'created_at' => time(),
    ];
    $path = $STORE . '/dispute_' . $ticketId . '.json';
    Util::writeJson($path, $ticket);
    if (!empty($_FILES['evidence']['tmp_name'])) {
        $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
        $destDir = $STORE . '/uploads';
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
        $dest = $destDir . '/' . $ticketId . '.' . preg_replace('/[^a-z0-9]+/i', '', $ext);
        @move_uploaded_file($_FILES['evidence']['tmp_name'], $dest);
    }
    $mailer = new Mailer($CFG);
    $to = (string)($CFG['smtp']['notify_email'] ?? ($CFG['site']['merchant_email'] ?? ''));
    if ($to !== '') {
        $html = "<p>Yeni itiraz bileti oluşturuldu.</p><p>Bilet ID: <strong>{$ticketId}</strong></p><p>Mesaj: " . htmlspecialchars($msg) . "</p>";
        $mailer->send($to, 'Yeni İtiraz Bileti: ' . $ticketId, $html);
    }
    Util::jsonResponse(['status' => 'ok', 'message' => 'İtirazınız alınmıştır.']);
}

http_response_code(404);
echo 'Not Found';
