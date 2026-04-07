CREATE TABLE IF NOT EXISTS `locally_funded_financial_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` bigint unsigned NOT NULL,
  `year` int NOT NULL,
  `month` int NOT NULL,
  `obligation` decimal(15,2),
  `disbursed_amount` decimal(15,2),
  `reverted_amount` decimal(15,2),
  `utilization_rate` decimal(5,2),
  `updated_by` bigint unsigned,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_locally_funded_financial_project` FOREIGN KEY (`project_id`) REFERENCES `locally_funded_projects` (`id`) ON DELETE CASCADE,
  INDEX `idx_financial_project_year_month` (`project_id`, `year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
