-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 26. Jul 2024 um 10:00
-- Server-Version: 10.4.28-MariaDB
-- PHP-Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `chelo_prime`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `discord_id` VARCHAR(255) NOT NULL,
  `warframe_ign` VARCHAR(255) DEFAULT NULL,
  `about_me` TEXT DEFAULT NULL,
  `nickname` VARCHAR(255) DEFAULT NULL,
  `age` INT DEFAULT NULL,
  `origin` VARCHAR(255) DEFAULT NULL,
  `main_frame` VARCHAR(255) DEFAULT NULL,
  `weapons` TEXT DEFAULT NULL,
  `steam_profile` VARCHAR(255) DEFAULT NULL,
  `nintendo_friend_code` VARCHAR(255) DEFAULT NULL,
  `isAdmin` BOOLEAN DEFAULT FALSE,
  `custom_title_id` INT DEFAULT NULL,
  `discord_username` VARCHAR(255) NOT NULL,
  `discord_avatar_url` VARCHAR(255) DEFAULT NULL,
  -- `custom_avatar_path` VARCHAR(255) DEFAULT NULL, -- Entfernt
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `users`
--
-- Das INSERT Statement muss angepasst werden, da die Spalte custom_avatar_path entfernt wurde.
-- Da es aber ohnehin NULL war, kann die Spalte einfach aus der Liste entfernt werden.
INSERT INTO `users` (`discord_id`, `warframe_ign`, `about_me`, `nickname`, `age`, `origin`, `main_frame`, `weapons`, `steam_profile`, `nintendo_friend_code`, `isAdmin`, `custom_title_id`, `discord_username`, `discord_avatar_url`, `created_at`, `updated_at`) VALUES
('1020559274012852294', 'InitialAdminIGN', 'Super Admin Account', 'CheloAdmin', NULL, NULL, NULL, NULL, NULL, NULL, TRUE, 1, 'InitialAdmin', NULL, NOW(), NOW());

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `motds` (Message of the Day, jetzt mit Verlauf)
--

CREATE TABLE `motds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `created_by_discord_id` VARCHAR(255) NOT NULL,
  `is_published` BOOLEAN DEFAULT TRUE,
  `version` VARCHAR(50) DEFAULT NULL, -- Optional, um MOTD mit einer Plattformversion zu verknüpfen
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `motds`
--
INSERT INTO `motds` (`title`, `content`, `created_by_discord_id`, `is_published`, `version`, `created_at`, `updated_at`) VALUES
('Willkommen!', 'Willkommen bei der Endo Reserve Bank! Die MOTD kann von Admins bearbeitet werden.', '1020559274012852294', TRUE, '1.0', NOW(), NOW());

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `profile_comments`
--

CREATE TABLE `profile_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `profile_discord_id` VARCHAR(255) NOT NULL,
  `author_discord_id` VARCHAR(255) NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`profile_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `custom_titles`
--

CREATE TABLE `custom_titles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_name` VARCHAR(255) UNIQUE NOT NULL,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `custom_titles`
--

INSERT INTO `custom_titles` (`id`, `title_name`, `description`) VALUES
(1, 'Tenno', 'Standard-Titel für alle Mitglieder.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_syndicates`
--

CREATE TABLE `user_syndicates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_discord_id` VARCHAR(255) NOT NULL,
  `syndicate_name` VARCHAR(255) NOT NULL,
  `rank` VARCHAR(255) DEFAULT NULL,
  `color_hex` VARCHAR(7) DEFAULT '#FFFFFF', -- Standardfarbe Weiß
  FOREIGN KEY (`user_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE CASCADE,
  UNIQUE KEY `user_syndicate_unique` (`user_discord_id`, `syndicate_name`) -- Ein User kann pro Syndikat nur einen Eintrag haben
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Foreign key constraints
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_custom_title` FOREIGN KEY (`custom_title_id`) REFERENCES `custom_titles`(`id`) ON DELETE SET NULL;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `changelog`
--
CREATE TABLE `changelog` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version_tag` VARCHAR(50) NOT NULL,
  `summary` TEXT NOT NULL,
  `created_by_discord_id` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE SET NULL -- Falls Admin gelöscht wird, bleibt Changelog erhalten
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Beispiel Daten für Tabelle `changelog`
--
INSERT INTO `changelog` (`version_tag`, `summary`, `created_by_discord_id`, `created_at`) VALUES
('6.9-Alpha', 'Initialer Launch der Endo Reserve Bank Plattform.', '1020559274012852294', NOW()),
('6.9-Delta', '- Footer-Text angepasst.\n- MOTD-System überarbeitet (Verlauf, Entwürfe, Löschen).\n- Button-Lesbarkeit für helle Themen verbessert.\n- Dynamische Akzentfarben für mehr UI-Elemente.', '1020559274012852294', NOW());


COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
