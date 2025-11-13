-- Vapi Integration Module - Database Installation
-- Run this SQL in phpMyAdmin to create the necessary tables

CREATE TABLE IF NOT EXISTS `tblvapi_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `request_payload` longtext,
  `response_payload` longtext,
  `recording_url` varchar(1024) DEFAULT NULL,
  `transcript` longtext,
  `duration_seconds` int(11) DEFAULT NULL,
  `ended_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `external_id` (`external_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblvapi_call_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vapi_call_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `event_type` varchar(191) DEFAULT NULL,
  `event_payload` longtext,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vapi_call_id` (`vapi_call_id`),
  KEY `lead_id` (`lead_id`),
  KEY `external_id` (`external_id`),
  KEY `event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblvapi_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(50) DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `total_leads` int(11) DEFAULT 0,
  `calls_initiated` int(11) DEFAULT 0,
  `calls_completed` int(11) DEFAULT 0,
  `calls_failed` int(11) DEFAULT 0,
  `calls_pending` int(11) DEFAULT 0,
  `lead_filter` longtext,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblvapi_campaign_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `call_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `lead_id` (`lead_id`),
  KEY `call_id` (`call_id`),
  KEY `status` (`status`),
  UNIQUE KEY `campaign_lead_unique` (`campaign_id`, `lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;