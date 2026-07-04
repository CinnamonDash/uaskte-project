-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 03, 2026 at 04:46 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uaskte`
--

-- --------------------------------------------------------

--
-- Table structure for table `otp_log`
--

CREATE TABLE `otp_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expired_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `otp_log`
--

INSERT INTO `otp_log` (`id`, `user_id`, `otp_code`, `expired_at`, `is_used`, `created_at`) VALUES
(6, 1, '763067', '2026-06-29 23:21:40', 1, '2026-06-29 23:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_hash` varchar(128) NOT NULL COMMENT 'SHA-512 dari email',
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'bcrypt password',
  `no_wa` varchar(20) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT '1',
  `google_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `email_hash`, `password_hash`, `no_wa`, `role`, `is_active`, `google_id`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'jes', 'jjessiee.c@gmail.com', '2b7c6658c3359e64f0e61ae948e849847901a67e52a60e0a0b73ef19ab1b3a752f4c37b623ae08f75ee77c9cf053a6f412b53d33e09a2b75b4c1be9961b74127', '$2y$12$PHuBA3erE5NnCwTrpPIPOulpbukcMBGduF0sxOrHNVvv/Hqbp/5Ne', '6285711455288', 'user', 1, NULL, '2026-06-29 13:10:06', '2026-06-29 13:10:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_audit`
--

CREATE TABLE `user_audit` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `changed_by` int DEFAULT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_audit`
--

INSERT INTO `user_audit` (`id`, `user_id`, `action`, `field_changed`, `old_value`, `new_value`, `changed_by`, `changed_at`, `ip_address`, `user_agent`) VALUES
(1, 1, 'CREATE', 'self_register', NULL, 'jjessiee.c@gmail.com', NULL, '2026-06-29 13:10:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(64) NOT NULL,
  `user_id` int NOT NULL,
  `google_token` text,
  `otp_verified` tinyint(1) DEFAULT '0',
  `biometric_verified` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webauthn_credentials`
--

CREATE TABLE `webauthn_credentials` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `credential_id` text NOT NULL,
  `public_key` text NOT NULL,
  `counter` int DEFAULT '0',
  `device_type` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `otp_log`
--
ALTER TABLE `otp_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email_hash` (`email_hash`);

--
-- Indexes for table `user_audit`
--
ALTER TABLE `user_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `webauthn_credentials`
--
ALTER TABLE `webauthn_credentials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `otp_log`
--
ALTER TABLE `otp_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_audit`
--
ALTER TABLE `user_audit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `webauthn_credentials`
--
ALTER TABLE `webauthn_credentials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `otp_log`
--
ALTER TABLE `otp_log`
  ADD CONSTRAINT `otp_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `webauthn_credentials`
--
ALTER TABLE `webauthn_credentials`
  ADD CONSTRAINT `webauthn_credentials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
