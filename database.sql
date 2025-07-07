USE db_kesiswaan; -- Pastikan Anda berada di database yang benar

-- Tabel untuk izin siswa
CREATE TABLE izin_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal_izin_mulai DATE NOT NULL,
    tanggal_izin_selesai DATE NULL, -- Nullable for single-day permits
    jenis_izin ENUM('Sakit', 'Izin Pribadi', 'Kegiatan Sekolah', 'Lain-lain') NOT NULL,
    keterangan TEXT NULL,
    file_bukti VARCHAR(255) NULL, -- e.g., surat dokter, surat pemberitahuan
    status ENUM('Diajukan', 'Disetujui', 'Ditolak', 'Dibatalkan') DEFAULT 'Diajukan',
    diajukan_oleh_user_id INT NOT NULL, -- User yang mengajukan (siswa atau ortu)
    disetujui_oleh_user_id INT NULL, -- Pembina yang menyetujui/menolak
    tanggal_persetujuan DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (diajukan_oleh_user_id) REFERENCES users(id),
    FOREIGN KEY (disetujui_oleh_user_id) REFERENCES users(id)
);

-- Tabel untuk dokumen/surat pengajuan siswa (generik)
CREATE TABLE dokumen_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    judul_dokumen VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    jenis_dokumen VARCHAR(100) NULL, -- e.g., "Surat Pernyataan", "Formulir Beasiswa", "Surat Pengajuan"
    file_path VARCHAR(255) NOT NULL,
    uploaded_by_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
);