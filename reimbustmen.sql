-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 17 Des 2024 pada 05.12
-- Versi server: 10.4.25-MariaDB
-- Versi PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reimbustmen`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `reimbursement`
--

CREATE TABLE `reimbursement` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `reimbursement`
--

INSERT INTO `reimbursement` (`id`, `user_email`, `file`, `amount`, `description`, `status`, `created_at`, `user_id`, `tanggal`, `nama`, `deskripsi`, `jumlah`) VALUES
(1, '', 'reimbursement_67603968a45966.15514585.jpg', NULL, NULL, 'Menunggu', '2024-12-16 14:30:00', 1, '2024-12-17', 'Maria', 'bayar utang 2k', 20000),
(2, '', 'reimbursement_6760399878afe2.97956075.jpg', NULL, NULL, 'Menunggu', '2024-12-16 14:30:48', 1, '2024-12-16', 'intan ', 'dana', 50000),
(3, '', 'reimbursement_67605bd1b69757.53339843.jpg', NULL, NULL, 'Menunggu', '2024-12-16 16:56:49', 1, '2024-12-16', 'Maria', 'la', 20000),
(4, '', 'reimbursement_6760603e5de532.56850789.jpg', NULL, NULL, 'Menunggu', '2024-12-16 17:15:42', 1, '2024-12-17', 'Maria', 'Transport', 20000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `reimbursement_entry`
--

CREATE TABLE `reimbursement_entry` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `description` varchar(225) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `reimbursement_history`
--

CREATE TABLE `reimbursement_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `role` varchar(10) NOT NULL,
  `created at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `role`, `created at`, `username`) VALUES
(1, 'mariacahyaj@gmail.com', '$2y$10$L3OkDQYOqge8HdMpAizsDeOJhWxUsJnSHML4w1DlYAhP81wb9N2fC', 'user', '2024-11-14 10:09:31', 'Maria'),
(2, 'admin@gmail.com', '$2y$10$EJcv0yBGvL50YYckqACKG.TMAln6BKdYLqWuGypHn4baB33L3q9U6', 'admin', '2024-12-16 16:46:39', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `reimbursement`
--
ALTER TABLE `reimbursement`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `reimbursement_entry`
--
ALTER TABLE `reimbursement_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `reimbursement_history`
--
ALTER TABLE `reimbursement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `entry_id` (`entry_id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `reimbursement`
--
ALTER TABLE `reimbursement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `reimbursement_entry`
--
ALTER TABLE `reimbursement_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `reimbursement_history`
--
ALTER TABLE `reimbursement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `reimbursement_entry`
--
ALTER TABLE `reimbursement_entry`
  ADD CONSTRAINT `reimbursement_entry_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reimbursement_history`
--
ALTER TABLE `reimbursement_history`
  ADD CONSTRAINT `reimbursement_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `reimbursement_history_ibfk_2` FOREIGN KEY (`entry_id`) REFERENCES `reimbursement_entry` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
