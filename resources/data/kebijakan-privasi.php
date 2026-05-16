<?php

/**
 * Sumber tunggal konten Kebijakan Privasi Aplikasi Sengkuyung.
 * Digunakan oleh halaman web (/kebijakan-privasi) dan API mobile (/api/kebijakan-privasi).
 */
return [
    'title' => 'Kebijakan Privasi Aplikasi Sengkuyung',
    'app_name' => 'Sengkuyung',
    'operator' => 'Badan Pendapatan Daerah Provinsi Jawa Tengah (BAPENDA Jateng)',
    'effective_date' => '2026-05-16',
    'last_updated' => '2026-05-16',
    'language' => 'id',
    'sections' => [
        [
            'id' => 'pendahuluan',
            'title' => '1. Pendahuluan',
            'paragraphs' => [
                'Kebijakan Privasi ini menjelaskan bagaimana Aplikasi Sengkuyung ("Aplikasi"), yang dikembangkan dan dioperasikan oleh Badan Pendapatan Daerah Provinsi Jawa Tengah ("BAPENDA Jateng"), mengumpulkan, menggunakan, menyimpan, melindungi, dan membagikan data pribadi pengguna.',
                'Aplikasi Sengkuyung tersedia melalui layanan web administrasi dan aplikasi mobile petugas lapangan yang terhubung ke API resmi. Dengan mengakses atau menggunakan Aplikasi, Anda menyatakan telah membaca, memahami, dan menyetujui ketentuan dalam Kebijakan Privasi ini.',
                'Jika Anda tidak setuju dengan kebijakan ini, mohon hentikan penggunaan Aplikasi.',
            ],
        ],
        [
            'id' => 'ruang-lingkup',
            'title' => '2. Ruang Lingkup',
            'paragraphs' => [
                'Kebijakan ini berlaku untuk seluruh pengguna Aplikasi Sengkuyung, termasuk petugas lapangan, verifikator, dan pengguna administrasi web yang memiliki akun resmi yang dikelola oleh BAPENDA Jateng.',
                'Kebijakan ini mencakup pemrosesan data yang dilakukan melalui fitur pendataan kendaraan bermotor, verifikasi, unggah dokumen, data tertagih, rekap pelaporan, otentikasi (login dan OTP), serta aktivitas lain yang disediakan dalam Aplikasi.',
            ],
        ],
        [
            'id' => 'data-yang-dikumpulkan',
            'title' => '3. Data yang Dikumpulkan',
            'paragraphs' => [
                'BAPENDA Jateng dapat mengumpulkan jenis data berikut sesuai kebutuhan layanan:',
            ],
            'list' => [
                'Data akun pengguna: nama, alamat email, nomor telepon (jika tersedia), peran (role), wilayah kerja (lokasi/kecamatan/kelurahan SAMSAT), serta kredensial autentikasi yang dienkripsi.',
                'Data pendataan kendaraan: nomor polisi, identitas dan data kendaraan, nama pemilik, alamat, jenis kendaraan/roda, lokasi layanan SAMSAT, status verifikasi, foto atau berkas pendukung, koordinat/lokasi pengambilan data (jika diaktifkan perangkat), serta metadata waktu pencatatan.',
                'Data tertagih: daftar kendaraan yang belum terdata sesuai wilayah dan tahun berjalan, termasuk informasi wilayah dan alamat yang diimpor secara resmi.',
                'Data teknis: alamat IP, jenis perangkat, sistem operasi, versi aplikasi, log aktivitas (activity log), token sesi/API, dan informasi diagnostik untuk keamanan sistem.',
                'Data komunikasi OTP: kode verifikasi sementara yang dikirim melalui email atau kanal resmi lain yang ditetapkan instansi.',
            ],
        ],
        [
            'id' => 'tujuan-penggunaan',
            'title' => '4. Tujuan Penggunaan Data',
            'paragraphs' => [
                'Data pribadi dan data operasional diproses untuk tujuan berikut:',
            ],
            'list' => [
                'Menyediakan layanan pendataan, verifikasi, dan pelaporan pajak kendaraan bermotor sesuai kewenangan BAPENDA Jateng.',
                'Mengelola akun pengguna, autentikasi, otorisasi akses, dan keamanan sesi.',
                'Memvalidasi keabsahan data, mencegah pendataan ganda oleh pengguna lain, serta menjaga integritas data wilayah SAMSAT.',
                'Menyediakan rekap, unduhan laporan, dan kebutuhan administrasi internal instansi.',
                'Meningkatkan kualitas layanan, pemeliharaan sistem, audit keamanan, dan penanganan insiden teknis.',
                'Memenuhi kewajiban hukum, peraturan perundang-undangan, dan permintaan resmi yang sah dari otoritas berwenang.',
            ],
        ],
        [
            'id' => 'dasar-hukum',
            'title' => '5. Dasar Pemrosesan',
            'paragraphs' => [
                'Pemrosesan data dilakukan berdasarkan pelaksanaan tugas dan fungsi BAPENDA Jateng dalam pelayanan perpajakan daerah, persetujuan pengguna sejauh diperlukan, serta ketentuan peraturan perundang-undangan yang berlaku di Indonesia, termasuk Undang-Undang Perlindungan Data Pribadi.',
            ],
        ],
        [
            'id' => 'penyimpanan-dan-keamanan',
            'title' => '6. Penyimpanan dan Keamanan',
            'paragraphs' => [
                'Data disimpan pada infrastruktur server yang dikelola atau ditunjuk oleh BAPENDA Jateng dengan kontrol akses berbasis peran, enkripsi pada saluran komunikasi (HTTPS/TLS), serta pembatasan akses administratif.',
                'Berkas sensitif dapat disimpan dengan mekanisme perlindungan tambahan (misalnya enkripsi berkas) dan hanya dapat diakses oleh pengguna yang berwenang melalui mekanisme autentikasi resmi.',
                'Meskipun kami menerapkan langkah keamanan yang wajar, tidak ada sistem yang sepenuhnya bebas risiko. Pengguna wajib menjaga kerahasiaan kredensial akun dan segera melaporkan indikasi penyalahgunaan.',
            ],
        ],
        [
            'id' => 'retensi',
            'title' => '7. Masa Penyimpanan Data',
            'paragraphs' => [
                'Data disimpan selama diperlukan untuk memenuhi tujuan layanan, kewajiban hukum, audit, dan arsip instansi sesuai kebijakan retensi data BAPENDA Jateng.',
                'Data dapat dihapus atau dianonimkan apabila tidak lagi diperlukan, kecuali diwajibkan lain oleh peraturan perundang-undangan.',
            ],
        ],
        [
            'id' => 'pembagian-data',
            'title' => '8. Pembagian Data kepada Pihak Ketiga',
            'paragraphs' => [
                'BAPENDA Jateng tidak menjual data pribadi pengguna. Data dapat dibagikan secara terbatas kepada:',
            ],
            'list' => [
                'Unit kerja internal BAPENDA Jateng dan instansi pemerintah terkait sesuai kewenangan.',
                'Penyedia infrastruktur/teknologi yang terikat perjanjian kerahasiaan dan hanya memproses data atas instruksi BAPENDA Jateng.',
                'Pihak berwenang apabila diwajibkan oleh hukum atau proses hukum yang sah.',
            ],
        ],
        [
            'id' => 'hak-pengguna',
            'title' => '9. Hak Pengguna',
            'paragraphs' => [
                'Sesuai ketentuan yang berlaku, pengguna berhak untuk:',
            ],
            'list' => [
                'Memperoleh informasi mengenai pemrosesan data pribadi yang berkaitan dengan akun atau aktivitas mereka.',
                'Meminta koreksi data yang tidak akurat melalui jalur resmi administrasi BAPENDA Jateng.',
                'Mengajukan keberatan atau pembatasan pemrosesan sejauh diatur peraturan perundang-undangan.',
                'Menarik persetujuan untuk fitur tertentu yang bersifat opsional, tanpa mempengaruhi pemrosesan yang diwajibkan oleh hukum.',
            ],
            'paragraphs_after' => [
                'Permintaan terkait hak privasi dapat diajukan melalui kontak resmi BAPENDA Jateng dengan menyertakan identitas dan keterangan permintaan yang jelas.',
            ],
        ],
        [
            'id' => 'izin-perangkat',
            'title' => '10. Izin Perangkat (Aplikasi Mobile)',
            'paragraphs' => [
                'Aplikasi mobile dapat meminta izin akses kamera, penyimpanan, dan lokasi hanya untuk keperluan pendataan, unggah dokumen, atau validasi wilayah kerja. Pengguna dapat mengatur izin melalui pengaturan perangkat; penolakan izin tertentu dapat membatasi fungsi Aplikasi.',
            ],
        ],
        [
            'id' => 'cookie-dan-log',
            'title' => '11. Cookie, Sesi, dan Log Aktivitas',
            'paragraphs' => [
                'Layanan web dapat menggunakan cookie atau mekanisme sesi untuk autentikasi dan keamanan. API mobile menggunakan token autentikasi (Bearer token) yang harus dijaga kerahasiaannya.',
                'Aktivitas pengguna tertentu dicatat dalam log sistem untuk keperluan audit, pemantauan keamanan, dan peningkatan layanan.',
            ],
        ],
        [
            'id' => 'perubahan-kebijakan',
            'title' => '12. Perubahan Kebijakan',
            'paragraphs' => [
                'BAPENDA Jateng dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Versi terbaru akan dipublikasikan melalui URL resmi Aplikasi dengan mencantumkan tanggal pembaruan.',
                'Penggunaan berkelanjutan setelah perubahan berlaku dianggap sebagai penerimaan atas kebijakan yang diperbarui, kecuali diwajibkan persetujuan ulang oleh peraturan yang berlaku.',
            ],
        ],
        [
            'id' => 'kontak',
            'title' => '13. Kontak',
            'paragraphs' => [
                'Untuk pertanyaan, permintaan akses data, atau laporan terkait privasi, silakan hubungi:',
            ],
            'contact' => [
                'institution' => 'Badan Pendapatan Daerah Provinsi Jawa Tengah',
                'application' => 'Aplikasi Sengkuyung',
                'website' => 'https://bapenda.jatengprov.go.id',
                'email' => 'bapenda@jatengprov.go.id',
            ],
        ],
    ],
];
