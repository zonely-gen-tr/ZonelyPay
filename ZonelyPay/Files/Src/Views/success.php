<?php

use Handlers\Lib\Config;
use Handlers\Lib\Util;

require_once __DIR__ . '/../../Handlers/Lib/Config.php';
require_once __DIR__ . '/../../Handlers/Lib/Util.php';
$CFG = Config::load(dirname(__DIR__, 3) . '/config.yml');
$STORE = dirname(__DIR__, 3) . '/Files/Handlers/Storage';
$tid = $_GET['tid'] ?? '';
$txn = Util::readJson($STORE . '/transactions_' . $tid . '.json');
$siteName = $CFG['site']['name'] ?? 'X Sitesi';
$domain = $txn['origin']['domain'] ?? '';
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <title>Ödeme Başarılı</title>
  <style>
    body {
      font-family: system-ui;
      background: #0f1115;
      color: #e6e6e6;
      padding: 40px
    }

    .card {
      background: #161A22;
      border-radius: 16px;
      padding: 24px;
      max-width: 700px;
      margin: 0 auto
    }
  </style>
</head>

<body>
  <div class="card">
    <h2>Ödeme Başarılı</h2>
    <p><?php echo htmlspecialchars($siteName); ?> üzerinde işleminiz başarıyla tamamlandı.</p>
    <p><a href="https://<?php echo htmlspecialchars($domain); ?>" style="color:#7bdc86">Siteye git</a></p>
  </div>
</body>

</html>