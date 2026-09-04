# Flowchart Revisi — Absensi Magang UP3 Serpong

Flowchart awal sudah mencakup dua role (Intern dan Mentor), login/register, presensi datang/pulang/izin, dan analisis spider chart. fileciteturn0file0L3-L18 fileciteturn0file0L87-L112

Berikut versi yang disesuaikan dengan requirement lengkap.

```mermaid
flowchart TD

A[Halaman Pilih Peran] --> B{Pilih role}
B -->|Intern| C[Login Intern]
B -->|Mentor| M[Login Mentor]

C --> D{Punya akun?}
D -->|Ya| E[Input username + password]
D -->|Tidak| F[Register Data Intern]
F --> G[Register Akun]
G --> E
E --> H{Valid?}
H -->|Tidak| C
H -->|Ya| I[Dashboard Intern]

M --> N{Punya akun?}
N -->|Ya| O[Input username + password]
N -->|Tidak| P[Register Data Mentor]
P --> Q[Register Akun Mentor]
Q --> O
O --> R{Valid?}
R -->|Tidak| M
R -->|Ya| S[Dashboard Mentor]

C -.-> OTP1[Lupa password → OTP → reset password]
M -.-> OTP2[Lupa password → OTP → reset password]

I --> T{Pilih menu}
T -->|Kedatangan| U{Tidak ada izin hari ini?}
U -->|Tidak| U1[Disabled: lah, katanya izin]
U -->|Ya| V{Belum presensi datang?}
V -->|Tidak| V1[Disabled: lah, kan tadi udah]
V -->|Ya| W{Dalam radius 100 m?}
W -->|Tidak| W1[Disabled: nyampe dulu kali]
W -->|Ya| X[Form timestamp + kamera]
X --> Y{Lewat jam masuk?}
Y -->|Ya| Z[Input alasan keterlambatan]
Y -->|Tidak| AA[Kirim presensi]
Z --> AA
AA --> I

T -->|Kepulangan| AB{Sudah jam pulang?}
AB -->|Belum| AB1[Disabled: mau kemana hayo]
AB -->|Sudah| AC{Sudah presensi pagi?}
AC -->|Tidak| AD[Yellow aktif: loh, lupa absen pagi kah?]
AC -->|Ya| AE[Form timestamp + kamera]
AD --> AE
AE --> AF[Kirim presensi pulang]
AF --> I

T -->|Perizinan| AG[Input tanggal mulai-selesai + alasan]
AG --> AH[Simpan pengajuan per tanggal]
AH --> AI[Menunggu approval mentor]
AI --> I

I --> AJ[Avatar Sidebar]
AJ --> AK[Data diri]
AJ --> AL[History]
AJ --> AM[Analisis Spider]

AK --> AK1[Edit setelah konfirmasi password]
AL --> AL1[Riwayat datang/pulang/izin]
AM --> AM1[Rajin]
AM --> AM2[Tepat waktu]
AM --> AM3[Lembur]
AM --> AM4[Konsistensi]

S --> AN{Pilih menu mentor}
AN -->|Daftar intern| AO[Intern dengan mentor/divisi sama]
AO --> AO1[Filter aktif + universitas]
AO --> AO2[Detail / delete intern]

AN -->|Analisis intern| AP[Card nama intern]
AP --> AP1[Spider chart per intern]

AN -->|Presensi intern| AQ[Tabel presensi]
AQ --> AQ1[Hari + tanggal + jam masuk + jam pulang + keterangan]

AN -->|Perizinan| AR[Daftar pengajuan izin]
AR --> AR1{Approval}
AR1 -->|Approve| AR2[Hijau: approved]
AR1 -->|Decline| AR3[Kuning: declined]
AR --> AR4[Pending merah + counter]

S --> AS[Avatar Sidebar Mentor]
AS --> AT[Data diri]
AS --> AU[History]
AS --> AV[Analisis]
```

## Perubahan penting dari flowchart awal

1. **OTP reset password** ditambahkan.
2. **Geofence 100 meter** menjadi validasi server, bukan hanya kondisi UI.
3. **Kamera** menjadi bagian wajib untuk bukti presensi.
4. **Alasan keterlambatan** hanya muncul ketika melewati jam masuk.
5. **Perizinan rentang tanggal** dipecah menjadi record harian di database.
6. **Approval mentor** mengubah status menjadi approved/declined.
7. **Sidebar profile** memiliki Data diri, History, Analisis.
8. **Analisis** memiliki empat kriteria: Rajin, Tepat waktu, Lembur, Konsistensi.
9. **Mentor** mendapatkan daftar intern berdasarkan relasi mentor/divisi.
10. **Counter pending approval** ditambahkan pada dashboard mentor.
11. **Kepulangan sebelum jam pulang** dibuat disabled.
12. Jika sudah jam pulang tetapi belum absen pagi, kepulangan tetap dapat dibuka dengan pesan pengingat.
