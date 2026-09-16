Anda adalah **Senior Joomla 6 Extension Developer, PHP 8 Engineer, JavaScript Engineer, dan UI/UX Engineer**.

Website saya menggunakan:

- Joomla **6.1.3**
- Template custom: `tpl_roja_portal`
- Component artikel: `com_content`
- Website: `suraen.xyz`

Saya sedang mengembangkan sistem komentar **ROJA Comments**.

## PERUBAHAN UTAMA

Perbaiki sistem komentar agar **form dan daftar komentar TIDAK langsung ditampilkan di bawah artikel**.

Saya ingin sistem komentar menggunakan **right-side drawer / off-canvas comments panel**.

Ketika pembaca menekan tombol:

`💬 24 Komentar`

panel komentar harus **slide masuk dari sisi kanan layar**.

Konsep interaksi:

ARTICLE PAGE

```text
┌─────────────────────────────────────────────┐
│ Judul Artikel                               │
│                                             │
│ Isi artikel...                              │
│                                             │
│ ♡ Rekomendasikan   💬 24 Komentar   Bagikan│
└─────────────────────────────────────────────┘
                                  ↓ klik
```

Kemudian:

```text
Desktop
┌──────────────────────────────┬──────────────────────────┐
│                              │  24 Komentar          × │
│                              │  pada "Judul Artikel"   │
│                              │──────────────────────────│
│         ARTICLE              │ Bagikan pendapat Anda...│
│                              │──────────────────────────│
│                              │ Pilihan Pembaca | Semua │
│                              │ Urutkan: Terbaru        │
│                              │──────────────────────────│
│                              │ Avatar  Suraen          │
│                              │ 5 menit lalu            │
│                              │                         │
│                              │ Isi komentar...         │
│                              │                         │
│                              │ ♡ 12   Balas   Bagikan  │
│                              │──────────────────────────│
│                              │ Komentar berikutnya...  │
└──────────────────────────────┴──────────────────────────┘
```

Panel harus berada di sisi kanan viewport, bukan menjadi bagian dari lebar container artikel.

# 1. DRAWER

Buat struktur original dengan namespace ROJA:

```html
<div class="roja-comments-overlay"></div>

<aside
    class="roja-comments-drawer"
    id="roja-comments-drawer"
    aria-hidden="true"
    aria-labelledby="roja-comments-title"
>
    ...
</aside>
```

Jangan copy HTML/CSS website lain.

Gunakan CSS sendiri.

Default:

```css
.roja-comments-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: min(520px, 100vw);
    height: 100dvh;
    background: var(--roja-comments-bg, #fff);
    z-index: 10001;
    transform: translateX(100%);
    transition: transform .3s ease;
    overflow: hidden;
}

.roja-comments-drawer.is-open {
    transform: translateX(0);
}
```

Buat overlay:

```css
.roja-comments-overlay {
    position: fixed;
    inset: 0;
    background: rgb(0 0 0 / .35);
    opacity: 0;
    visibility: hidden;
    transition:
        opacity .3s ease,
        visibility .3s ease;
    z-index: 10000;
}

.roja-comments-overlay.is-open {
    opacity: 1;
    visibility: visible;
}
```

Sesuaikan implementasi final agar production-ready.

# 2. RESPONSIVE

Desktop:

- width drawer sekitar `480–560px`
- maksimal sekitar `40–45vw`
- muncul dari kanan
- artikel di belakang tetap terlihat
- overlay transparan

Tablet:

- sekitar `70vw`

Mobile:

```css
@media (max-width: 767px) {
    .roja-comments-drawer {
        width: 100%;
        max-width: none;
    }
}
```

Pada smartphone panel menjadi full-screen.

Tidak boleh terjadi horizontal scrolling.

# 3. OPEN COMMENTS

Di bawah artikel buat action bar:

```text
♡ Rekomendasikan    💬 24 Komentar    ↗ Bagikan
```

Tombol komentar harus berupa `<button>`, bukan link palsu.

Contoh:

```html
<button
    type="button"
    class="roja-comments-trigger"
    aria-controls="roja-comments-drawer"
    aria-expanded="false"
>
    <span class="roja-comments-icon"></span>
    <span class="roja-comments-count">24</span>
    Komentar
</button>
```

Ketika diklik:

1. tampilkan overlay
2. slide drawer dari kanan
3. set `aria-hidden="false"`
4. set trigger `aria-expanded="true"`
5. lock scrolling pada body
6. load komentar jika belum dimuat
7. pindahkan focus ke drawer

Jangan reload halaman.

# 4. CLOSE DRAWER

Drawer dapat ditutup menggunakan:

- tombol `×`
- klik overlay
- tombol Escape
- gesture/back behavior yang masuk akal pada mobile

Ketika ditutup:

```text
transform: translateX(100%)
```

Kemudian kembalikan focus ke tombol Komentar.

Restore body scrolling.

# 5. HEADER DRAWER

Header harus sticky:

```text
24 Komentar pada
"Jejak Budaya Kopi Biara Yaman"

                                  ×
```

Contoh struktur:

```html
<header class="roja-comments-header">

    <div>
        <strong>
            <span class="roja-comments-total">24</span>
            Komentar pada
        </strong>

        <div class="roja-comments-article-title">
            Judul Artikel
        </div>
    </div>

    <button
        type="button"
        class="roja-comments-close"
        aria-label="Tutup komentar"
    >
        ×
    </button>

</header>
```

Header tetap berada di atas ketika daftar komentar di-scroll.

# 6. FORM KOMENTAR

Tepat di bawah header tampilkan:

```text
┌──────────────────────────────────┐
│ Bagikan pendapat Anda...         │
└──────────────────────────────────┘
```

Awalnya boleh berupa compact composer.

Ketika diklik/focus, expand menjadi:

```text
Bagikan pendapat Anda...

────────────────────────────────────

0 / 2000

                    Batal   Kirim
```

Untuk guest apabila guest comments diaktifkan:

```text
Nama
Email

Bagikan pendapat Anda...

0 / 2000

                    Batal   Kirim
```

Untuk user Joomla login, jangan minta nama/email lagi.

Gunakan identitas Joomla user.

# 7. FORM HARUS AJAX

Komentar tidak boleh membuat browser reload.

Gunakan `fetch()` + Joomla endpoint yang sesuai dengan implementasi extension.

Request harus memiliki Joomla CSRF token.

State tombol:

```text
Kirim
↓
Mengirim...
↓
Berhasil
```

Jika moderasi aktif:

`Komentar Anda telah dikirim dan menunggu moderasi.`

Jika langsung publish:

tambahkan komentar baru ke UI tanpa reload halaman.

# 8. BODY DRAWER

Gunakan struktur:

```text
HEADER
──────────────────────────

COMMENT FORM

──────────────────────────

Pilihan Pembaca | Semua

                Urutkan: Terbaru

──────────────────────────

COMMENTS LIST
```

Area list harus scrollable.

Header jangan ikut hilang.

Gunakan layout flex:

```css
.roja-comments-drawer {
    display: flex;
    flex-direction: column;
}

.roja-comments-header {
    flex: 0 0 auto;
}

.roja-comments-body {
    flex: 1 1 auto;
    overflow-y: auto;
    overscroll-behavior: contain;
}
```

# 9. COMMENT CARD

Desain:

```text
 S       Suraen
         Lombok · 5 menit lalu

         Menarik sekali bagaimana sejarah
         kopi Yaman berkembang...

         ♥ 12 Rekomendasi    Balas    Bagikan    ⋯
```

Avatar user yang tidak mempunyai foto menggunakan inisial.

Contoh:

```text
S
```

Avatar harus berbentuk lingkaran.

# 10. REPLY

Ketika:

`Balas`

diklik, jangan membuka halaman lain.

Tampilkan inline reply form:

```text
↳ Membalas Suraen

   Tulis balasan...

                 Batal  Balas
```

Setelah berhasil, reply langsung muncul di bawah parent comment.

Batasi nesting default menjadi 2 level.

# 11. RECOMMEND

Klik:

`♡ Rekomendasikan`

berubah menjadi:

`♥ 13`

tanpa reload.

Server harus mencegah vote duplikat.

Jangan mengandalkan JavaScript saja.

# 12. TAB

Tambahkan:

```text
Pilihan Pembaca | Semua
```

Default:

`Semua`

Pilihan Pembaca menggunakan ranking engagement berdasarkan data server.

Tab tidak boleh reload keseluruhan artikel.

# 13. SORTING

Tambahkan dropdown:

```text
Urutkan: Terbaru ▼
```

Pilihan:

- Terbaru
- Terlama
- Paling Direkomendasikan

Reload hanya daftar komentar melalui AJAX.

# 14. LOAD MORE

Jangan load ratusan komentar sekaligus.

Default:

20 komentar.

Di bawah:

`Muat komentar lainnya`

Ketika ditekan, fetch halaman berikutnya dan append ke list.

# 15. DEEP LINK

Support:

`#comment-123`

Jika URL artikel dibuka dengan fragment komentar:

1. buka drawer otomatis
2. load komentar terkait
3. scroll ke komentar
4. highlight sementara komentar tersebut

# 16. SHARE

Tombol Bagikan pada komentar menghasilkan permalink:

`ARTICLE_URL#comment-ID`

Gunakan Clipboard API.

Tampilkan toast:

`Tautan komentar disalin.`

# 17. REPORT

Menu:

`⋯`

menampilkan:

```text
Laporkan komentar
```

Alasan:

- Spam
- Pelecehan
- Ujaran kebencian
- Informasi pribadi
- Tidak relevan
- Lainnya

Kirim report melalui AJAX.

# 18. JOOMLA 6.1.3

INI SANGAT PENTING.

Website menggunakan:

**Joomla 6.1.3**

Jangan menghasilkan extension yang hanya diasumsikan kompatibel dengan Joomla 4 atau Joomla 5.

Audit semua:

- PHP namespaces
- extension manifest
- service provider
- dependency injection
- Joomla events
- WebAssetManager
- DatabaseInterface
- MVC
- Router
- Session
- User
- HTMLHelper
- Text
- Factory jika masih tepat
- deprecated API
- plugin architecture

Prioritaskan Joomla **6.1.3**.

Jangan menggunakan API Joomla lama apabila sudah deprecated/removed pada Joomla 6.

Target PHP harus mengikuti requirement Joomla 6.1.3 yang terpasang.

# 19. WEB ASSETS

Jangan hardcode:

```html
<script src="/templates/...">
```

Gunakan Joomla WebAssetManager atau asset registration extension yang sesuai.

Assets:

```text
media/com_rojacomments/css/comments.css
media/com_rojacomments/js/comments.js
```

Load asset **hanya pada halaman detail artikel yang memiliki komentar aktif**.

Jangan load comments JS pada semua halaman jika tidak diperlukan.

# 20. CONTENT PLUGIN

Plugin:

`plg_content_rojacomments`

harus mendeteksi:

`com_content.article`

Gunakan Joomla content event yang kompatibel dengan Joomla 6.1.3.

Plugin memasukkan hanya:

1. article engagement/action bar
2. comments drawer markup
3. asset registration
4. konfigurasi JS yang diperlukan

Jangan render daftar komentar server-side seluruhnya jika lazy AJAX loading lebih efisien.

# 21. JANGAN MENYEBABKAN HTTP 500

Website sebelumnya mengalami:

`500 Internal Server Error`

pada URL detail artikel seperti:

`/index.php/component/content/article/...`

Karena itu semua perubahan harus diuji khusus terhadap `com_content.article`.

Jangan berasumsi `$article`, `$item`, `$article->id`, `$article->catid`, atau event property selalu tersedia.

Validasi context dan object sebelum digunakan.

Jangan menggunakan:

```php
try {
   // ...
} catch (\Throwable $e) {
}
```

untuk sekadar menyembunyikan fatal error.

Cari dan perbaiki root cause.

# 22. TEMPLATE ROJA PORTAL

Jangan membuat komentar bergantung penuh pada:

`tpl_roja_portal`

Extension harus tetap berfungsi pada template Joomla lain.

Tetapi CSS harus menyatu dengan desain ROJA Portal.

Gunakan namespace:

```css
.roja-comments-*
```

Hindari:

```css
button {}
p {}
aside {}
body {}
```

yang dapat merusak CSS template.

# 23. BODY SCROLL LOCK

Saat drawer terbuka:

```css
body.roja-comments-open {
    overflow: hidden;
}
```

Namun implementasikan dengan hati-hati agar posisi scroll artikel tidak meloncat.

Ketika drawer ditutup, pembaca harus kembali ke posisi artikel sebelumnya.

# 24. ACCESSIBILITY

Implementasikan:

- `aria-expanded`
- `aria-controls`
- `aria-hidden`
- accessible close button
- keyboard Escape
- focus management
- focus-visible
- semantic button
- form label
- error announcements

Drawer harus dapat digunakan hanya dengan keyboard.

Jika menggunakan dialog semantics, implementasikan ARIA secara benar.

# 25. ANIMATION

Gunakan animasi halus sekitar:

`250–320ms`

Tambahkan:

```css
@media (prefers-reduced-motion: reduce) {
    .roja-comments-drawer,
    .roja-comments-overlay {
        transition: none;
    }
}
```

# 26. LOADING STATE

Ketika drawer pertama kali dibuka:

```text
24 Komentar pada
"Judul Artikel"

Bagikan pendapat Anda...

────────────────────

Memuat komentar...
```

Gunakan skeleton sederhana jika sesuai.

Jika AJAX gagal:

```text
Komentar gagal dimuat.

[Coba Lagi]
```

Jangan membuat seluruh halaman artikel error hanya karena endpoint komentar gagal.

# 27. EMPTY STATE

Jika belum ada komentar:

```text
Belum ada komentar.

Jadilah yang pertama memberikan pendapat.
```

Form tetap tersedia.

# 28. SECURITY

Wajib:

- Joomla CSRF protection
- server-side validation
- output escaping
- prepared Joomla database queries
- XSS prevention
- rate limiting
- honeypot
- duplicate submission prevention
- permission checking
- sanitize comment
- jangan menerima arbitrary HTML

Frontend validation bukan pengganti server-side validation.

# 29. ERROR ISOLATION

Sistem komentar tidak boleh membuat artikel menghasilkan HTTP 500 hanya karena:

- database comments belum tersedia
- endpoint gagal
- tidak ada komentar
- user guest
- user belum login
- asset gagal
- konfigurasi belum lengkap

Untuk kondisi recoverable, tampilkan UI error komentar tanpa merusak rendering artikel.

Untuk masalah instalasi/database yang serius, log error menggunakan Joomla logging mechanism.

# 30. OUTPUT

Jangan hanya memberikan contoh.

Perbaiki source code extension yang saya berikan.

Output akhir harus mencakup:

1. PHP Joomla 6.1.3 yang diperbaiki
2. Content plugin
3. Component/API endpoint
4. `comments.js`
5. `comments.css`
6. AJAX create comment
7. AJAX list comments
8. Reply
9. Recommend
10. Share
11. Report
12. Sorting
13. Load More
14. Right-side drawer
15. Responsive mobile UI
16. SQL/index jika diperlukan
17. Manifest Joomla 6.1.3
18. File/package installer

Jika saya memberikan source ZIP existing, **edit source tersebut**, jangan membuat proyek lain yang tidak terhubung.

Pertahankan fitur yang sudah bekerja.

Setelah modifikasi lakukan pemeriksaan:

- PHP syntax
- XML manifest
- namespace
- Joomla 6.1.3 compatibility
- database queries
- CSRF
- JS syntax
- CSS
- mobile responsiveness
- `com_content.article`
- logged-in user
- guest
- artikel dengan 0 komentar
- artikel dengan banyak komentar

Target akhir:

**Klik "Komentar" → drawer muncul dari kanan → user dapat membaca/menulis/membalas komentar tanpa meninggalkan halaman artikel.**

Dan yang paling penting:

**Halaman artikel harus tetap HTTP 200 dan tidak menghasilkan Internal Server Error.**