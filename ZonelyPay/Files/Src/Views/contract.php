<?php

use Handlers\Lib\Config;

require_once __DIR__ . '/../../Handlers/Lib/Config.php';
$CFG = Config::load(dirname(__DIR__, 3) . '/config.yml');
$url = $CFG['site']['sales_contract_url'] ?? '';
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <title>Mesafeli Satış Sözleşmesi</title>
  <style>
    html,
    body,
    iframe {
      height: 100%;
      width: 100%;
      margin: 0;
      border: 0;
      padding: 0
    }
  </style>
</head>

<body>
  <?php if ($url): ?>
    <iframe src="<?php echo htmlspecialchars($url); ?>"></iframe>
  <?php else: ?>
    <div style="padding:24px;font-family:system-ui">Sözleşme URL'i yapılandırılmadı.</div>
  <?php endif; ?>
</body>

</html>