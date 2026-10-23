# Dokumentasi API

API ini menyediakan data dan operasi untuk aplikasi Ruang Tukar Guru DKI Jakarta. Semua endpoint memakai prefix `/api` dan mengembalikan JSON. Klien eksternal menggunakan personal access token Laravel Sanctum, sementara aplikasi web same-origin tetap dapat memakai sesi dan perlindungan CSRF melalui middleware stateful Sanctum. Body permintaan umumnya JSON, kecuali unggahan CSV.

## Autentikasi dan format

- Kirim `POST /api/login` dengan `email` dan `password`. `device_name` opsional dan hanya menjadi label token; jika tidak dikirim, label `api-client` digunakan. Respons memberikan token Sanctum yang hanya ditampilkan sekali.
- Sertakan `Authorization: Bearer <token>` dan `Accept: application/json` pada setiap endpoint terproteksi.
- Token berlaku selama 30 hari. Kirim `DELETE /api/logout` dengan token tersebut untuk mencabut token yang sedang dipakai.
- Token guru hanya memiliki ability `guru`; token admin hanya memiliki ability `admin`. Setiap route memeriksa ability token dan role akun, dan endpoint guru juga memerlukan email terverifikasi.
- Untuk endpoint dengan body JSON, kirim `Content-Type: application/json`. Endpoint CSV menerima `multipart/form-data`.
- Contoh login:

```http
POST /api/login
Accept: application/json
Content-Type: application/json

{
  "email": "guru@example.test",
  "password": "password"
}
```

Respons sukses berisi `data.token`, `data.token_type`, `data.abilities`, dan `data.expires_at`. Simpan token secara aman di aplikasi klien; jangan masukkan ke log atau bagikan.

## Respons dan status

- Respons sukses umumnya membungkus hasil dalam properti `data`; beberapa operasi juga mengembalikan `message`.
- Validasi mengembalikan `422` dengan `message` dan `errors`. Konflik keadaan data mengembalikan `409`.
- Token tidak valid/kedaluwarsa mengembalikan `401`; role atau ability yang tidak sesuai mengembalikan `403`.
- Email reset yang dibatasi mengembalikan `429`. Kegagalan pengiriman email kecocokan atau layanan data kelurahan dapat mengembalikan `502`.
- API belum memiliki prefix versi atau pagination.

## API guru

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| `POST` | `/api/login` | Tukar kredensial akun menjadi token Bearer Sanctum. |
| `DELETE` | `/api/logout` | Cabut token Bearer yang sedang dipakai. |
| `GET` | `/api/sudins` | Daftar Sudin untuk guru. |
| `GET` | `/api/sudins/{sudin}/districts` | Daftar kecamatan dalam Sudin. |
| `GET` | `/api/sudins/{sudin}/districts/{district}/villages` | Daftar kelurahan dalam kecamatan Sudin; data diambil dari layanan wilayah eksternal. |
| `GET` | `/api/profile` | Profil guru saat ini; `data` bernilai `null` jika belum ada. |
| `POST` | `/api/profile` | Membuat atau memperbarui profil. Batas 10 permintaan per menit. |
| `GET` | `/api/teachers` | Daftar guru lain dengan jabatan asal sama seperti profil pemanggil. |
| `GET` | `/api/matches` | Daftar calon tukeran yang cocok dua arah. Batas 30 permintaan per menit. |
| `PATCH` | `/api/teacher-profile/status` | Memperbarui status mutasi profil. |
| `POST` | `/api/teacher-profile/deletion-request` | Mengajukan penghapusan profil yang sudah mutasi. |

### Kecamatan dan kelurahan Sudin

Gunakan `GET /api/sudins/{sudin}/districts` untuk daftar kecamatan Sudin. Untuk memuat kelurahan dari kecamatan tertentu, kirim `GET /api/sudins/{sudin}/districts/{district}/villages`; `{district}` adalah kode kecamatan seperti `31.75.07`. Kedua endpoint memerlukan Bearer token guru. Endpoint kelurahan hanya menerima kecamatan yang terdaftar pada Sudin tersebut dan mengembalikan `502` bila layanan data wilayah sedang tidak tersedia.

### Membuat atau memperbarui profil

`POST /api/profile` menerima JSON berikut. Semua field wajib kecuali ditandai opsional.

| Field | Tipe / nilai |
| --- | --- |
| `name` | string, 2–120 karakter |
| `phone` | string, 8–24 karakter; hanya angka, spasi, `+`, `()`, titik, dan tanda hubung |
| `employment_type` | `PNS`, `PPPK`, atau `KKI` |
| `school_name` | string, maksimal 160 karakter |
| `school_address` | string, maksimal 1000 karakter |
| `sudin_id` | ID Sudin asal |
| `position` | `guru_kelas` atau `guru_mapel` |
| `level` | `SD`, `SMP`, `SMA`, atau `SMK` |
| `subject_id` | opsional; wajib jika `position` adalah `guru_mapel` |
| `destination_position` | `guru_kelas` atau `guru_mapel` |
| `destination_levels` | array jenjang tujuan; wajib, minimal satu. Untuk `guru_kelas`, harus hanya `SD`; `guru_mapel` dapat memilih beberapa jenjang |
| `destination_subject_ids` | opsional; minimal satu ID jika posisi tujuan `guru_mapel` |
| `destination_sudin_id` | ID Sudin tujuan |
| `destination_district_codes` | opsional; array kode kecamatan tujuan. Kosong/tidak dikirim berarti semua kecamatan dalam Sudin tujuan |
| `province_code` | harus `31` (DKI Jakarta) |
| `regency_code`, `regency_name` | kode dan nama kabupaten/kota asal |
| `district_code`, `district_name` | kode dan nama kecamatan asal |
| `village_code`, `village_name` | kode dan nama kelurahan asal |

Kode wilayah harus sesuai hierarki DKI Jakarta, kecamatan asal harus berada dalam Sudin asal, dan setiap kecamatan tujuan harus berada dalam Sudin tujuan. Nomor telepon disimpan dalam format `+62...`.

```json
{
  "name": "Guru Contoh",
  "phone": "081234567890",
  "employment_type": "PNS",
  "school_name": "SDN Contoh 01",
  "school_address": "Jakarta Timur",
  "sudin_id": 1,
  "position": "guru_kelas",
  "level": "SD",
  "destination_position": "guru_kelas",
  "destination_levels": ["SD"],
  "destination_sudin_id": 2,
  "destination_district_codes": ["31.75.07"],
  "province_code": "31",
  "regency_code": "31.75",
  "regency_name": "Kota Administrasi Jakarta Timur",
  "district_code": "31.75.07",
  "district_name": "Cipayung",
  "village_code": "31.75.07.1001",
  "village_name": "Cipayung"
}
```

Profil baru mengembalikan `201 Created`; pembaruan mengembalikan `200 OK`. Data profil menyertakan relasi Sudin, kecamatan tujuan, jenjang, dan mapel terkait.

### Mencari kecocokan

`GET /api/matches` dapat menerima query opsional `candidate_origin_district_code=31.75.07`. Kecamatan filter harus berada dalam Sudin tujuan pemanggil. Hasil hanya mencakup kecocokan dua arah berdasarkan Sudin, jabatan, jenjang, mapel, dan batas kecamatan. Nama kandidat tetap disamarkan; nomor kontak disertakan hanya untuk kecocokan dua arah dan karena endpoint ini hanya dapat dipakai guru terautentikasi.

### Daftar guru dengan jabatan yang sama

`GET /api/teachers` mengembalikan guru lain yang `position`-nya sama dengan jabatan saat ini pada profil pemanggil (`guru_kelas` atau `guru_mapel`). Hasil mengecualikan profil sendiri, guru yang sudah mutasi, dan akun yang emailnya belum terverifikasi. Endpoint ini berbeda dari `/api/matches`: daftar ini tidak mensyaratkan kecocokan dua arah. Nama guru lain disamarkan dengan mempertahankan huruf pertama dan terakhir setiap bagian nama (contoh `M****a G**j`). Nomor telepon, email, dan alamat sekolah tidak disertakan; profil dan jabatan harus sudah dilengkapi.

### Status dan pengajuan penghapusan

```json
{ "is_mutated": true }
```

Kirim body tersebut ke `PATCH /api/teacher-profile/status`. Nilai harus boolean. Profil hanya dapat mengajukan penghapusan setelah ditandai sudah mutasi:

```http
POST /api/teacher-profile/deletion-request
```

Respons sukses berstatus `201` dan berisi ID serta status pengajuan. Pengajuan yang sudah menunggu diproses tidak dibuat ulang.

## API admin

Semua endpoint berikut memerlukan peran `admin`.

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| `GET` | `/api/admin/districts` | Daftar kecamatan DKI yang tersimpan. |
| `GET`, `POST` | `/api/admin/sudins` | Daftar Sudin; tambah Sudin beserta cakupan kecamatannya. |
| `PUT`, `DELETE` | `/api/admin/sudins/{sudin}` | Ubah atau hapus Sudin. Sudin yang masih dipakai profil tidak dapat dihapus (`409`). |
| `GET`, `POST` | `/api/admin/subjects` | Daftar atau tambah mapel. |
| `PUT`, `DELETE` | `/api/admin/subjects/{subject}` | Ubah atau hapus mapel. Mapel yang dipakai profil tidak dapat dihapus (`409`). |
| `POST` | `/api/admin/subjects/import` | Impor CSV dengan file form-data bernama `file` (maksimal 512 KB). |
| `PATCH` | `/api/admin/users/{user}/verify-email` | Verifikasi email user. |
| `PATCH` | `/api/admin/users/{user}/reset-password` | Kirim tautan reset password ke email user; tidak menetapkan password bersama. |
| `DELETE` | `/api/admin/users/{user}` | Hapus permanen akun dan data profil terkait; tidak dapat menghapus diri sendiri atau admin terakhir. |
| `PATCH` | `/api/admin/teachers/{teacherProfile}/status` | Ubah status mutasi profil guru. |
| `POST` | `/api/admin/profile-deletion-requests/{deletionRequest}/review` | Tinjau pengajuan penghapusan. |
| `POST` | `/api/admin/match-notifications/scan` | Cari kecocokan baru dan kirim notifikasi email. |
| `POST` | `/api/admin/match-notifications/send-pending` | Kirim semua notifikasi yang masih tertunda. |
| `POST` | `/api/admin/match-notifications/{pair}/send` | Kirim ulang notifikasi untuk ID pasangan. |

### Cakupan Sudin

Body `POST /api/admin/sudins` dan `PUT /api/admin/sudins/{sudin}`:

```json
{
  "name": "Sudin Pendidikan Jakarta Timur Wilayah II",
  "abbreviation": "JT II",
  "districts": [
    {
      "code": "31.75.07",
      "name": "Cipayung",
      "regency_code": "31.75",
      "regency_name": "Kota Administrasi Jakarta Timur"
    }
  ]
}
```

`abbreviation` opsional. Semua kecamatan harus berkode DKI dan satu kecamatan hanya dapat dimiliki satu Sudin. Kecamatan yang menjadi asal profil aktif tidak dapat dikeluarkan dari Sudin.

### Mapel

Body `POST /api/admin/subjects` dan `PUT /api/admin/subjects/{subject}`:

```json
{ "name": "Matematika" }
```

Impor menerima unggahan CSV/TXT melalui `multipart/form-data`; satu nama mapel berada di kolom pertama setiap baris. Duplikat dan baris tidak valid dilewati.

### Tinjau pengajuan penghapusan

`POST /api/admin/profile-deletion-requests/{deletionRequest}/review`:

```json
{ "decision": "approve" }
```

`decision` harus `approve` atau `reject`. Persetujuan melakukan soft delete profil dan akun guru.
