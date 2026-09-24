<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Migrasi Data Lama — LaporanSidak</title>
<style>
  body{ font-family:"Segoe UI", Arial, sans-serif; background:#f4f3ea; color:#1f2b16; max-width:640px; margin:40px auto; padding:0 18px; }
  h1{ font-size:20px; }
  .card{ background:#fffef9; border:1px solid #ecebe2; border-radius:16px; padding:20px; margin-top:16px; }
  ol{ padding-left:20px; line-height:1.7; }
  input[type=file]{ margin:14px 0; }
  button{ padding:11px 18px; border:none; border-radius:10px; background:#5a9430; color:#fff; font-weight:700; cursor:pointer; font-size:14px; }
  button:disabled{ opacity:0.6; cursor:not-allowed; }
  #log{ margin-top:16px; font-size:13px; background:#f1f0e8; border-radius:10px; padding:12px; white-space:pre-wrap; display:none; }
  a.back{ display:inline-block; margin-top:20px; color:#5a9430; font-weight:700; text-decoration:none; }
</style>
</head>
<body>
  <h1>Migrasi Data Lama ke MySQL</h1>
  <div class="card">
    <p>Halaman ini digunakan <strong>sekali saja</strong> untuk memindahkan data lama yang masih
    tersimpan di localStorage browser (versi HTML lama) ke database MySQL aplikasi ini.</p>
    <ol>
      <li>Buka file HTML lama di browser tempat data lama tersimpan.</li>
      <li>Buka DevTools (F12) &rarr; tab Console, lalu jalankan isi file
        <code>migrate_export_console.js</code> (ada di folder project ini).</li>
      <li>Sebuah file <code>sidak_migration_export.json</code> akan otomatis terdownload.</li>
      <li>Upload file tersebut di bawah ini, lalu klik "Mulai Migrasi".</li>
    </ol>
    <input type="file" id="fileInput" accept="application/json">
    <br>
    <button id="startBtn" type="button">Mulai Migrasi</button>
    <div id="log"></div>
  </div>
  <a class="back" href="index.php">&larr; Kembali ke aplikasi</a>

<script>
  const fileInput = document.getElementById('fileInput');
  const startBtn = document.getElementById('startBtn');
  const log = document.getElementById('log');

  startBtn.addEventListener('click', async () => {
    const file = fileInput.files[0];
    if (!file) { alert('Pilih file JSON hasil ekspor terlebih dahulu.'); return; }

    startBtn.disabled = true;
    log.style.display = 'block';
    log.textContent = 'Membaca file...';

    try {
      const text = await file.text();
      const payload = JSON.parse(text);

      log.textContent = 'Mengirim ' + (payload.entries ? payload.entries.length : 0) + ' data ke server...';

      const res = await fetch('api/migrate_import.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json();

      if (!json.success) throw new Error(json.message || 'Migrasi gagal');

      let msg = 'Selesai. Berhasil mengimpor ' + json.imported + ' dari ' + json.total + ' data.';
      if (json.errors && json.errors.length) {
        msg += '\n\nCatatan:\n' + json.errors.join('\n');
      }
      msg += '\n\nSilakan cek data di halaman utama sebelum menghapus data lama di localStorage browser lama.';
      log.textContent = msg;
    } catch (err) {
      log.textContent = 'Gagal: ' + err.message;
    } finally {
      startBtn.disabled = false;
    }
  });
</script>
</body>
</html>
