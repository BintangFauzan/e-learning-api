# Dokumentasi API: Sistem E-Learning

## 1. Spesifikasi Autentikasi

Semua *endpoint* yang bersifat sensitif dilindungi oleh *middleware* Laravel Sanctum dan memerlukan *Bearer Token*. Kontrol akses dilakukan pada lapisan **Controller** untuk memastikan hanya pengguna dengan peran yang tepat yang dapat beroperasi.

| Peran (Role) | Hak Akses Utama |
| :--- | :--- |
| **Dosen** | Bertanggung jawab atas CRUD Mata Kuliah, unggah Materi, Tugas, Penilaian (*Grading*), dan akses Laporan Statistik. |
| **Mahasiswa**| Bertanggung jawab atas *Enrollment*, mengunduh Materi, *Submission* Tugas, dan berpartisipasi dalam Forum Diskusi. |

### Endpoint Autentikasi

| Metode | Endpoint | Keterangan | Akses |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/register` | Pendaftaran pengguna baru. | Publik |
| `POST` | `/api/login` | Akuisisi *Bearer Token* Sanctum. | Publik |
| `POST` | `/api/logout` | Pencabutan token sesi pengguna. | Terautentikasi |

---

## 2. Daftar Endpoint API Inti

Semua rute yang dilindungi memerlukan *header* **`Authorization: Bearer <token>`** untuk diakses.

### 2.1. Manajemen Mata Kuliah & Pendaftaran (*Enrollment*)

| Metode | Endpoint | Keterangan | Peran |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/courses` | Daftar seluruh Mata Kuliah. | Dosen/Mahasiswa |
| `POST` | `/api/courses` | Membuat Mata Kuliah baru. | Dosen |
| `PUT` | `/api/courses/{course}` | Memperbarui detail Mata Kuliah. | Dosen |
| `DELETE`| `/api/courses/{course}` | Menghapus Mata Kuliah. | Dosen |
| `POST` | `/api/courses/{course}/enroll` | Pendaftaran Mahasiswa ke Mata Kuliah. | Mahasiswa |

### 2.2. Materi Pembelajaran (dengan File Handling)

| Metode | Endpoint | Keterangan | Peran |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/courses/{course}/materials`| Melihat daftar materi. | Dosen/Mahasiswa |
| `POST` | `/api/courses/{course}/materials`| Mengunggah materi baru. | Dosen |
| `GET` | `/api/materials/{material}/download`| Mengunduh file materi. | Dosen/Mahasiswa |

### 2.3. Tugas dan Penilaian (*Grading*)

| Metode | Endpoint | Keterangan | Peran |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/assignments` | Membuat penugasan (tugas) baru. | Dosen |
| `POST` | `/api/submissions` | Mengunggah jawaban tugas (`form-data`). | Mahasiswa |
| `POST` | `/api/submissions/{submission}/grade`| Memberikan nilai pada *submission*. | Dosen |

### 2.4. Forum Diskusi

| Metode | Endpoint | Keterangan | Peran |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/discussions` | Membuat diskusi baru. | Dosen/Mahasiswa |
| `POST` | `/api/discussions/{discussion}/replies`| Membalas diskusi yang ada. | Dosen/Mahasiswa |

### 2.5. Laporan dan Statistik

Semua *endpoint* di bagian ini hanya dapat diakses oleh **Dosen**.

| Metode | Endpoint | Keterangan | Peran |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/reports/courses` | Statistik jumlah mahasiswa per mata kuliah. | Dosen |
| `GET` | `/api/reports/assignments`| Statistik status tugas (*submitted* vs *graded*). | Dosen |
| `GET` | `/api/reports/students/{id}`| Detail tugas dan nilai untuk Mahasiswa spesifik. | Dosen |