/**
 * CARA PAKAI:
 * 1. Buka file HTML LAMA (InputLaporanSidakHr_fix_nw...html) di browser
 *    yang sama tempat data lama Anda tersimpan.
 * 2. Buka DevTools (tekan F12), pindah ke tab "Console".
 * 3. Copy-paste SELURUH isi script ini ke console, lalu tekan Enter.
 * 4. Browser akan otomatis mendownload file "sidak_migration_export.json".
 * 5. Buka aplikasi baru di http://localhost/laporan_sidak/migrate.php
 *    lalu upload file JSON tersebut untuk memindahkan data ke MySQL.
 */
(function () {
  try {
    const entries = JSON.parse(localStorage.getItem('sidak_entries_v1') || '[]');
    const settings = JSON.parse(localStorage.getItem('sidak_settings_v1') || '{}');

    const payload = { entries, settings, exportedAt: new Date().toISOString() };
    const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'sidak_migration_export.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    console.log('Berhasil! ' + entries.length + ' data sidak diekspor ke file sidak_migration_export.json');
  } catch (err) {
    console.error('Gagal mengekspor data lama:', err);
  }
})();
