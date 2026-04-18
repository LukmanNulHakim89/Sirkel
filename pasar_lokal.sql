-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 08:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pasar_lokal`
--

-- --------------------------------------------------------

--
-- Table structure for table `pesan`
--

CREATE TABLE `pesan` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `pembeli_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `status` enum('baru','dibaca','dibalas') DEFAULT 'baru',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `satuan` varchar(30) DEFAULT 'pcs',
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `toko_id`, `nama_produk`, `deskripsi`, `harga`, `stok`, `satuan`, `gambar`, `status`, `created_at`) VALUES
(1, 1, 'Nasi Uduk Komplit', 'Nasi uduk dengan lauk ayam goreng, tempe, sambal', 15000.00, 50, 'porsi', NULL, 'aktif', '2026-04-18 04:52:57'),
(2, 1, 'Ayam Goreng Kremes', 'Ayam goreng renyah dengan kremesan gurih', 20000.00, 30, 'potong', NULL, 'aktif', '2026-04-18 04:52:57'),
(3, 1, 'Es Teh Manis', 'Es teh manis segar', 5000.00, 100, 'gelas', NULL, 'aktif', '2026-04-18 04:52:57'),
(4, 2, 'Kemeja Batik Kawung', 'Batik motif kawung ukuran M-XXL', 185000.00, 15, 'pcs', NULL, 'aktif', '2026-04-18 04:52:57'),
(5, 2, 'Kain Batik Parang', 'Kain batik tulis motif parang 2 meter', 350000.00, 8, 'lembar', NULL, 'aktif', '2026-04-18 04:52:57');

-- --------------------------------------------------------

--
-- Table structure for table `toko`
--

CREATE TABLE `toko` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_toko` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `alamat` text NOT NULL,
  `kota` varchar(100) NOT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `kategori` enum('makanan','fashion','elektronik','pertanian','kerajinan','jasa','lainnya') DEFAULT 'lainnya',
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('pending','aktif','ditolak','nonaktif') DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toko`
--

INSERT INTO `toko` (`id`, `user_id`, `nama_toko`, `deskripsi`, `alamat`, `kota`, `kecamatan`, `latitude`, `longitude`, `kategori`, `foto`, `status`, `catatan_admin`, `created_at`) VALUES
(1, 2, 'Warung Bu Sari', 'Masakan rumahan, nasi uduk, lauk pauk segar tiap hari', 'Jl. Margonda Raya No. 45', 'Depok', 'Beji', -6.32910890, 107.30316550, 'makanan', NULL, 'aktif', NULL, '2026-04-18 04:52:57'),
(2, 3, 'Batik Pak Budi', 'Batik tulis dan cap motif Jawa, harga langsung pengrajin', 'Jl. Nusantara No. 12', 'Depok', 'Pancoran Mas', -6.40250000, 106.81950000, 'fashion', NULL, 'aktif', NULL, '2026-04-18 04:52:57'),
(3, 4, 'Warung Mang Ojo', 'Gorengan, kopi, es teh, jajan pasar lengkap', 'Jl. Raya Bogor Km 31', 'Depok', 'Sukmajaya', -6.38950000, 106.85200000, 'makanan', NULL, 'nonaktif', NULL, '2026-04-18 04:52:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `role` enum('admin','penjual','pembeli') DEFAULT 'pembeli',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `telepon`, `role`, `created_at`) VALUES
(1, 'Admin PasarLokal', 'admin@pasarlokal.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08001234567', 'admin', '2026-04-18 04:52:57'),
(2, 'Ibu Sari', 'sari@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'penjual', '2026-04-18 04:52:57'),
(3, 'Pak Budi', 'budi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '082345678901', 'penjual', '2026-04-18 04:52:57'),
(4, 'Warung Mang Ojo', 'ojo@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '083456789012', 'penjual', '2026-04-18 04:52:57'),
(5, 'Dedi Pembeli', 'dedi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '084567890123', 'pembeli', '2026-04-18 04:52:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pesan`
--
ALTER TABLE `pesan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `pembeli_id` (`pembeli_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indexes for table `toko`
--
ALTER TABLE `toko`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pesan`
--
ALTER TABLE `pesan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `toko`
--
ALTER TABLE `toko`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pesan`
--
ALTER TABLE `pesan`
  ADD CONSTRAINT `pesan_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `pesan_ibfk_2` FOREIGN KEY (`pembeli_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`toko_id`) REFERENCES `toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `toko`
--
ALTER TABLE `toko`
  ADD CONSTRAINT `toko_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
