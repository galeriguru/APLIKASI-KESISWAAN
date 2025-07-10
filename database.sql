-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2025 at 04:32 PM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



--

-- --------------------------------------------------------

--
-- Table structure for table `agenda_kesiswaan`
--

CREATE TABLE `agenda_kesiswaan` (
  `id` int(11) NOT NULL,
  `judul_kegiatan` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `created_by_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `agenda_kesiswaan`
--

INSERT INTO `agenda_kesiswaan` (`id`, `judul_kegiatan`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `created_by_user_id`) VALUES
(3, 'Halan halan kitahh', 'gass teruss', '2025-07-07 14:10:00', '2025-07-09 14:10:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_siswa`
--

CREATE TABLE `dokumen_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `jenis_dokumen` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `izin_siswa`
--

CREATE TABLE `izin_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tanggal_izin_mulai` date NOT NULL,
  `tanggal_izin_selesai` date DEFAULT NULL,
  `jenis_izin` enum('Sakit','Izin Pribadi','Kegiatan Sekolah','Lain-lain') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status` enum('Diajukan','Disetujui','Ditolak','Dibatalkan') DEFAULT 'Diajukan',
  `diajukan_oleh_user_id` int(11) NOT NULL,
  `disetujui_oleh_user_id` int(11) DEFAULT NULL,
  `tanggal_persetujuan` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `izin_siswa`
--

INSERT INTO `izin_siswa` (`id`, `siswa_id`, `tanggal_izin_mulai`, `tanggal_izin_selesai`, `jenis_izin`, `keterangan`, `file_bukti`, `status`, `diajukan_oleh_user_id`, `disetujui_oleh_user_id`, `tanggal_persetujuan`, `created_at`, `updated_at`) VALUES
(4, 12, '2025-07-07', '2025-07-09', 'Sakit', 'sakit', '', 'Disetujui', 24, 1, '2025-07-07 14:14:51', '2025-07-07 07:12:33', '2025-07-07 07:14:51');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_pelanggaran`
--

CREATE TABLE `jenis_pelanggaran` (
  `id` int(11) NOT NULL,
  `nama_pelanggaran` varchar(255) NOT NULL,
  `poin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jenis_pelanggaran`
--

INSERT INTO `jenis_pelanggaran` (`id`, `nama_pelanggaran`, `poin`) VALUES
(2, 'Senyum tapi tidak ada orang', 50);

-- --------------------------------------------------------

--
-- Table structure for table `jenis_prestasi`
--

CREATE TABLE `jenis_prestasi` (
  `id` int(11) NOT NULL,
  `nama_prestasi` varchar(255) NOT NULL,
  `tingkat` enum('Sekolah','Kecamatan','Kabupaten','Provinsi','Nasional','Internasional') DEFAULT NULL,
  `jenis` enum('Akademik','Non-Akademik') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jenis_prestasi`
--

INSERT INTO `jenis_prestasi` (`id`, `nama_prestasi`, `tingkat`, `jenis`) VALUES
(3, 'Sering tersenyum', 'Nasional', 'Non-Akademik');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `wali_kelas` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `wali_kelas`) VALUES
(6, 'XII IPA 1', 'Pak Guru');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggaran_siswa`
--

CREATE TABLE `pelanggaran_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `jenis_pelanggaran_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `dicatat_oleh_user_id` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pelanggaran_siswa`
--

INSERT INTO `pelanggaran_siswa` (`id`, `siswa_id`, `jenis_pelanggaran_id`, `tanggal`, `dicatat_oleh_user_id`, `keterangan`) VALUES
(6, 11, 2, '2025-07-07', 1, 'tidak boleh senyum sendiri');

-- --------------------------------------------------------

--
-- Table structure for table `prestasi_siswa`
--

CREATE TABLE `prestasi_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `jenis_prestasi_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `file_bukti` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `prestasi_siswa`
--

INSERT INTO `prestasi_siswa` (`id`, `siswa_id`, `jenis_prestasi_id`, `tanggal`, `keterangan`, `file_bukti`) VALUES
(4, 12, 3, '2025-07-07', 'juara senyum nasional', '');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ortu_user_id` int(11) DEFAULT NULL,
  `kelas_id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `public_link_hash` varchar(64) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `user_id`, `ortu_user_id`, `kelas_id`, `nis`, `nama_lengkap`, `alamat`, `no_telepon`, `public_link_hash`, `foto`) VALUES
(11, 22, 23, 6, '123', 'Asep', 'Jl. Kebahagiaan', '816', 'f919e58f5186f521e6a86c0481e202e8e9b221b227dcfbf3155d44f247de85c4', 'default.png'),
(12, 24, 25, 6, '234', 'Jaenudin', 'Jl. Kebahagiaan', '817', '5accf53186485772862370c40c20af3de3f3b53de8c383efb8eca1039409ab46', 'default.png'),
(13, 26, 27, 6, '345', 'Asep Jaenudin', 'Jl. Kebahagiaan', '818', '0451a12f50a29ec901e7dbb69c15a763483302914384124e206d3911e3dba5ad', 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `surat_panggilan_log`
--

CREATE TABLE `surat_panggilan_log` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tanggal_panggilan` date NOT NULL,
  `poin_saat_panggilan` int(11) NOT NULL,
  `dicatat_oleh_user_id` int(11) NOT NULL,
  `keterangan_tambahan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `surat_panggilan_log`
--

INSERT INTO `surat_panggilan_log` (`id`, `siswa_id`, `tanggal_panggilan`, `poin_saat_panggilan`, `dicatat_oleh_user_id`, `keterangan_tambahan`, `file_surat`, `created_at`) VALUES
(18, 11, '0000-00-00', 50, 1, NULL, 'Surat_Panggilan_123_20250707140944.pdf', '2025-07-07 07:09:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('pembina','siswa','orang_tua') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'pembina', '$2y$10$dI942nuakZhH5p65Se.KMeuSZ298gtrd1rvM0xhQAAVzE9tWNb5ni', 'Asep Jaenudin', 'pembina', '2025-07-05 00:18:26'),
(22, '123', '$2y$10$pDCxi/8ORZazorGL0ZMWTe8p1/Hn1c2gOOVhiZEMYhesiOAfCA52O', 'Asep', 'siswa', '2025-07-07 07:05:00'),
(23, 'ortu_123', '$2y$10$GIPYYi1/MB3qhQqGehc7D.IOuejx3jun5DRjqMeRwB9smsuFJNPT2', 'Ayah', 'orang_tua', '2025-07-07 07:05:00'),
(24, '234', '$2y$10$Q6GhunRogAfnEMezzN03cuytc5dys8WUmM2TAgLO3696bvegXaBPO', 'Jaenudin', 'siswa', '2025-07-07 07:05:00'),
(25, 'ortu_234', '$2y$10$mlBW61wGAhRZZmOJcUerJeYlGRt25GXXhdr4eKEdFN8h6Du7IaQli', 'Ibu', 'orang_tua', '2025-07-07 07:05:00'),
(26, '345', '$2y$10$4JCgZvMA9jkUpnVuDmH2JeTF5Ld2aO4CrBsvzAURqlCSI7dzrUm4q', 'Asep Jaenudin', 'siswa', '2025-07-07 07:05:00'),
(27, 'ortu_345', '$2y$10$JLlB3MOcQ0YIf3yKsqu0ee36yrhEG0rwmiC5EMmu3eHGds19jbbSG', 'Anak', 'orang_tua', '2025-07-07 07:05:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agenda_kesiswaan`
--
ALTER TABLE `agenda_kesiswaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `dokumen_siswa`
--
ALTER TABLE `dokumen_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `uploaded_by_user_id` (`uploaded_by_user_id`);

--
-- Indexes for table `izin_siswa`
--
ALTER TABLE `izin_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `diajukan_oleh_user_id` (`diajukan_oleh_user_id`),
  ADD KEY `disetujui_oleh_user_id` (`disetujui_oleh_user_id`);

--
-- Indexes for table `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jenis_prestasi`
--
ALTER TABLE `jenis_prestasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelanggaran_siswa`
--
ALTER TABLE `pelanggaran_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `jenis_pelanggaran_id` (`jenis_pelanggaran_id`),
  ADD KEY `dicatat_oleh_user_id` (`dicatat_oleh_user_id`);

--
-- Indexes for table `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `jenis_prestasi_id` (`jenis_prestasi_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `ortu_user_id` (`ortu_user_id`),
  ADD UNIQUE KEY `public_link_hash` (`public_link_hash`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `surat_panggilan_log`
--
ALTER TABLE `surat_panggilan_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `dicatat_oleh_user_id` (`dicatat_oleh_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agenda_kesiswaan`
--
ALTER TABLE `agenda_kesiswaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dokumen_siswa`
--
ALTER TABLE `dokumen_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `izin_siswa`
--
ALTER TABLE `izin_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jenis_prestasi`
--
ALTER TABLE `jenis_prestasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pelanggaran_siswa`
--
ALTER TABLE `pelanggaran_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `surat_panggilan_log`
--
ALTER TABLE `surat_panggilan_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agenda_kesiswaan`
--
ALTER TABLE `agenda_kesiswaan`
  ADD CONSTRAINT `agenda_kesiswaan_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `dokumen_siswa`
--
ALTER TABLE `dokumen_siswa`
  ADD CONSTRAINT `dokumen_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_siswa_ibfk_2` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `izin_siswa`
--
ALTER TABLE `izin_siswa`
  ADD CONSTRAINT `izin_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `izin_siswa_ibfk_2` FOREIGN KEY (`diajukan_oleh_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `izin_siswa_ibfk_3` FOREIGN KEY (`disetujui_oleh_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pelanggaran_siswa`
--
ALTER TABLE `pelanggaran_siswa`
  ADD CONSTRAINT `pelanggaran_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`),
  ADD CONSTRAINT `pelanggaran_siswa_ibfk_2` FOREIGN KEY (`jenis_pelanggaran_id`) REFERENCES `jenis_pelanggaran` (`id`),
  ADD CONSTRAINT `pelanggaran_siswa_ibfk_3` FOREIGN KEY (`dicatat_oleh_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prestasi_siswa`
--
ALTER TABLE `prestasi_siswa`
  ADD CONSTRAINT `prestasi_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`),
  ADD CONSTRAINT `prestasi_siswa_ibfk_2` FOREIGN KEY (`jenis_prestasi_id`) REFERENCES `jenis_prestasi` (`id`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`ortu_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `siswa_ibfk_3` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);

--
-- Constraints for table `surat_panggilan_log`
--
ALTER TABLE `surat_panggilan_log`
  ADD CONSTRAINT `surat_panggilan_log_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surat_panggilan_log_ibfk_2` FOREIGN KEY (`dicatat_oleh_user_id`) REFERENCES `users` (`id`);
COMMIT;
