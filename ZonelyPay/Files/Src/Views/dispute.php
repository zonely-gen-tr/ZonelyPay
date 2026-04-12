<?php
$tid = $_GET['tid'] ?? '';
?>
<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <title>İtiraz / Destek</title>
  <style>
    body {
      font-family: system-ui;
      background: #0f1115;
      color: #e6e6e6;
      padding: 24px
    }

    .card {
      background: #161A22;
      padding: 24px;
      border-radius: 16px;
      max-width: 720px;
      margin: 0 auto
    }
  </style>
</head>

<body>
  <div class="card">
    <h2>İtiraz / Destek Talebi</h2>
    <form action="/Files/Api/process.php?action=dispute" method="post" enctype="multipart/form-data">
      <input type="hidden" name="tid" value="<?php echo htmlspecialchars($tid); ?>">
      <div>
        <label>Ad Soyad</label><br>
        <input type="text" name="name" required style="width:100%;padding:8px">
      </div>
      <div>
        <label>E-posta</label><br>
        <input type="email" name="email" required style="width:100%;padding:8px">
      </div>
      <div>
        <label>Mesaj</label><br>
        <textarea name="message" rows="5" required style="width:100%;padding:8px"></textarea>
      </div>
      <div>
        <label>Dekont / Ekran Görüntüsü (opsiyonel)</label><br>
        <input type="file" name="evidence">
      </div>
      <div style="margin-top:12px">
        <button type="submit" style="padding:10px 16px;border:0;border-radius:8px;background:#2e7d32;color:#fff">Gönder</button>
      </div>
    </form>
  </div>
</body>

</html>