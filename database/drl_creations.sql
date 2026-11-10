-- =============================================================================
-- PROJET : DRL CRÉATIONS — L'ÉLÉGANCE PREND FORME
-- Base de données relationnelle MySQL 8.0+ (UTF8MB4)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `drl_creations`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `drl_creations`;

-- -----------------------------------------------------------------------------
-- 1. Table : users (Clients & Administrateurs)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('client', 'admin') NOT NULL DEFAULT 'client',
  `oauth_provider` ENUM('local', 'google', 'apple') NOT NULL DEFAULT 'local',
  `oauth_id` VARCHAR(191) DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `verification_token` VARCHAR(128) DEFAULT NULL,
  `reset_token` VARCHAR(128) DEFAULT NULL,
  `reset_expires_at` DATETIME DEFAULT NULL,
  `failed_login_attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `preferred_lang` ENUM('fr', 'en') NOT NULL DEFAULT 'fr',
  `preferred_theme` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_oauth` (`oauth_provider`, `oauth_id`),
  KEY `idx_users_verification` (`verification_token`),
  KEY `idx_users_reset` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table : categories (Catégories Boutique & Créations)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_fr` VARCHAR(120) NOT NULL,
  `name_en` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `type` ENUM('creation', 'product', 'both') NOT NULL DEFAULT 'both',
  `description_fr` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_type_active` (`type`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table : products (Articles de la Boutique)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `reference` VARCHAR(60) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `name_fr` VARCHAR(180) NOT NULL,
  `name_en` VARCHAR(180) NOT NULL,
  `description_fr` TEXT NOT NULL,
  `description_en` TEXT DEFAULT NULL,
  `fabric_details` VARCHAR(255) DEFAULT NULL,
  `sizes_available` VARCHAR(150) DEFAULT 'S, M, L, Sur-mesure',
  `price` DECIMAL(12,2) UNSIGNED DEFAULT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'FCFA',
  `pricing_mode` ENUM('fixed', 'negotiable', 'on_request') NOT NULL DEFAULT 'fixed',
  `availability_status` ENUM('available', 'sold', 'archived', 'hidden') NOT NULL DEFAULT 'available',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_reference` (`reference`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status_featured` (`availability_status`, `is_featured`),
  KEY `idx_products_pricing` (`pricing_mode`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table : product_images (Galerie photographique des articles)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(180) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`, `is_primary`, `sort_order`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table : creations (Modèles réalisables par l'atelier)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `creations`;
CREATE TABLE `creations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `reference` VARCHAR(60) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `name_fr` VARCHAR(180) NOT NULL,
  `name_en` VARCHAR(180) NOT NULL,
  `description_fr` TEXT NOT NULL,
  `description_en` TEXT DEFAULT NULL,
  `estimated_delay` VARCHAR(100) DEFAULT '7 à 14 jours ouvrés',
  `customizable_options` VARCHAR(255) DEFAULT 'Tissu, Couleur, Broderie, Mensurations sur-mesure',
  `indicative_price` DECIMAL(12,2) UNSIGNED DEFAULT NULL,
  `publication_status` ENUM('published', 'unpublished', 'draft') NOT NULL DEFAULT 'published',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_creations_reference` (`reference`),
  UNIQUE KEY `uk_creations_slug` (`slug`),
  KEY `idx_creations_category` (`category_id`),
  KEY `idx_creations_status_featured` (`publication_status`, `is_featured`),
  CONSTRAINT `fk_creations_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table : creation_images (Galerie photographique des créations)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `creation_images`;
CREATE TABLE `creation_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `creation_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(180) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_creation_images_creation` (`creation_id`, `is_primary`, `sort_order`),
  CONSTRAINT `fk_creation_images_creation` FOREIGN KEY (`creation_id`) REFERENCES `creations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Table : favorites (Favoris des clients : articles & modèles)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `item_type` ENUM('product', 'creation') NOT NULL DEFAULT 'product',
  `product_id` INT UNSIGNED DEFAULT NULL,
  `creation_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_favorites_user_product` (`user_id`, `item_type`, `product_id`),
  UNIQUE KEY `uk_favorites_user_creation` (`user_id`, `item_type`, `creation_id`),
  KEY `idx_favorites_user` (`user_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_creation` FOREIGN KEY (`creation_id`) REFERENCES `creations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Table : site_settings (Paramètres globaux de l'atelier & de l'IA)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(60) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_settings_key` (`setting_key`),
  KEY `idx_site_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Table : faq (Questions fréquentes et réponses officielles pour l'IA & le site)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `faq`;
CREATE TABLE `faq` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_fr` VARCHAR(255) NOT NULL,
  `question_en` VARCHAR(255) NOT NULL,
  `answer_fr` TEXT NOT NULL,
  `answer_en` TEXT NOT NULL,
  `keywords` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faq_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Table : ai_conversations (Historique des échanges avec DRL Assistant)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ai_conversations`;
CREATE TABLE `ai_conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(128) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_message` TEXT NOT NULL,
  `assistant_reply` TEXT NOT NULL,
  `detected_intent` VARCHAR(80) DEFAULT NULL,
  `ip_hash` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ai_conv_session` (`session_id`),
  KEY `idx_ai_conv_user` (`user_id`),
  CONSTRAINT `fk_ai_conv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Table : custom_requests (Demandes de modèles personnalisés, devis & contact)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `custom_requests`;
CREATE TABLE `custom_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `request_type` ENUM('creation_order', 'product_inquiry', 'custom_measure', 'contact') NOT NULL DEFAULT 'creation_order',
  `creation_id` INT UNSIGNED DEFAULT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `full_name` VARCHAR(160) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `measurements_notes` TEXT DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_custom_requests_user` (`user_id`),
  KEY `idx_custom_requests_status` (`status`, `created_at`),
  CONSTRAINT `fk_custom_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_custom_requests_creation` FOREIGN KEY (`creation_id`) REFERENCES `creations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_custom_requests_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DONNÉES INITIALES (SEED) : CATÉGORIES, PARAMÈTRES, CATALOGUE
-- =============================================================================
DROP PROCEDURE IF EXISTS `sp_create_secure_admin`;
START TRANSACTION;

-- Catégories officielles (Exigences Section 6 & 7)
INSERT INTO `categories` (`id`, `name_fr`, `name_en`, `slug`, `type`, `description_fr`, `description_en`, `sort_order`, `is_active`) VALUES
(1, 'Robes de soirée', 'Evening Gowns', 'robes-de-soiree', 'both', 'Silhouettes sculptées, drapés soyeux et finitions main pour vos galas et réceptions.', 'Sculpted silhouettes, silky drapes and hand finishes for galas and receptions.', 1, 1),
(2, 'Robes de cérémonie', 'Ceremonial Dresses', 'robes-de-ceremonie', 'both', 'Pièces d''exception pensées pour sublimer les mariages, fiançailles et grands événements.', 'Exceptional pieces designed to elevate weddings, engagements and grand celebrations.', 2, 1),
(3, 'Tenues traditionnelles', 'Traditional Attire', 'tenues-traditionnelles', 'both', 'Hommage au patrimoine textile royal : broderies d''art, dentelles et étoffes nobles.', 'Tribute to royal textile heritage: art embroidery, lace and noble fabrics.', 3, 1),
(4, 'Tenues africaines', 'African Couture', 'tenues-africaines', 'both', 'Créations contemporaines en pagne tissé, Kanvo, Bazin riche et soie sauvage.', 'Contemporary creations in woven cloth, Kanvo, rich Bazin and wild silk.', 4, 1),
(5, 'Ensembles modernes', 'Modern Ensembles', 'ensembles-modernes', 'both', 'Tailleurs fluides, coupes architecturales et élégance citadine contemporaine.', 'Fluid tailoring, architectural cuts and contemporary urban elegance.', 5, 1),
(6, 'Costumes', 'Bespoke Suits', 'costumes', 'both', 'Costumes sur-mesure à l''allure impériale, entoilage traditionnel et tombé irréprochable.', 'Bespoke suits with imperial poise, traditional canvas construction and impeccable drape.', 6, 1),
(7, 'Créations personnalisées', 'Bespoke Creations', 'creations-personnalisees', 'creation', 'Une œuvre unique dessinée exclusivement selon votre morphologie et votre inspiration.', 'A one-of-a-kind piece sketched exclusively for your silhouette and inspiration.', 7, 1);

-- Modèles réalisables par l'atelier (Espace Nos Créations)
INSERT INTO `creations` (`id`, `category_id`, `reference`, `slug`, `name_fr`, `name_en`, `description_fr`, `description_en`, `estimated_delay`, `customizable_options`, `indicative_price`, `publication_status`, `is_featured`) VALUES
(1, 1, 'DRL-MOD-001', 'robe-imperiale-or-et-nuit', 'Robe Impériale Or & Nuit', 'Imperial Gold & Midnight Gown', 'Robe de haute couture en duchesse de soie bleu marine profond, rehaussée d''applications de dentelle dorée brodée à la main et d''une traîne fluide amovible.', 'Haute couture gown in deep navy silk duchesse, enhanced with hand-embroidered gold lace appliqués and a detachable fluid train.', '14 à 21 jours ouvrés', 'Longueur de traîne, décolleté, teinte du satin, broderies personnalisées', NULL, 'published', 1),
(2, 2, 'DRL-MOD-002', 'silhouette-champagne-celeste', 'Silhouette Champagne Céleste', 'Celestial Champagne Silhouette', 'Robe de cérémonie en crêpe georgette ivoire et organza champagne. Corsage drapé asymétrique et manches vaporeuses ornées de perles fines.', 'Ceremonial gown in ivory georgette crepe and champagne organza. Asymmetric draped bodice and airy sleeves adorned with fine pearls.', '10 à 15 jours ouvrés', 'Manches, voile assorti, choix de perlage, mensurations exactes', NULL, 'published', 1),
(3, 4, 'DRL-MOD-003', 'ensemble-royal-kanvo-prestige', 'Ensemble Royal Kanvo Prestige', 'Royal Kanvo Prestige Ensemble', 'Alliance majestueuse du pagne tissé traditionnel et d''une coupe tailleur haute couture. Finitions gansées d''or clair et doublure en soie respirante.', 'Majestic alliance of traditional handwoven cloth and haute couture tailoring. Light gold piped finishes and breathable silk lining.', '7 à 12 jours ouvrés', 'Motif du tissage, coupe de la jupe ou pantalon, broderie col', NULL, 'published', 1),
(4, 3, 'DRL-MOD-004', 'parure-reine-d-abomey', 'Parure Reine d''Abomey', 'Queen of Abomey Regalia', 'Tenue traditionnelle d''apparat en Bazin riche teint artisanalement et brodé au fil d''or champagne selon un motif géométrique exclusif DRL.', 'Traditional ceremonial attire in artisan-dyed rich Bazin embroidered with champagne gold thread in an exclusive DRL geometric motif.', '10 à 14 jours ouvrés', 'Couleur du Bazin, densité de broderie, foulard sculpté inclus', NULL, 'published', 0),
(5, 5, 'DRL-MOD-005', 'tailleur-architectural-ivoire', 'Tailleur Architectural Ivoire', 'Architectural Ivory Suit', 'Ensemble deux pièces en laine froide et soie ivoire. Veste cintrée à basque structurée et boutons martelés dorés, pantalon palazzo taille haute.', 'Two-piece ensemble in cool wool and ivory silk. Cinched jacket with structured peplum and hammered gold buttons, high-waisted palazzo trousers.', '7 à 10 jours ouvrés', 'Couleur du tissu, coupe du pantalon, gravure boutons', NULL, 'published', 1),
(6, 6, 'DRL-MOD-006', 'smoking-souverain-bleu-abysse', 'Smoking Souverain Bleu Abysse', 'Sovereign Abyss Blue Tuxedo', 'Costume trois pièces sur-mesure en laine Super 150s bleu marine, revers châle en satin champagne subtil et boutonnières cousues main.', 'Three-piece bespoke suit in navy Super 150s wool, subtle champagne satin shawl lapel and hand-stitched buttonholes.', '12 à 18 jours ouvrés', 'Revers (châle ou cran aigu), doublure monogrammée, gilet croisé', NULL, 'published', 1);

INSERT INTO `creation_images` (`creation_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1, 'assets/images/creation-1.svg', 'Robe Impériale Or & Nuit - DRL Créations', 1, 1),
(2, 'assets/images/creation-2.svg', 'Silhouette Champagne Céleste - DRL Créations', 1, 1),
(3, 'assets/images/creation-3.svg', 'Ensemble Royal Kanvo Prestige - DRL Créations', 1, 1),
(4, 'assets/images/creation-4.svg', 'Parure Reine d''Abomey - DRL Créations', 1, 1),
(5, 'assets/images/creation-5.svg', 'Tailleur Architectural Ivoire - DRL Créations', 1, 1),
(6, 'assets/images/creation-6.svg', 'Smoking Souverain Bleu Abysse - DRL Créations', 1, 1);

-- Articles de la Boutique (3 modes de tarification : fixed, negotiable, on_request)
INSERT INTO `products` (`id`, `category_id`, `reference`, `slug`, `name_fr`, `name_en`, `description_fr`, `description_en`, `fabric_details`, `sizes_available`, `price`, `currency`, `pricing_mode`, `availability_status`, `is_featured`) VALUES
(1, 1, 'DRL-BTQ-101', 'robe-duchesse-eclat-d-or', 'Robe Duchesse Éclat d''Or', 'Golden Radiance Duchesse Gown', 'Pièce unique prête à porter issue de notre collection capsule. Satin duchesse bleu nuit avec incrustations de guipure dorée sur le bustier et fente latérale délicate.', 'One-of-a-kind ready-to-wear piece from our capsule collection. Midnight blue duchesse satin with gold guipure inlays on the bustier and delicate side slit.', 'Satin duchesse & Guipure dorée', 'M (Ajustable en atelier)', 185000.00, 'FCFA', 'fixed', 'available', 1),
(2, 5, 'DRL-BTQ-102', 'ensemble-fluide-beige-sable', 'Ensemble Fluide Beige Textile', 'Fluid Textile Beige Ensemble', 'Ensemble tunique asymétrique et pantalon large en lin lavé et soie beige textile, gansé de fil champagne. Confort absolu et tombé sculptural.', 'Asymmetric tunic and wide-leg trouser set in washed linen and textile beige silk, piped with champagne thread. Absolute comfort and sculptural drape.', 'Lin lavé & Soie naturelle', 'S, M, L', 95000.00, 'FCFA', 'negotiable', 'available', 1),
(3, 4, 'DRL-BTQ-103', 'cape-couture-brodee-or-clair', 'Cape Couture Brodée Or Clair', 'Light Gold Embroidered Couture Cape', 'Cape d''apparat en velours léger bleu marine et doublure satin ivoire, entièrement rebrodée au fil d''or clair sur l''encolure et les poignets.', 'Ceremonial cape in lightweight navy velvet and ivory satin lining, fully hand-embroidered with light gold thread along the neckline and cuffs.', 'Velours de soie & Fil d''or', 'Taille unique majestueuse', 240000.00, 'FCFA', 'negotiable', 'available', 1),
(4, 2, 'DRL-BTQ-104', 'robe-de-gala-souveraine-ivoire', 'Robe de Gala Souveraine Ivoire', 'Sovereign Ivory Gala Gown', 'Création haute couture d''exposition réalisée entièrement à la main (plus de 80 heures d''atelier). Organza de soie triple épaisseur et cristaux cousus un à un.', 'Exhibition haute couture creation crafted entirely by hand (over 80 hours of atelier work). Triple-layer silk organza and individually hand-sewn crystals.', 'Organza de soie pur & Cristaux', 'Sur ajustement privé', NULL, 'FCFA', 'on_request', 'available', 1),
(5, 6, 'DRL-BTQ-105', 'veste-croisee-gentleman-champagne', 'Veste Croisée Gentleman Champagne', 'Champagne Gentleman Double-Breasted Jacket', 'Veste de smoking croisée en jacquard ton sur ton beige et or champagne, coupe napolitaine structurée et boutons en laiton brossé.', 'Double-breasted tuxedo jacket in tone-on-tone beige and champagne gold jacquard, structured Neapolitan cut and brushed brass buttons.', 'Jacquard italien & Doublure Bemberg', '48, 50, 52', 145000.00, 'FCFA', 'fixed', 'available', 0),
(6, 3, 'DRL-BTQ-106', 'kaftan-prestige-nuit-etoilee', 'Kaftan Prestige Nuit Étoilée', 'Starry Night Prestige Kaftan', 'Kaftan fluide en crêpe de soie bleu marine avec plastron travaillé à la main. Pièce déjà acquise par une cliente lors de notre vente privée.', 'Fluid kaftan in navy silk crepe with hand-worked plastron. Piece already acquired during our private salon sale.', 'Crêpe de soie & Passementerie', 'M', 160000.00, 'FCFA', 'fixed', 'sold', 0);

INSERT INTO `product_images` (`product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1, 'assets/images/product-1.svg', 'Robe Duchesse Éclat d''Or - DRL Créations', 1, 1),
(2, 'assets/images/product-2.svg', 'Ensemble Fluide Beige Textile - DRL Créations', 1, 1),
(3, 'assets/images/product-3.svg', 'Cape Couture Brodée Or Clair - DRL Créations', 1, 1),
(4, 'assets/images/product-4.svg', 'Robe de Gala Souveraine Ivoire - DRL Créations', 1, 1),
(5, 'assets/images/product-5.svg', 'Veste Croisée Gentleman Champagne - DRL Créations', 1, 1),
(6, 'assets/images/product-6.svg', 'Kaftan Prestige Nuit Étoilée - DRL Créations', 1, 1);

-- Paramètres globaux du site (site_settings)
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'DRL CRÉATIONS', 'general'),
('site_slogan_fr', 'L''élégance prend forme, le style devient une signature.', 'general'),
('site_slogan_en', 'Elegance takes shape, style becomes a signature.', 'general'),
('whatsapp_number', '2290197000000', 'contact'),
('contact_email', 'contact@drl-creations.com', 'contact'),
('contact_phone', '+229 01 97 00 00 00', 'contact'),
('atelier_address', 'Quartier Haie Vive, Cotonou, République du Bénin', 'contact'),
('atelier_hours_fr', 'Lundi – Samedi : 09h00 à 19h30 (Sur rendez-vous privé)', 'contact'),
('atelier_hours_en', 'Monday – Saturday: 9:00 AM to 7:30 PM (By private appointment)', 'contact'),
('instagram_url', 'https://instagram.com/drlcreations', 'social'),
('facebook_url', 'https://facebook.com/drlcreations', 'social'),
('tiktok_url', 'https://tiktok.com/@drlcreations', 'social'),
('pinterest_url', 'https://pinterest.com/drlcreations', 'social'),
('admin_bootstrap_claimed', '0', 'system'),
('about_story_fr', 'Fondée avec la conviction que chaque vêtement doit être le prolongement de l''âme et de la prestance, la Maison DRL CRÉATIONS perpétue l''art du sur-mesure et de la haute façon. De la sélection rigoureuse des étoffes nobles jusqu''au dernier point de broderie main, notre atelier façonne des pièces intemporelles où le patrimoine culturel rencontre la modernité architecturale.', 'content'),
('about_story_en', 'Founded on the belief that every garment should be an extension of soul and poise, Maison DRL CRÉATIONS perpetuates the art of bespoke tailoring and fine craftsmanship. From selecting noble fabrics to the final hand-embroidered stitch, our atelier shapes timeless pieces where cultural heritage meets architectural modernity.', 'content'),
('ai_enabled', '1', 'ai'),
('ai_welcome_fr', 'Bonjour et bienvenue chez DRL CRÉATIONS ✨ Je suis DRL Assistant, votre conseiller haute couture personnel. Souhaitez-vous explorer nos créations sur-mesure, vérifier le prix d''un article en boutique ou connaître nos délais de confection ?', 'ai'),
('ai_welcome_en', 'Hello and welcome to DRL CRÉATIONS ✨ I am DRL Assistant, your personal haute couture advisor. Would you like to explore our bespoke creations, check the price of a boutique item, or learn about our tailoring timelines?', 'ai'),
('site_logo', 'assets/images/logo.svg', 'general');

-- Questions fréquentes officielles (faq)
INSERT INTO `faq` (`id`, `question_fr`, `question_en`, `answer_fr`, `answer_en`, `keywords`, `sort_order`, `is_active`) VALUES
(1, 'Comment fonctionne la confection sur-mesure chez DRL CRÉATIONS ?', 'How does bespoke tailoring work at DRL CRÉATIONS?', 'Chaque création sur-mesure débute par une consultation personnalisée (en atelier ou via WhatsApp vidéo). Nous prenons vos mensurations exactes, sélectionnons ensemble les étoffes et réalisons un essayage intermédiaire avant la livraison finale.', 'Each bespoke creation begins with a personalized consultation (in our atelier or via WhatsApp video). We take your exact measurements, select fabrics together, and conduct a fitting before final delivery.', 'sur-mesure,mesure,personnalisation,essayage,confection,bespoke,custom,fitting', 1, 1),
(2, 'Quels sont les délais de réalisation d''un modèle ?', 'What are the production lead times for a model?', 'Nos délais varient entre 7 et 21 jours ouvrés selon la complexité du modèle et le travail de broderie main. Un service express sous 72h à 5 jours est disponible sur demande.', 'Our lead times range from 7 to 21 business days depending on model complexity and hand embroidery. An express 72h to 5-day service is available upon request.', 'délai,delai,temps,livraison,combien de jours,time,delay,delivery,express', 2, 1),
(3, 'Quelle est la différence entre Prix fixe, Prix négociable et Prix sur demande ?', 'What is the difference between Fixed Price, Negotiable Price and Price on Request?', 'En Boutique, les articles à « Prix fixe » ont un tarif arrêté. Les articles à « Prix négociable » vous permettent de discuter d''une offre directe avec l''atelier via WhatsApp. Les pièces d''exception « Prix sur demande » font l''objet d''une cotation personnalisée selon les ajustements souhaités.', 'In the Boutique, "Fixed Price" items have a set price. "Negotiable Price" items allow you to discuss a direct offer with our atelier via WhatsApp. Exceptional "Price on Request" pieces receive a tailored quote based on desired adjustments.', 'prix,tarif,négociable,negociable,demande,cout,coût,price,cost,quote', 3, 1),
(4, 'Livrez-vous à l''international ?', 'Do you ship internationally?', 'Oui, DRL CRÉATIONS expédie ses créations et articles de boutique partout dans le monde via DHL Express avec suivi sécurisé et housse de protection haute couture.', 'Yes, DRL CRÉATIONS ships bespoke creations and boutique items worldwide via DHL Express with secure tracking and a haute couture garment bag.', 'livraison,expédition,international,dhl,pays,shipping,worldwide,abroad', 4, 1),
(5, 'Comment prendre rendez-vous à l''atelier ?', 'How can I book an appointment at the atelier?', 'Vous pouvez réserver une séance privée via notre page Contact, en cliquant sur « Demander ce modèle » sur la création de votre choix, ou directement sur WhatsApp.', 'You can book a private session via our Contact page, by clicking "Request this model" on your chosen creation, or directly via WhatsApp.', 'rendez-vous,rdv,atelier,adresse,visite,horaire,appointment,visit,address', 5, 1);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
