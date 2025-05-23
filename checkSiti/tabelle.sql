-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Creato il: Mag 23, 2025 alle 22:19
-- Versione del server: 9.1.0
-- Versione PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `checksiti`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `disservizi_mensili`
--

DROP TABLE IF EXISTS `disservizi_mensili`;
CREATE TABLE IF NOT EXISTS `disservizi_mensili` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idSito` int NOT NULL,
  `anno` int NOT NULL,
  `mese` int NOT NULL,
  `contatore` int DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idSito` (`idSito`,`anno`,`mese`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `sitimonitorati`
--

DROP TABLE IF EXISTS `sitimonitorati`;
CREATE TABLE IF NOT EXISTS `sitimonitorati` (
  `id` int NOT NULL AUTO_INCREMENT,
  `url` varchar(191) NOT NULL,
  `contatoreDisserviziConsecutivi` int DEFAULT '0',
  `ultimoControllo` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
