-- Create tbfur_fdp table
CREATE TABLE IF NOT EXISTS `tbfur_fdp` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_code` varchar(255) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `fdp_file_path` varchar(255) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  UNIQUE KEY `tbfur_fdp_project_code_quarter_unique` (`project_code`, `quarter`),
  FOREIGN KEY (`project_code`) REFERENCES `tbfur` (`project_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tbfur_admin_remarks table
CREATE TABLE IF NOT EXISTS `tbfur_admin_remarks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_code` varchar(255) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `remarks` text NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  UNIQUE KEY `tbfur_admin_remarks_project_code_quarter_unique` (`project_code`, `quarter`),
  FOREIGN KEY (`project_code`) REFERENCES `tbfur` (`project_code`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `tbusers` (`idno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
