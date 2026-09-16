# ROJA Portal Template

Template Joomla editorial portal yang dibuat dengan gaya newsroom modern, mirip layout media berita seperti New York Times.

## Gambaran Umum

Template ini dirancang untuk portal berita/editorial dengan struktur seperti:
- header brand dan navigasi
- breaking news bar
- hero utama
- trending stories
- grid berita
- sidebar konten
- footer multi kolom

## Struktur Folder

- `extracted_tpl/` — sumber template Joomla yang sudah diubah
- `constituents/` — file package instalasi module dan template
- `pkg_roja_portal.xml` — manifest package
- `pkg_roja_portal_v2_2_10.zip` — package utama yang siap diupload ke Joomla

## File Penting

- `extracted_tpl/index.php` — layout utama template
- `extracted_tpl/media/css/template.css` — styling utama template
- `extracted_tpl/templateDetails.xml` — metadata template Joomla

## Cara Install

1. Masuk ke administrator Joomla.
2. Buka menu `Extensions > Manage > Install`.
3. Pilih tab `Upload Package File`.
4. Upload file `pkg_roja_portal_v2_2_10.zip`.
5. Tunggu proses instalasi selesai.
6. Buka `Extensions > Templates`.
7. Aktifkan template `roja_portal` dan set sebagai default.

## Catatan Template

Template ini sudah dimodifikasi agar lebih mirip tampilan portal berita, termasuk:
- header dengan hari dan jam
- warna aksen merah editorial
- typo serif untuk judul berita
- layout hero utama yang lebih dramatis
- breaking news strip
- area artikel dan card berita yang lebih bersih

## Review Kode

### Kelebihan
- Struktur layout template sudah cukup rapi.
- Sudah ada pemisahan posisi modul yang jelas.
- Sudah mendukung homepage dan halaman artikel/pencarian dengan layout dasar.
- CSS sudah cukup lengkap untuk kebutuhan portal berita.

### Perbaikan yang Direkomendasikan

1. Ubah teks hardcoded menjadi `Text::_()` agar lebih ramah multi bahasa.
2. Gunakan timezone Joomla untuk tanggal dan jam, bukan `date()` dari server.
3. Pisahkan CSS menjadi beberapa file berdasarkan area seperti layout, module, artikel, responsive.
4. Konsistenkan selector agar tidak berbenturan antar modul.
5. Pastikan metadata template sesuai dengan versi Joomla yang digunakan.
6. Tambahkan bahasa template untuk teks label yang masih hardcoded.

## Rekomendasi Next Step

Untuk tahap berikutnya, template siap dikembangkan menjadi versi yang lebih profesional dengan:
- layout homepage yang lebih dekat dengan NYT
- header yang lebih formal
- modul kategori per bagian
- artikel single page yang lebih premium
- bahasa dan translasi siap dipakai di Joomla

## Hubungan File

- Template utama: `extracted_tpl/`
- Paket install: `pkg_roja_portal_v2_2_0.zip`
- Modul pendukung: `constituents/`

## Sumber Referensi

Template ini dibuat dengan inspirasi dari tampilan portal berita modern dan editorial.
