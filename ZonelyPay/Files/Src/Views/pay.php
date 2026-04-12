<?php

use Handlers\Lib\Config;
use Handlers\Lib\Util;

require_once __DIR__ . '/../../Handlers/Lib/Config.php';
require_once __DIR__ . '/../../Handlers/Lib/Util.php';
$CFG = Config::load(dirname(__DIR__, 3) . '/config.yml');
$STORE = dirname(__DIR__, 3) . '/Files/Handlers/Storage';
$tid = $_GET['tid'] ?? '';
$txn = Util::readJson($STORE . '/transactions_' . $tid . '.json');
if (!$txn) {
  http_response_code(404);
  exit('Transaction not found');
}
$siteName = $CFG['site']['name'] ?? 'X Sitesi';
$logo = $CFG['site']['logo_url'] ?? '';
$contractUrl = $CFG['site']['sales_contract_url'] ?? '';
$price = $txn['order']['price'] ?? '0.00';
$domain = $txn['origin']['domain'] ?? '';
$provider = strtolower((string)($txn['order']['provider_name'] ?? ''));
$gw = (int)($txn['order']['gateway_type_id'] ?? 0);
$weepay_iframe = $CFG['payments']['weepay']['iframe_url'] ?? '';
$paytr_iframe  = $CFG['payments']['paytr']['iframe_url'] ?? '';
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ödeme • <?php echo htmlspecialchars($siteName); ?></title>
  <style>
    body {
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu;
      margin: 0;
      background: #0f1115;
      color: #e6e6e6
    }

    .wrap {
      max-width: 960px;
      margin: 40px auto;
      padding: 24px
    }

    .card {
      background: #161A22;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 6px 30px rgba(0, 0, 0, .25)
    }

    .top {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 16px
    }

    .logo {
      height: 36px
    }

    .bar {
      background: #1f2430;
      padding: 16px;
      border-radius: 12px;
      margin: 16px 0;
      display: flex;
      justify-content: space-between;
      align-items: center
    }

    .muted {
      color: #a8b0bb;
      font-size: .95rem
    }

    .iframe-wrap {
      background: #0c0e12;
      border: 1px solid #283141;
      border-radius: 12px;
      overflow: hidden;
      min-height: 520px
    }

    a.btn {
      display: inline-block;
      padding: 10px 16px;
      background: #2e7d32;
      color: #fff;
      text-decoration: none;
      border-radius: 8px
    }

    .foot {
      margin-top: 18px;
      font-size: .9rem;
      color: #9aa3ad
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="top">
      <?php if ($logo): ?><img class="logo" src="<?php echo htmlspecialchars($logo); ?>" alt="logo"><?php endif; ?>
      <h1 style="margin:0;font-size:1.4rem;">Güvenli Ödeme</h1>
    </div>
    <div class="bar">
      <div class="muted"><strong><?php echo htmlspecialchars($siteName); ?></strong> üzerinde <strong><?php echo htmlspecialchars($price); ?> TL</strong> tutarında ödeme gerçekleştiriyorsunuz.</div>
      <div><small class="muted">Alan adı: <?php echo htmlspecialchars($domain); ?></small></div>
    </div>

    <div class="card iframe-wrap">
      <?php
      $src = '';
      if ($CFG['payments']['weepay']['enabled'] ?? false) {
        if (stripos($provider, 'weepay') !== false) $src = $weepay_iframe;
      }
      if (!$src && ($CFG['payments']['paytr']['enabled'] ?? false)) {
        if (stripos($provider, 'paytr') !== false) $src = $paytr_iframe;
      }
      if (!$src && ($CFG['payments']['bank_transfer']['enabled'] ?? false)) {
        echo '<div style="padding:24px">';
        echo '<h3>Havale/EFT (IBAN) ile Ödeme</h3>';
        if (!empty($CFG['payments']['bank_transfer']['accounts'])) {
          echo '<ul>';
          foreach ($CFG['payments']['bank_transfer']['accounts'] as $acc) {
            echo '<li><strong>' . htmlspecialchars($acc['bank'] ?? '') . '</strong> - IBAN: ' . htmlspecialchars($acc['iban'] ?? '') . ' (' . htmlspecialchars($acc['holder'] ?? '') . ')</li>';
          }
          echo '</ul>';
        }
        echo '<p class="muted">Ödeme dekontunu <a href="/Files/Src/Views/dispute.php?tid=' . urlencode($tid) . '">itiraz/ekle</a> formundan yükleyebilirsiniz.</p>';
        echo '</div>';
      } elseif ($src) {
        $src = str_replace(
          ['{TID}', '{AMOUNT}', '{EMAIL}', '{USERID}'],
          [urlencode($tid), urlencode($price), urlencode($txn['origin']['user_email'] ?? ''), urlencode((string)($txn['origin']['user_id'] ?? ''))],
          $src
        );
        echo '<iframe src="' . htmlspecialchars($src) . '" width="100%" height="600" frameborder="0" allowpaymentrequest></iframe>';
      } else {
        echo '<div style="padding:24px">Seçilen ödeme sağlayıcısı henüz yapılandırılmamış.</div>';
      }
      ?>
    </div>

    <div class="foot">
      <?php if ($contractUrl): ?>
        <div>Satış sözleşmesi: <a class="btn" href="<?php echo htmlspecialchars($contractUrl); ?>" target="_blank" rel="noopener">Görüntüle</a></div>
      <?php else: ?>
        <div>Satış sözleşmesi yapılandırılmadı.</div>
      <?php endif; ?>
      <div style="margin-top:8px;">İtiraz hakkınız ödeme tarihinden itibaren en az 3 gün boyunca geçerlidir. <a href="/Files/Src/Views/dispute.php?tid=<?php echo urlencode($tid); ?>">İtiraz formu</a></div>
    </div>
  </div>
</body>

</html>