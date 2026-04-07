CREATE TABLE IF NOT EXISTS `locally_funded_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint unsigned NOT NULL,
  
  -- Project Profile
  `province` varchar(255) NOT NULL,
  `city_municipality` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `subaybayan_project_code` varchar(255) NOT NULL UNIQUE,
  `project_description` longtext NOT NULL,
  `project_type` varchar(255) NOT NULL,
  `date_nadai` date NOT NULL,
  `lgsf_allocation` decimal(15,2) NOT NULL,
  `lgu_counterpart` decimal(15,2) NOT NULL,
  `no_of_beneficiaries` int NOT NULL,
  `rainwater_collection_system` varchar(255) NOT NULL,
  `date_confirmation_fund_receipt` date NOT NULL,
  
  -- Contract Information
  `mode_of_procurement` varchar(255) NOT NULL,
  `implementing_unit` varchar(255) NOT NULL,
  `date_posting_itb` date NOT NULL,
  `date_bid_opening` date NOT NULL,
  `date_noa` date NOT NULL,
  `date_ntp` date NOT NULL,
  `contractor` varchar(255) NOT NULL,
  `contract_amount` decimal(15,2) NOT NULL,
  `project_duration` varchar(255) NOT NULL,
  `actual_start_date` date NOT NULL,
  `target_date_completion` date NOT NULL,
  `revised_target_date_completion` date,
  `actual_date_completion` date,
  `actual_date_completion_updated_by` bigint unsigned,

  -- Financial Accomplishment
  `disbursed_amount` decimal(15,2),
  `obligation` decimal(15,2),
  `reverted_amount` decimal(15,2),
  `balance` decimal(15,2),
  `utilization_rate` decimal(5,2),
  `financial_remarks` longtext,
  
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign key
  CONSTRAINT `fk_locally_funded_projects_user` FOREIGN KEY (`user_id`) REFERENCES `tbusers` (`idno`) ON DELETE CASCADE,
  
  -- Indexes for better query performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_province` (`province`),
  INDEX `idx_city_municipality` (`city_municipality`),
  INDEX `idx_subaybayan_project_code` (`subaybayan_project_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
