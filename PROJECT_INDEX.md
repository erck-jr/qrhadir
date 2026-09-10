# Project Index: Absensi QR Code

## 1. Ikhtisar Proyek
Aplikasi ini adalah sistem manajemen kehadiran (absensi) dan acara berbasis Laravel. Fitur utamanya mencakup pendaftaran acara publik, pembuatan tiket QR Code, pemindaian kehadiran (kiosk), pembuatan ID Card otomatis, hingga penerbitan dan verifikasi sertifikat. Sistem ini juga memiliki manajemen pengguna dengan peran (Admin dan Super Admin). Aplikasi mendukung 3 mode event: **Offline** (menggunakan tiket QR untuk absensi di lokasi), **Online** (absensi otomatis saat registrasi pada jam acara), dan **Hybrid** (peserta dapat memilih untuk hadir secara online atau offline).

## 2. Dependencies Utama (composer.json)
- **PHP**: `^8.2`
- **Laravel Framework**: `^12.0`
- **intervention/image**: `^3.11` (Manipulasi gambar, digunakan untuk ID Card & Sertifikat)
- **simplesoftwareio/simple-qrcode**: `^4.2` (Pembuatan QR Code)
- **maatwebsite/excel**: `^3.1` (Ekspor laporan ke format Excel)
- **laravel-lang/lang**: `^15.26` (Dukungan multibahasa/lokalisasi)

## 3. Struktur Direktori Utama

### A. Controllers (`app/Http/Controllers`)
**Admin**:
- `AttendanceController`: Menangani pemindaian dan pencatatan absensi.
- `AuthController`: Menangani login/logout admin.
- `CertificateController`: Manajemen sertifikat dan penyelesaian laporan masalah sertifikat.
- `DashboardController`: Menampilkan statistik dan ringkasan dashboard.
- `EventController`: CRUD acara, pengaturan status acara, print QR Event, dan transfer kepemilikan.
- `EventParticipantController`: Manajemen peserta dalam suatu acara.
- `EventReportController`: Mengelola tampilan, cetak, dan ekspor laporan acara.
- `IdCardController`: Mengelola unduhan batch dan generate satuan ID Card.
- `IdCardTemplateController`: Manajemen template ID Card per acara.
- `ParticipantController`: CRUD data master peserta.
- `ParticipantTypeController`: Mengelola tipe peserta per acara (misal: Peserta, Panitia, VIP).
- `ProfileController`: Manajemen profil dan ganti password.
- `SettingController`: Pengaturan umum aplikasi (Super Admin).
- `UserController`: Manajemen akun admin/user (Super Admin).

**Public**:
- `CertificateController`: Halaman pencarian, tampilan, unduh, dan pelaporan masalah sertifikat publik.
- `EventRegistrationController`: Registrasi acara, pengecekan tiket, dan generate ID card mandiri.
- `PageController`: Halaman utama, daftar acara, dan portal pengecekan.
- `RegistrationController`: Registrasi akun baru (jika dibuka untuk publik).

### B. Models (`app/Models`)
- `Attendance`: Menyimpan data kehadiran peserta.
- `AttendanceQrToken`: Token QR yang digunakan untuk absensi / tiket.
- `CertificateReport`: Laporan terkait sertifikat yang bermasalah.
- `Event`: Data acara yang diselenggarakan.
- `EventParticipant`: Data relasi antara acara dan peserta (pendaftaran).
- `EventTemplate`: Template sertifikat/ID Card terkait suatu acara.
- `IdCardTemplate`: Data template visual untuk pembuatan ID Card.
- `Participant`: Data master profil peserta.
- `ParticipantType`: Tipe-tipe peserta yang dapat dikategorikan.
- `Setting`: Pengaturan aplikasi global.
- `Signature`: Tanda tangan digital untuk sertifikat.
- `User`: Data pengguna (Admin/Super Admin).

### C. Services (`app/Services`)
- `CertificateGenerator.php`: Logika bisnis pemrosesan manipulasi gambar (Intervention Image) untuk menggambar teks/nama pada template sertifikat.
- `IdCardService.php`: Logika bisnis pemrosesan manipulasi gambar untuk merender ID Card, memposisikan foto, nama, dan QR Code.

### D. Traits (`app/Traits`)
- `HasOwnership.php`: Membatasi akses atau scope query hanya untuk data milik user (admin) yang sedang login.
- `HasRelatedOwnership.php`: Membatasi akses relasi data berdasarkan kepemilikan parent (contoh: peserta di dalam event milik admin tertentu).

### E. Helpers (`app/Helpers`)
- `helpers.php`: Kumpulan fungsi bantuan global yang dapat dipanggil di seluruh aplikasi.

### F. Exports (`app/Exports`)
- `AttendanceExport.php`: Konfigurasi ekspor data kehadiran peserta ke format Excel menggunakan Maatwebsite Excel.

### G. Routing (`routes`)
- `web.php`: Seluruh route aplikasi (Publik & Admin), mencakup middleware autentikasi, pengecekan peran (Super Admin), dan pengelompokan fungsi.

### H. Views (`resources/views`)
- `admin/`: Halaman dan panel kontrol untuk pengguna yang login.
- `auth/`: Tampilan otentikasi (login, dll).
- `components/`: Komponen Blade yang dapat digunakan kembali (misalnya tombol, modal, form input).
- `layouts/`: Struktur kerangka halaman (layout admin, layout guest, dsb).
- `pages/`: Halaman front-end publik (beranda, pengecekan, dsb).
- `public/`: File tampilan untuk fitur spesifik yang dapat diakses publik.

## 4. Gambaran Alur Proses Bisnis

1. **Pembuatan Acara (Admin)**: Admin membuat acara baru, mengatur tanggal, dan membuat/mengunggah desain template ID Card serta Sertifikat.
2. **Pendaftaran (Publik)**: Peserta mengunjungi halaman publik, mendaftarkan diri pada acara tertentu.
3. **Penerbitan Tiket & ID Card**: Setelah mendaftar, peserta mendapatkan QR Token sebagai tiket. Peserta dapat melihat/mengunduh ID Card mereka. Admin juga dapat mengunduh ID Card secara massal (batch).
4. **Hari H - Absensi**: Di lokasi acara, panitia menggunakan fitur Scan Kiosk untuk memindai QR Code peserta (dari ID card atau HP). Data masuk ke model `Attendance`.
5. **Laporan & Ekspor**: Admin dapat memantau kehadiran, mencetak laporan, dan mengekspornya ke Excel.
6. **Penerbitan Sertifikat**: Pasca acara, sistem/admin memfasilitasi penerbitan sertifikat digital. Peserta dapat mencari, memverifikasi, dan mengunduh sertifikatnya secara online.
7. **Resolusi Masalah**: Jika ada kesalahan nama di sertifikat, peserta dapat mengirim laporan. Admin dapat menindaklanjutinya di dashboard.
