# ROJA Comments v3

ROJA Comments v3 adalah sistem komentar native untuk Joomla yang otomatis muncul di halaman artikel tanpa perlu shortcode atau pengaturan manual per artikel.

## Persyaratan
- Joomla 5.x (direkomendasikan) atau Joomla 4.x
- PHP 8.1 atau lebih tinggi
- MySQL/MariaDB
- Akses admin Joomla untuk instalasi extension

## Apa yang akan terjadi setelah install
- Komponen ROJA Comments v3 terpasang
- Plugin Content - ROJA Comments v3 aktif
- Setiap artikel bisa menampilkan form komentar otomatis
- Pengunjung bisa menulis komentar, membalas, merekomendasikan, dan melaporkan komentar

## Cara install untuk pemula
1. Buka halaman admin Joomla Anda.
2. Masuk ke menu System → Install → Extensions.
3. Klik Upload Package.
4. Pilih file pkg_v3.zip.
5. Tunggu proses instalasi selesai.
6. Jika instalasi berhasil, lanjut ke langkah berikutnya.

## Cara mengaktifkan fitur
1. Buka Extensions → Plugins.
2. Cari plugin Content - ROJA Comments v3.
3. Ubah statusnya menjadi Enabled.
4. Buka Components → ROJA Comments v3 → Options.
5. Atur pengaturan seperti:
   - Enable Comments
   - Allow Guest Comments
   - Require Login
   - Moderate New Comments
   - Max Comment Length
   - Allow Comments By Category

## Cara komentar oleh pengunjung
1. Buka halaman artikel frontend.
2. Scroll ke bawah artikel.
3. Pengunjung akan melihat kotak komentar.
4. Tulis komentar lalu klik tombol kirim.
5. Komentar akan masuk ke database dan muncul di daftar komentar.

## Cara moderasi komentar
1. Masuk ke admin Joomla.
2. Buka Components → ROJA Comments v3.
3. Lihat daftar komentar yang masuk.
4. Approve, reject, spam, atau delete sesuai kebutuhan.

## Jika tidak muncul
- Pastikan plugin Content - ROJA Comments v3 aktif.
- Pastikan component ROJA Comments v3 terinstall dengan benar.
- Pastikan artikel berada di kategori yang diizinkan.
- Clear cache Joomla setelah instalasi.

## Troubleshooting dasar
- Error 500 saat membuka artikel: nonaktifkan plugin sementara, lalu install ulang package terbaru.
- Komentar tidak muncul: periksa kategori artikel dan plugin aktif.
- AJAX gagal: pastikan token Joomla masih valid dan cache dibersihkan.

## Uninstall
1. Buka Extensions → Manage → Manage.
2. Nonaktifkan plugin ROJA Comments v3.
3. Hapus extension ROJA Comments v3 dari daftar install.

## Catatan penting
- Fitur komentar ini dipasang otomatis di artikel tanpa shortcode.
- Package ini sudah dibuat agar lebih aman dan tidak crash saat halaman artikel dibuka.
- Untuk user awam, cukup upload file package, aktifkan plugin, dan buka artikel untuk melihat hasilnya.
