-- Task Management System — seeded database dump (FT-006 BR-4)
--
-- The same schema the migrations build, plus the dataset DatabaseSeeder creates:
-- the demo account (demo@example.com / password123), a second account, 5 projects
-- and 28 tasks. Load this only if you would rather not run the migrations; the two
-- routes produce identical tables and columns.
--
--   mysql -u root -p task_management_sys < database/dump/task_management_sys.sql
--
-- The database itself is not created here, so the name above is yours to choose.
-- Passwords are stored as bcrypt hashes of the published demo password; no real
-- credential and no real email address appears in this file (BR-17).

-- MySQL dump 10.13  Distrib 9.5.0, for macos26.1 (arm64)
--
-- Host: 127.0.0.1    Database: task_management_sys
-- ------------------------------------------------------
-- Server version	9.5.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_31_195644_create_personal_access_tokens_table',1),(5,'2026_07_31_213712_create_projects_table',1),(6,'2026_07_31_213713_create_tasks_table',1),(7,'2026_08_01_085604_add_owner_list_index_to_tasks_table',1),(8,'2026_08_01_120501_add_overdue_notified_at_to_tasks_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_user_id_deleted_at_created_at_index` (`user_id`,`deleted_at`,`created_at`),
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` (`id`, `user_id`, `name`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,1,'Website Redesign','Rebuild the marketing site on the new design system.','active','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL),(2,1,'Mobile App Launch','Ship the first public build to both stores.','active','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL),(3,1,'Q2 Marketing Campaign','The spring push, now closed out.','completed','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL),(4,1,'Legacy Portal Migration','Retire the old customer portal. Parked for now.','archived','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL),(5,2,'Internal Tooling','Nour\'s own work. The demo account cannot see any of it.','active','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todo',
  `due_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `overdue_notified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_project_id_deleted_at_index` (`project_id`,`deleted_at`),
  KEY `tasks_due_date_index` (`due_date`),
  KEY `tasks_deleted_due_index` (`deleted_at`,`due_date`),
  KEY `tasks_overdue_sweep_index` (`overdue_notified_at`,`due_date`),
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `priority`, `status`, `due_date`, `created_at`, `updated_at`, `deleted_at`, `overdue_notified_at`) VALUES (1,1,'Audit the current information architecture','Map every page that survives the rebuild.','high','done','2026-07-02','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(2,1,'Agree the new colour palette','Sign off the accessible contrast pairs.','medium','done','2026-07-11','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(3,1,'Rebuild the pricing page','Three tiers, an annual toggle, and no hard-coded copy.','high','in_progress','2026-07-27','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(4,1,'إعادة تصميم صفحة الاتصال','نموذج أقصر مع رسائل تحقق أوضح.','medium','todo','2026-07-30','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(5,1,'Write copy for the about page','Two paragraphs and the team list.','low','todo','2026-08-08','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(6,1,'Compress the hero images','Under 200 KB each, with no visible loss.','low','todo','2026-08-15','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(7,1,'Set up redirects for the old URLs','Every retired page points somewhere useful.','high','in_progress','2026-08-04','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(8,1,'Run an accessibility pass','Keyboard order, labels and contrast on every template.','medium','todo','2026-08-22','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(9,2,'Finalise the onboarding flow','Four screens, skippable after the first.','high','done','2026-07-14','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(10,2,'مراجعة نصوص الإشعارات','توحيد نبرة الرسائل قبل الإطلاق.','medium','in_progress','2026-07-23','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(11,2,'Ship the beta to TestFlight','Internal testers first, then the waiting list.','high','todo','2026-07-31','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(12,2,'Wire up push notifications','Device registration and one test payload.','high','in_progress','2026-08-06','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(13,2,'Localise the app store listing','English and Arabic, screenshots included.','medium','todo','2026-08-13','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(14,2,'Draft the release notes','What changed, in language a user recognises.','low','todo','2026-08-19','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(15,2,'Add crash reporting','Symbol upload wired into the release build.','medium','done','2026-07-20','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(16,3,'Book the newsletter slot','Second week, the partner list.','medium','done','2026-06-17','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(17,3,'Design the campaign banners','Three sizes for the ad network.','high','done','2026-06-22','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(18,3,'حملة إعلانات جوجل','ضبط الكلمات المفتاحية والميزانية اليومية.','high','done','2026-06-27','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(19,3,'Report on campaign reach','Impressions, clicks and sign-ups on one page.','low','done','2026-07-04','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(20,3,'Archive the campaign assets','Move the finals into shared storage.','low','todo','2026-07-12','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(21,3,'Collect testimonials from beta users','Five quotes, cleared for public use.','medium','todo','2026-08-10','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(22,4,'Export the legacy database','A full dump, checksummed and stored twice.','high','done','2026-06-02','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(23,4,'Map the old roles to the new ones','Nine legacy roles onto four.','medium','in_progress','2026-08-31','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(24,4,'نقل ملفات المستخدمين','نقل المرفقات مع الحفاظ على الروابط القديمة.','low','todo','2026-09-15','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(25,4,'Decommission the old server','After thirty days with no traffic.','low','todo','2026-09-30','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(26,5,'Necessitatibus dicta quasi reprehenderit.','Ut quis natus nesciunt officiis ut maxime ad. Facilis et et quaerat iste quisquam animi tempora. Aut quidem dolore unde quasi illo excepturi voluptas est. Labore corporis vero voluptate et aspernatur voluptate.','medium','todo','2026-08-26','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(27,5,'Quas non quia.','Culpa mollitia molestias tempora voluptatem amet est ipsam. Aperiam cumque dolor hic ut sit. Aliquam repellat dolorem porro incidunt numquam aperiam. Eaque qui officia hic esse quaerat deserunt tenetur est.','medium','todo','2026-07-30','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL),(28,5,'Aut mollitia quia.','Quae vero nisi nulla optio quo. Tenetur et culpa at ipsam ratione tempora incidunt minus. Qui deleniti omnis consectetur nostrum. Cupiditate dolorem qui voluptatibus.','medium','todo','2026-08-25','2026-08-01 13:50:00','2026-08-01 13:50:00',NULL,NULL);
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1,'Demo Reviewer','demo@example.com',NULL,'$2y$12$KHlbWRXG7oKLrNudSshOuuM1vva1VvCSlAFKS9jlYw10NvlNJoNdW',NULL,'2026-08-01 13:50:00','2026-08-01 13:50:00'),(2,'Nour Hassan','other@example.com',NULL,'$2y$12$gr3Ei47SCso5iZvDhr3lD.mBKJebVnqMu3HDVa8r.cDDm70kngBGm',NULL,'2026-08-01 13:50:00','2026-08-01 13:50:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
