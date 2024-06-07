-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2024 at 07:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_managment`
--

-- --------------------------------------------------------

--
-- Table structure for table `resetcredentials`
--

CREATE TABLE `resetcredentials` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token_storage`
--

CREATE TABLE `token_storage` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `refresh_token` text NOT NULL,
  `expires_at` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `token_storage`
--

INSERT INTO `token_storage` (`id`, `username`, `refresh_token`, `expires_at`) VALUES
(20, 'Dula2', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MTc2NDM0NDgsImV4cCI6MTcxNzc3MzA0OCwidXNlcm5hbWUiOiJEdWxhMiJ9.ym5Algq9ouKN3Zo5FWuh0sYSdDmxQOyK7Fo0BlKoAsE', 1717773048),
(29, 'Dula1', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MTc2NDYyNzYsImV4cCI6MTcxNzY0NjMzNiwidXNlcm5hbWUiOiJEdWxhMSJ9.fV5aNrrfk-xtAy2d9d-dQOEjFHpBcLHA6C3FITrEyXM', 1717646336),
(35, 'Dula3', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MTc2NTg1OTAsImV4cCI6MTcxNzc4ODE5MCwidXNlcm5hbWUiOiJEdWxhMyJ9.sp_9DFGpGfpvMlKqj--r1BooiHV7KtgcO44MwM_HErg', 1717788190),
(37, 'Dula', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MTc2NTkyODksImV4cCI6MTcxNzc4ODg4OSwidXNlcm5hbWUiOiJEdWxhIn0.XNVatr_Ynv7ETpQQUP7f3Mr3odB5KEYOPLnb9YcDMgE', 1717788889);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `userrole` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `token`, `active`, `userrole`) VALUES
(11, 'Dula', 'dulanjali.koswatte@gmail.com', '$2y$10$7jExgQ301wH8/V980DDwGOjsMMRqUgdKRtGpWuM0wEqiOH9jnyuM2', '7a9c6745ec24441a4ee24b7c872d5234', 0, 'admin'),
(21, 'Dula2', 'dula91@gmail.com', '$2y$10$tkxFHcQd3dCb58auuUlb1.pS4r0sNvMLLSWtRbYcdvlM0X5brNHFK', 'cb36f6e0a982879ae2cc669494a0c5cc', 1, 'user'),
(22, 'Dula3', 'piratedilshan@gmail.com', '$2y$10$5Gd3vW.mjzh7XgyZcuUi.exwb57IOBO.sn9OOsY4U4cgVQ6wWHRBS', '699ea6fa9efe70aca2716d8d054ad884', 0, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `resetcredentials`
--
ALTER TABLE `resetcredentials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_storage`
--
ALTER TABLE `token_storage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

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
-- AUTO_INCREMENT for table `resetcredentials`
--
ALTER TABLE `resetcredentials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `token_storage`
--
ALTER TABLE `token_storage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
