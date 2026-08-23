-- ============================================================
-- The Wave Men's Salon - Single Merged Database SQL
-- Complete Structure (Schema) + Initial Seed Data
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `wave_salon` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wave_salon`;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `loyalty_transactions`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `appointment_services`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `otp_verifications`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `salaries`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `offers`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `service_categories`;
DROP TABLE IF EXISTS `staff_schedules`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `role` ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `phone_verified_at` DATETIME DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `loyalty_points` INT NOT NULL DEFAULT 0,
  `membership_tier` ENUM('Standard','Gold','Platinum','Diamond') NOT NULL DEFAULT 'Standard',
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT 'male',
  `address` TEXT DEFAULT NULL,
  `fcm_token` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_uuid` (`uuid`),
  UNIQUE KEY `uk_users_phone` (`phone`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_tier` (`membership_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: otp_verifications
-- ------------------------------------------------------------
CREATE TABLE `otp_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `purpose` ENUM('login','register','phone_change','password_reset') NOT NULL DEFAULT 'login',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `channel` ENUM('sms','email','log') NOT NULL DEFAULT 'sms',
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_phone` (`phone`),
  KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: service_categories
-- ------------------------------------------------------------
CREATE TABLE `service_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: services
-- ------------------------------------------------------------
CREATE TABLE `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 30,
  `price` DECIMAL(10,2) NOT NULL,
  `discount_price` DECIMAL(10,2) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_service_slug` (`slug`),
  KEY `idx_services_category` (`category_id`),
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: staff_schedules
-- ------------------------------------------------------------
CREATE TABLE `staff_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Sun..6=Sat',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_off` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_staff` (`staff_id`),
  CONSTRAINT `fk_schedule_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: appointments
-- ------------------------------------------------------------
CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` VARCHAR(32) NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED DEFAULT NULL,
  `appointment_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('Pending','Confirmed','Completed','Cancelled','No-Show') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `qr_code` VARCHAR(255) DEFAULT NULL,
  `calendar_event_id` VARCHAR(255) DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `final_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_booking_id` (`booking_id`),
  KEY `idx_appt_customer` (`customer_id`),
  KEY `idx_appt_staff` (`staff_id`),
  KEY `idx_appt_date` (`appointment_date`),
  KEY `idx_appt_status` (`status`),
  CONSTRAINT `fk_appt_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_appt_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: appointment_services
-- ------------------------------------------------------------
CREATE TABLE `appointment_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration_minutes` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_as_appt` (`appointment_id`),
  KEY `idx_as_service` (`service_id`),
  CONSTRAINT `fk_as_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: payments
-- ------------------------------------------------------------
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` ENUM('Cash','Card','UPI','Wallet','Stripe','Razorpay') NOT NULL DEFAULT 'Cash',
  `status` ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `gateway` VARCHAR(50) DEFAULT NULL,
  `gateway_payment_id` VARCHAR(255) DEFAULT NULL,
  `gateway_order_id` VARCHAR(255) DEFAULT NULL,
  `meta` JSON DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_appt` (`appointment_id`),
  KEY `idx_pay_customer` (`customer_id`),
  CONSTRAINT `fk_pay_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pay_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: invoices
-- ------------------------------------------------------------
CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(40) NOT NULL,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` BIGINT UNSIGNED DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(10,2) NOT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Draft','Issued','Paid','Void') NOT NULL DEFAULT 'Issued',
  `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_number` (`invoice_number`),
  KEY `idx_inv_customer` (`customer_id`),
  CONSTRAINT `fk_inv_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inv_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inv_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: invoice_items
-- ------------------------------------------------------------
CREATE TABLE `invoice_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: reviews
-- ------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `staff_id` BIGINT UNSIGNED DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_customer` (`customer_id`),
  CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: loyalty_transactions
-- ------------------------------------------------------------
CREATE TABLE `loyalty_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `type` ENUM('earn','redeem','adjust','expire') NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` BIGINT UNSIGNED DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loyalty_user` (`user_id`),
  CONSTRAINT `fk_loyalty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: offers
-- ------------------------------------------------------------
CREATE TABLE `offers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `code` VARCHAR(40) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_amount` DECIMAL(10,2) DEFAULT 0,
  `max_uses` INT DEFAULT NULL,
  `used_count` INT NOT NULL DEFAULT 0,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_offer_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: gallery
-- ------------------------------------------------------------
CREATE TABLE `gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) DEFAULT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `cloud_url` VARCHAR(500) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'general',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: attendance
-- ------------------------------------------------------------
CREATE TABLE `attendance` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `status` ENUM('Present','Absent','Leave','Half-Day') NOT NULL DEFAULT 'Present',
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attendance` (`staff_id`,`date`),
  CONSTRAINT `fk_att_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: salaries
-- ------------------------------------------------------------
CREATE TABLE `salaries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `month` TINYINT NOT NULL,
  `year` SMALLINT NOT NULL,
  `base_salary` DECIMAL(12,2) NOT NULL,
  `bonus` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `deductions` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `net_salary` DECIMAL(12,2) NOT NULL,
  `status` ENUM('Pending','Paid') NOT NULL DEFAULT 'Pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_salary` (`staff_id`,`month`,`year`),
  CONSTRAINT `fk_sal_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: expenses
-- ------------------------------------------------------------
CREATE TABLE `expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `receipt` VARCHAR(255) DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exp_date` (`expense_date`),
  CONSTRAINT `fk_exp_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: inventory
-- ------------------------------------------------------------
CREATE TABLE `inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(60) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) DEFAULT 'pcs',
  `reorder_level` INT NOT NULL DEFAULT 5,
  `unit_cost` DECIMAL(10,2) DEFAULT 0,
  `supplier` VARCHAR(150) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inv_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `body` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'general',
  `data` JSON DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `sent_via` ENUM('app','fcm','email','sms') DEFAULT 'app',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: settings
-- ------------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `group_name` VARCHAR(50) DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INITIAL SEED DATA
-- ============================================================

-- Default users
INSERT INTO `users` (`uuid`,`role`,`name`,`phone`,`email`,`password`,`phone_verified_at`,`loyalty_points`,`membership_tier`,`is_active`) VALUES
(UUID(),'admin','Salon Admin','+919999000001','contactwavemenssalon@gmail.com','$2y$10$tjiBiTb8iSiiTBYVwVljhOV25osK44qb0fKlYF2do4OIteXQxPyVu',NOW(),0,'Diamond',1),
(UUID(),'staff','Rahul Sharma','+919999000002','rahul@thewavemenssalon.com','$2y$10$.9eVDHrveBkPE2yDmKBIx.Hjg5j1OA7l8ld9LleThB3WlQeAABEsW',NOW(),0,'Standard',1),
(UUID(),'staff','Amit Verma','+919999000003','amit@thewavemenssalon.com','$2y$10$.9eVDHrveBkPE2yDmKBIx.Hjg5j1OA7l8ld9LleThB3WlQeAABEsW',NOW(),0,'Standard',1),
(UUID(),'staff','Vikram Singh','+919999000004','vikram@thewavemenssalon.com','$2y$10$.9eVDHrveBkPE2yDmKBIx.Hjg5j1OA7l8ld9LleThB3WlQeAABEsW',NOW(),0,'Standard',1),
(UUID(),'staff','Karan Mehta','+919999000005','karan@thewavemenssalon.com','$2y$10$.9eVDHrveBkPE2yDmKBIx.Hjg5j1OA7l8ld9LleThB3WlQeAABEsW',NOW(),0,'Standard',1),
(UUID(),'customer','Demo Customer','+919876543210','demo@example.com','$2y$10$JWEu2rjLEdeYZBKMYWx77e8.o/.u.LY3NcDCdR/opmIxBfdbxOaym',NOW(),750,'Gold',1),
(UUID(),'customer','Arjun Patel','+919876543211','arjun@example.com','$2y$10$JWEu2rjLEdeYZBKMYWx77e8.o/.u.LY3NcDCdR/opmIxBfdbxOaym',NOW(),320,'Standard',1),
(UUID(),'customer','Rohan Kapoor','+919876543212','rohan@example.com','$2y$10$JWEu2rjLEdeYZBKMYWx77e8.o/.u.LY3NcDCdR/opmIxBfdbxOaym',NOW(),2100,'Platinum',1),
(UUID(),'customer','Siddharth Rao','+919876543213','sid@example.com','$2y$10$JWEu2rjLEdeYZBKMYWx77e8.o/.u.LY3NcDCdR/opmIxBfdbxOaym',NOW(),120,'Standard',1),
(UUID(),'customer','Neel Joshi','+919876543214','neel@example.com','$2y$10$JWEu2rjLEdeYZBKMYWx77e8.o/.u.LY3NcDCdR/opmIxBfdbxOaym',NOW(),5600,'Diamond',1);

-- Service Categories
INSERT INTO `service_categories` (`id`,`name`,`slug`,`description`,`icon`,`sort_order`) VALUES
(1,'Haircuts & Hair Care','haircuts-care','Scissor cuts and soothing hair washes','scissors',1),
(2,'Beard Grooming','beard-grooming','Precision beard trims, styling, and coloring','mustache',2),
(3,'Facial & De-Tan Care','facial-detan','De-tanning, clean-ups, facials, and face massages','face',3),
(4,'Hair Colour','hair-colour','Professional matrix, loreal, and wella hair coloring','palette',4),
(5,'Hair Spa & Treatments','hair-spa','Nourishing and restorative hair spa rituals','spa',5);

-- Services
INSERT INTO `services` (`id`,`category_id`,`name`,`slug`,`description`,`duration_minutes`,`price`,`discount_price`,`is_featured`,`sort_order`) VALUES
(1,1,'Haircut','haircut','Precision haircut and styling',30,200.00,NULL,1,1),
(2,1,'Normal Hairwash','normal-hairwash','Refreshing hair wash and scalp clean',15,100.00,NULL,0,2),
(3,1,'Premium Hairwash','premium-hairwash','Deep conditioning hair wash ritual',20,150.00,NULL,1,3),
(4,2,'Normal Beard','normal-beard','Classic beard trim and outline',20,150.00,NULL,1,4),
(5,2,'Fade Beard','fade-beard','Modern sharp gradient fade beard shaping',25,200.00,NULL,1,5),
(6,2,'Beard Colour','beard-colour','Natural coverage beard colouring',30,300.00,NULL,0,6),
(7,3,'De-Tan ₹500','detan-500','Essential skin de-tan treatment',30,500.00,NULL,0,7),
(8,3,'De-Tan ₹700','detan-700','Advanced de-tan therapy with skin glow',40,700.00,NULL,1,8),
(9,3,'De-Tan ₹900','detan-900','Ultra de-tan deep pigmentation removal',45,900.00,NULL,0,9),
(10,3,'Clean-Up ₹1000','clean-up-1000','Deep pore skin clean-up and steam',45,1000.00,NULL,0,10),
(11,3,'Clean-Up ₹1250','clean-up-1250','Premium skin clean-up with blackhead removal',50,1250.00,NULL,1,11),
(12,3,'Facial Oxy Life (₹1500)','facial-oxy-life-1500','Oxygenating skin radiance facial by Oxy Life',60,1500.00,NULL,1,12),
(13,3,'Facial N+ (₹1800)','facial-n-plus-1800','Intensive skin repair facial by N+',60,1800.00,NULL,0,13),
(14,3,'Facial N+ (₹2000)','facial-n-plus-2000','Advanced whitening & glow facial by N+',60,2000.00,NULL,1,14),
(15,3,'Facial Coteskin (₹2500)','facial-coteskin-2500','Dermatological deep hydrate facial by Coteskin',65,2500.00,NULL,0,15),
(16,3,'Facial Coteskin (₹3000)','facial-coteskin-3000','Luxury anti-aging & brightness facial by Coteskin',75,3000.00,NULL,1,16),
(17,3,'Facial Briller (₹3500)','facial-briller-3500','Ultra luxury diamond shine facial by Briller',75,3500.00,NULL,1,17),
(18,3,'Facial Lotus (₹4000)','facial-lotus-4000','Supreme herbal luxury facial by Lotus',90,4000.00,NULL,1,18),
(19,3,'Face Massage','face-massage-250','Relaxing muscle stress relief face massage',25,250.00,NULL,0,19),
(20,4,'Hair Colour Matrix','hair-colour-matrix-300','Matrix rich shade hair colouring',45,300.00,NULL,1,20),
(21,4,'Hair Colour Loreal','hair-colour-loreal-350','L\'Oreal Paris salon hair color finish',45,350.00,NULL,1,21),
(22,4,'Hair Colour Wella','hair-colour-wella-400','Wella Koleston premium hair colour',45,400.00,NULL,1,22),
(23,5,'Normal Hair Spa','normal-hairspa-500','Essential moisture hair spa ritual',45,500.00,NULL,0,23),
(24,5,'Hair Spa Matrix','hair-spa-matrix-600','Matrix Biolage deep nourishment hair spa',60,600.00,NULL,1,24),
(25,5,'Hair Spa Loreal','hair-spa-loreal-800','L\'Oreal Professionnel mythic hair spa',60,800.00,NULL,1,25),
(26,5,'Hair Spa Wella','hair-spa-wella-1100','Wella System Professional intense therapy spa',60,1100.00,NULL,1,26);

-- Offers
INSERT INTO `offers` (`title`,`code`,`description`,`discount_type`,`discount_value`,`min_amount`,`max_uses`,`starts_at`,`ends_at`,`is_active`) VALUES
('Welcome Offer','WAVE10','10% off your first booking','percent',10,500,1000,NOW(),DATE_ADD(NOW(), INTERVAL 1 YEAR),1),
('Gold Member Perk','GOLD15','15% off for Gold members','percent',15,800,NULL,NOW(),DATE_ADD(NOW(), INTERVAL 1 YEAR),1),
('Festive Flat','FLAT200','Flat ₹200 off on services','fixed',200,1500,500,NOW(),DATE_ADD(NOW(), INTERVAL 6 MONTH),1);

-- Inventory
INSERT INTO `inventory` (`name`,`sku`,`category`,`quantity`,`unit`,`reorder_level`,`unit_cost`,`supplier`) VALUES
('Premium Pomade','INV-POM-01','Styling',48,'jar',10,350,'StyleCo'),
('Beard Oil 30ml','INV-BO-01','Grooming',60,'bottle',15,280,'BeardLab'),
('Shampoo 1L','INV-SH-01','Haircare',24,'bottle',8,450,'LuxeHair'),
('Aftershave Balm','INV-AS-01','Grooming',35,'tube',10,220,'SkinForge'),
('Disposable Blades','INV-BL-01','Tools',200,'pcs',50,5,'BarberSupply'),
('Hot Towel Set','INV-HT-01','Tools',40,'set',10,120,'SalonEssentials'),
('Hair Colour - Matrix','INV-HC-MX','Colour',18,'tube',5,380,'MatrixPro'),
('Facial Kit - Lotus','INV-FK-LT','Facial',12,'kit',3,1800,'LotusLab');

-- Gallery
INSERT INTO `gallery` (`title`,`image_path`,`category`,`sort_order`,`is_active`) VALUES
('Classic Cut Finish','assets/img/gallery/cut-1.jpg','haircuts',1,1),
('Beard Sculpture','assets/img/gallery/beard-1.jpg','beard',2,1),
('Luxury Interior','assets/img/gallery/salon-1.jpg','salon',3,1),
('Hot Towel Ritual','assets/img/gallery/shave-1.jpg','shave',4,1),
('Gold Facial Glow','assets/img/gallery/facial-1.jpg','facial',5,1),
('Executive Style','assets/img/gallery/cut-2.jpg','haircuts',6,1);

-- Settings
INSERT INTO `settings` (`setting_key`,`setting_value`,`group_name`) VALUES
('salon_name',"The Wave Men's Salon",'general'),
('tax_percent','18','billing'),
('currency','INR','billing'),
('booking_slot_minutes','30','booking'),
('advance_booking_days','30','booking'),
('cancellation_hours','4','booking'),
('google_maps_embed','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.0!2d77.209!3d28.6139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjjCsDM2JzUwLjAiTiA3N8KwMTInMzIuNCJF!5e0!3m2!1sen!2sin!4v1','contact'),
('about_text','The Wave Men\'s Salon delivers refined grooming experiences in a luxury black-and-gold setting. From precision cuts to restorative treatments, every visit is crafted for the modern gentleman.','about'),
('fcm_enabled','0','notifications'),
('maintenance_mode','0','system');

-- Reviews
INSERT INTO `reviews` (`customer_id`,`rating`,`title`,`comment`,`is_approved`,`is_featured`) VALUES
(6,5,'Best haircut and beard fade','Rahul gave me an impeccable haircut and fade beard. The ambiance is pure luxury.','1','1'),
(6,5,'Worth every rupee','Facial Lotus and Hair Spa Wella were outstanding. Next level service.','1','1'),
(8,5,'Platinum experience','Always consistent — Rohan here for Facial Coteskin every month.','1','1'),
(10,4,'Diamond worth it','Neel — Facial Oxy Life was excellent, will return.','1','0');

-- Staff Schedules
INSERT INTO `staff_schedules` (`staff_id`,`day_of_week`,`start_time`,`end_time`,`is_off`) VALUES
(2,1,'09:00:00','21:00:00',0),(2,2,'09:00:00','21:00:00',0),(2,3,'09:00:00','21:00:00',0),
(2,4,'09:00:00','21:00:00',0),(2,5,'09:00:00','21:00:00',0),(2,6,'10:00:00','20:00:00',0),(2,0,'10:00:00','18:00:00',0),
(3,1,'09:00:00','21:00:00',0),(3,2,'09:00:00','21:00:00',0),(3,3,'09:00:00','21:00:00',0),
(3,4,'09:00:00','21:00:00',0),(3,5,'09:00:00','21:00:00',0),(3,6,'10:00:00','20:00:00',0),(3,0,'00:00:00','00:00:00',1),
(4,1,'10:00:00','20:00:00',0),(4,2,'10:00:00','20:00:00',0),(4,3,'10:00:00','20:00:00',0),
(4,4,'10:00:00','20:00:00',0),(4,5,'10:00:00','20:00:00',0),(4,6,'10:00:00','18:00:00',0),(4,0,'10:00:00','18:00:00',0),
(5,1,'09:00:00','20:00:00',0),(5,2,'09:00:00','20:00:00',0),(5,3,'09:00:00','20:00:00',0),
(5,4,'09:00:00','20:00:00',0),(5,5,'09:00:00','20:00:00',0),(5,6,'10:00:00','18:00:00',0),(5,0,'00:00:00','00:00:00',1);

-- Appointments
INSERT INTO `appointments` (`booking_id`,`customer_id`,`staff_id`,`appointment_date`,`start_time`,`end_time`,`status`,`total_amount`,`discount_amount`,`final_amount`,`confirmed_at`,`completed_at`) VALUES
('TW-DEMO0001-250801',6,2,DATE_SUB(CURDATE(), INTERVAL 45 DAY),'10:00:00','10:45:00','Completed',400.00,0,400.00,DATE_SUB(NOW(), INTERVAL 45 DAY),DATE_SUB(NOW(), INTERVAL 45 DAY)),
('TW-DEMO0002-250820',6,2,DATE_SUB(CURDATE(), INTERVAL 20 DAY),'11:00:00','12:15:00','Completed',1100.00,0,1100.00,DATE_SUB(NOW(), INTERVAL 20 DAY),DATE_SUB(NOW(), INTERVAL 20 DAY)),
('TW-DEMO0003-250901',6,3,DATE_ADD(CURDATE(), INTERVAL 3 DAY),'15:00:00','15:40:00','Confirmed',500.00,0,500.00,NOW(),NULL),
('TW-ROHAN01-250710',8,3,DATE_SUB(CURDATE(), INTERVAL 60 DAY),'12:00:00','14:00:00','Completed',2500.00,200,2300.00,DATE_SUB(NOW(), INTERVAL 60 DAY),DATE_SUB(NOW(), INTERVAL 60 DAY)),
('TW-ROHAN02-250815',8,2,DATE_SUB(CURDATE(), INTERVAL 12 DAY),'16:00:00','17:00:00','Completed',800.00,0,800.00,DATE_SUB(NOW(), INTERVAL 12 DAY),DATE_SUB(NOW(), INTERVAL 12 DAY)),
('TW-NEEL001-250825',10,4,DATE_SUB(CURDATE(), INTERVAL 10 DAY),'14:00:00','15:10:00','Completed',1500.00,0,1500.00,DATE_SUB(NOW(), INTERVAL 10 DAY),DATE_SUB(NOW(), INTERVAL 10 DAY));

-- Appointment Services
INSERT INTO `appointment_services` (`appointment_id`,`service_id`,`price`,`duration_minutes`) VALUES
(1,1,200.00,30),(1,5,200.00,25),
(2,26,1100.00,60),
(3,23,500.00,45),
(4,15,2500.00,65),
(5,25,800.00,60),
(6,12,1500.00,60);

-- Payments
INSERT INTO `payments` (`appointment_id`,`customer_id`,`amount`,`method`,`status`,`paid_at`) VALUES
(1,6,400.00,'Cash','Paid',DATE_SUB(NOW(), INTERVAL 45 DAY)),
(2,6,1100.00,'Cash','Paid',DATE_SUB(NOW(), INTERVAL 20 DAY)),
(4,8,2300.00,'Cash','Paid',DATE_SUB(NOW(), INTERVAL 60 DAY)),
(5,8,800.00,'Cash','Paid',DATE_SUB(NOW(), INTERVAL 12 DAY)),
(6,10,1500.00,'Cash','Paid',DATE_SUB(NOW(), INTERVAL 10 DAY));

-- Loyalty Transactions
INSERT INTO `loyalty_transactions` (`user_id`,`points`,`type`,`reference_type`,`reference_id`,`description`) VALUES
(6,400,'earn','appointment',1,'Earned 400 points'),
(6,1100,'earn','appointment',2,'Earned 1100 points'),
(8,2300,'earn','appointment',4,'Earned 2300 points'),
(8,800,'earn','appointment',5,'Earned 800 points'),
(10,1500,'earn','appointment',6,'Earned 1500 points');

SET FOREIGN_KEY_CHECKS = 1;
