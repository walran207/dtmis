-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 03:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `denr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_scope` enum('CUSTODY','ACTION') NOT NULL DEFAULT 'ACTION',
  `destination_office_id` int(11) DEFAULT NULL,
  `destination_user_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_visible_on_slip` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `document_id`, `user_id`, `action_type`, `action_scope`, `destination_office_id`, `destination_user_id`, `remarks`, `is_visible_on_slip`, `created_at`) VALUES
(1, 1, 78, 'Created', 'ACTION', NULL, NULL, 'This submission should succeed even with an empty file slot, as we now allow remarks-only intake.', 0, '2026-04-06 11:33:34'),
(2, 2, 10, 'Created', 'ACTION', NULL, NULL, 'Initial intake from CENRO Banga.', 0, '2026-04-06 14:02:39'),
(3, 3, 10, 'Created', 'ACTION', NULL, NULL, 'TEST-VALIDATION-WORKFLOW-VALIDATION-DO-NOT-DELETE', 0, '2026-04-06 14:18:48'),
(4, 3, 10, 'Forwarded', 'ACTION', 4, NULL, 'Forwarded to next office.', 0, '2026-04-06 14:24:06'),
(5, 3, 3, 'Received', 'CUSTODY', 4, NULL, 'Document officially received.', 1, '2026-04-06 14:25:45'),
(6, 3, 3, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 14:26:39'),
(7, 3, 3, 'Forwarded', 'ACTION', 2, NULL, 'Forwarded to next office.', 0, '2026-04-06 14:27:22'),
(8, 3, 2, 'Received', 'CUSTODY', 2, NULL, 'Received via quick tracking input.', 1, '2026-04-06 14:31:09'),
(9, 3, 2, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 14:31:58'),
(10, 3, 2, 'Forwarded', 'ACTION', 1, NULL, 'Forwarded to next office.', 0, '2026-04-06 14:32:50'),
(11, 3, 1, 'Received', 'CUSTODY', 1, NULL, 'Document officially received.', 1, '2026-04-06 14:35:43'),
(12, 3, 1, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 14:36:10'),
(13, 3, 1, 'Forwarded', 'ACTION', 49, NULL, 'ORED instruction: assigned to concerned ARD.', 0, '2026-04-06 14:38:34'),
(14, 3, 74, 'Received', 'CUSTODY', 49, NULL, 'Document officially received.', 1, '2026-04-06 16:24:53'),
(15, 3, 74, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 16:25:21'),
(16, 3, 74, 'Forwarded', 'ACTION', 9, NULL, 'Forwarded for processing.', 0, '2026-04-06 16:26:18'),
(17, 3, 27, 'Received', 'CUSTODY', 9, NULL, 'Document officially received.', 1, '2026-04-06 16:28:13'),
(18, 3, 27, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 16:28:42'),
(19, 3, 27, 'Forwarded', 'ACTION', 16, NULL, 'Forwarded to next office.', 0, '2026-04-06 16:30:10'),
(20, 3, 49, 'Received', 'CUSTODY', 16, NULL, 'Document officially received.', 1, '2026-04-06 16:32:27'),
(21, 3, 49, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 16:32:55'),
(22, 4, 10, 'Created', 'ACTION', NULL, NULL, 'Testing offline sync functionality. No soft copy attached for this test.', 0, '2026-04-06 16:44:32'),
(23, 5, 10, 'Created', 'ACTION', NULL, NULL, 'Test offline role A', 0, '2026-04-06 17:10:15'),
(24, 6, 11, 'Created', 'ACTION', NULL, NULL, 'Test offline role B sync', 0, '2026-04-06 17:11:38'),
(25, 7, 3, 'Created', 'ACTION', NULL, NULL, 'Standard Intake for Stale Data Test. No file attached, remarks provided.', 0, '2026-04-06 17:29:15'),
(26, 8, 3, 'Created', 'ACTION', NULL, NULL, 'Action required: For Appropriate Action | Test for concurrency conflict', 0, '2026-04-06 17:51:08'),
(27, 9, 2, 'Created', 'ACTION', NULL, NULL, 'CONFLICT-TEST-EXTERNAL', 0, '2026-04-06 18:18:46'),
(28, 5, 10, 'Forwarded', 'ACTION', 4, NULL, 'Forwarded to next office.', 0, '2026-04-06 18:25:41'),
(29, 5, 3, 'Received', 'CUSTODY', 4, NULL, 'Document officially received.', 1, '2026-04-06 18:26:24'),
(30, 5, 3, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 18:26:29'),
(31, 5, 3, 'Forwarded', 'ACTION', 2, NULL, 'Forwarded to next office.', 0, '2026-04-06 18:26:37'),
(32, 5, 2, 'Received', 'CUSTODY', 2, NULL, 'Document officially received.', 1, '2026-04-06 18:27:18'),
(33, 5, 2, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 18:28:42'),
(34, 5, 2, 'Forwarded', 'ACTION', 1, NULL, 'Forwarded to next office.', 0, '2026-04-06 18:28:48'),
(35, 5, 1, 'Received', 'CUSTODY', 1, NULL, 'Document officially received.', 1, '2026-04-06 18:31:56'),
(36, 5, 1, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 18:32:01'),
(37, 5, 1, 'Forwarded', 'ACTION', 49, NULL, 'ORED instruction: assigned to concerned ARD.', 0, '2026-04-06 18:32:30'),
(38, 5, 74, 'Received', 'CUSTODY', 49, NULL, 'Document officially received.', 1, '2026-04-06 18:50:41'),
(39, 5, 74, 'Approved', 'ACTION', NULL, NULL, 'Document approved.', 0, '2026-04-06 18:50:47'),
(40, 5, 74, 'Forwarded', 'ACTION', 9, NULL, 'ARD instruction: assigned to concerned division.', 0, '2026-04-06 18:51:00');

-- --------------------------------------------------------

--
-- Table structure for table `api_idempotency_operations`
--

CREATE TABLE `api_idempotency_operations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_key` varchar(64) NOT NULL,
  `operation_id` varchar(80) NOT NULL,
  `request_hash` char(64) NOT NULL,
  `status` enum('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
  `document_id` int(11) DEFAULT NULL,
  `tracking_id` varchar(64) DEFAULT NULL,
  `attachment_count` int(11) DEFAULT NULL,
  `response_code` smallint(5) UNSIGNED DEFAULT NULL,
  `response_json` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `api_idempotency_operations`
--

INSERT INTO `api_idempotency_operations` (`id`, `user_id`, `action_key`, `operation_id`, `request_hash`, `status`, `document_id`, `tracking_id`, `attachment_count`, `response_code`, `response_json`, `created_at`, `updated_at`) VALUES
(1, 78, 'create_document_intake', 'a70b46fc-c7bd-44eb-b666-125a185caafc', '7b3b647d8c7cef86f6b44e803204734c5c9326d92ca197105939443f8c1697cb', 'COMPLETED', 1, 'DENR-XII-2026-0001', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":1,\"tracking_id\":\"DENR-XII-2026-0001\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0001\",\"operation_id\":\"a70b46fc-c7bd-44eb-b666-125a185caafc\"}', '2026-04-06 11:33:34', '2026-04-06 11:33:34'),
(2, 10, 'create_document_intake', '52c51eeb-c8b9-46e7-bc48-1a1a86cf7b71', '3f08f2e2b10e1283e339b02404971f07b240fd450a2e690475595b9d6cb43782', 'COMPLETED', 2, 'DENR-XII-2026-0002', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":2,\"tracking_id\":\"DENR-XII-2026-0002\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0002\",\"operation_id\":\"52c51eeb-c8b9-46e7-bc48-1a1a86cf7b71\"}', '2026-04-06 14:02:39', '2026-04-06 14:02:39'),
(3, 10, 'create_document_intake', '2806476c-dbf2-429e-b0e0-f3ca8b70a77c', '904e24d6b1bdd606cea174e2634f7c5d0abe828475ba8bba2802a178b9a63c0a', 'COMPLETED', 3, 'DENR-XII-2026-0003', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":3,\"tracking_id\":\"DENR-XII-2026-0003\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0003\",\"operation_id\":\"2806476c-dbf2-429e-b0e0-f3ca8b70a77c\"}', '2026-04-06 14:18:47', '2026-04-06 14:18:48'),
(4, 10, 'document_action_workflow', 'b03a76ee-db3b-46a8-9428-5f38ab22c79a', 'f6ae3064d6907aeb93cb26cde2241eff8339817b0572811bd7210b2ba57d09cc', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":2,\"operation_id\":\"b03a76ee-db3b-46a8-9428-5f38ab22c79a\"}', '2026-04-06 14:24:06', '2026-04-06 14:24:06'),
(5, 3, 'document_action_workflow', '00b7a9f4-dfcc-4c44-8f76-e90b1afdc9e5', 'cde2fd2147be3b0004fffdcec3b2a060d09122f360eef7d380c064e37dc6a8ab', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":3,\"operation_id\":\"00b7a9f4-dfcc-4c44-8f76-e90b1afdc9e5\"}', '2026-04-06 14:25:45', '2026-04-06 14:25:45'),
(6, 3, 'document_action_workflow', '685c281f-67c8-41b6-aa9c-09ffba7ed239', '25f3aca954a0e2dd1fb9360f7434621ca86c9250bc3f4ad6f8a7488e35a4baf1', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":4,\"operation_id\":\"685c281f-67c8-41b6-aa9c-09ffba7ed239\"}', '2026-04-06 14:26:39', '2026-04-06 14:26:39'),
(7, 3, 'document_action_workflow', 'd4c6f843-066b-4917-bb79-f3d5725528a6', 'a95e4b71e02335feefe15cd67031f7e04be1a3387deb397480cb77286f403f2e', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":5,\"operation_id\":\"d4c6f843-066b-4917-bb79-f3d5725528a6\"}', '2026-04-06 14:27:22', '2026-04-06 14:27:22'),
(8, 2, 'document_action_workflow', '8949f4b3-fc21-48fe-ba26-1315370525ba', '055855fea965d86ad7fe29f1f68d9705345ee9d9dffb3b380a2cdd1ed59e59be', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":6,\"operation_id\":\"8949f4b3-fc21-48fe-ba26-1315370525ba\"}', '2026-04-06 14:31:09', '2026-04-06 14:31:09'),
(9, 2, 'document_action_workflow', '06358019-565d-4f06-96fb-04e756f9cf7d', '3aed10349ed39112d1095c7fb25130ebb84ac494c148b647df9375bd9f1fca0e', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":7,\"operation_id\":\"06358019-565d-4f06-96fb-04e756f9cf7d\"}', '2026-04-06 14:31:58', '2026-04-06 14:31:58'),
(10, 2, 'document_action_workflow', 'ff003425-6278-4bdb-afea-d5de94090679', '273a05d2f476fd17e7773755efc365bea03bfb9839b07004416fd583e2b0aba5', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":8,\"operation_id\":\"ff003425-6278-4bdb-afea-d5de94090679\"}', '2026-04-06 14:32:50', '2026-04-06 14:32:50'),
(11, 1, 'document_action_workflow', '8b4a12f2-60e9-4ca8-8bb3-f5767f7b155b', '785b1cdd7caa5a53110cd0b335b28c22af5b5b6ac05afdf5c2e08f311b1ecc76', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":9,\"operation_id\":\"8b4a12f2-60e9-4ca8-8bb3-f5767f7b155b\"}', '2026-04-06 14:35:42', '2026-04-06 14:35:43'),
(12, 1, 'document_action_workflow', '2b41da09-e6f2-4cbb-b935-9c88b3f603b4', '5ab69f3ca36c310295ea4ef1c7541a91f166fbdae82071d49e0ea69bfe821757', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":10,\"operation_id\":\"2b41da09-e6f2-4cbb-b935-9c88b3f603b4\"}', '2026-04-06 14:36:10', '2026-04-06 14:36:10'),
(13, 1, 'document_action_workflow', '09557ba3-9c45-40a2-be3d-22bea7573a1e', '047adce1d35e5a71dab1ca8dc2eadeef87316ce9f5aec83ca99563d83b7575d3', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":11,\"operation_id\":\"09557ba3-9c45-40a2-be3d-22bea7573a1e\"}', '2026-04-06 14:38:34', '2026-04-06 14:38:34'),
(14, 74, 'document_action_workflow', '8cc0124d-7e6f-4769-99c1-aec1d2cc2abf', 'cb53e6b29697a0d3e2d65e048dd49c1f31711256114dac4a550af58fa28c65b9', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":12,\"operation_id\":\"8cc0124d-7e6f-4769-99c1-aec1d2cc2abf\"}', '2026-04-06 16:24:53', '2026-04-06 16:24:53'),
(15, 74, 'document_action_workflow', '9a09c82a-2fae-450a-9c1c-9385d2037f96', 'a0326d81038c84da48febd3509fd444bed745564aea64c9ac60aec5d378366ce', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":13,\"operation_id\":\"9a09c82a-2fae-450a-9c1c-9385d2037f96\"}', '2026-04-06 16:25:21', '2026-04-06 16:25:21'),
(16, 74, 'document_action_workflow', 'a39c7a7c-0a15-4b0c-918f-bd2bcd0060ed', '6dcfeb3b3abb83b7da439e833f731db552bd7bb483778474796e26bc2e964cff', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":14,\"operation_id\":\"a39c7a7c-0a15-4b0c-918f-bd2bcd0060ed\"}', '2026-04-06 16:26:18', '2026-04-06 16:26:18'),
(17, 27, 'document_action_workflow', '4d3ddbf2-5f12-41ee-b3d3-962bc110f418', '7fa5f2239f74cf0fb7764e8dd44d7810534d72ae272a6966c8553ca2b77783de', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":15,\"operation_id\":\"4d3ddbf2-5f12-41ee-b3d3-962bc110f418\"}', '2026-04-06 16:28:13', '2026-04-06 16:28:13'),
(18, 27, 'document_action_workflow', '6d67cd53-fdbf-45cc-825d-c6b2f731c69a', '78430b5eceb68ea843488f3fc94050a44d07eea5be573537b803d784a6fa71c4', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":16,\"operation_id\":\"6d67cd53-fdbf-45cc-825d-c6b2f731c69a\"}', '2026-04-06 16:28:42', '2026-04-06 16:28:42'),
(19, 27, 'document_action_workflow', 'a13eaa5a-6a83-407b-ac49-88546203be44', '65fcd0350d069486c54e2ab3a3f9a45030b50bc0c829642c8a9234c671e2ddf2', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":17,\"operation_id\":\"a13eaa5a-6a83-407b-ac49-88546203be44\"}', '2026-04-06 16:30:10', '2026-04-06 16:30:10'),
(20, 49, 'document_action_workflow', 'bae91e73-3407-4f94-940f-424cd4595e75', 'd5c3ebfbfd6d363325cc51701e3c96014b41c3ca6113b92cd454e7ec300f7d83', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":18,\"operation_id\":\"bae91e73-3407-4f94-940f-424cd4595e75\"}', '2026-04-06 16:32:27', '2026-04-06 16:32:27'),
(21, 49, 'document_action_workflow', '512a67e8-7e49-4952-bb89-9a5bd33f5982', 'eeaea43d7b78f14ece53abd16c78df1640553b03f6ad9f77ee1527e69f594bbf', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":19,\"operation_id\":\"512a67e8-7e49-4952-bb89-9a5bd33f5982\"}', '2026-04-06 16:32:55', '2026-04-06 16:32:55'),
(22, 10, 'create_document_intake', '89ef3df5-656a-4c8a-8246-1c155b747ba7', '5c4e97b7bd3eb310e755063dc8c77ce0268af8564cffbc54bd00279dcc301774', 'COMPLETED', 4, 'DENR-XII-2026-0004', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":4,\"tracking_id\":\"DENR-XII-2026-0004\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0004\",\"operation_id\":\"89ef3df5-656a-4c8a-8246-1c155b747ba7\"}', '2026-04-06 16:44:32', '2026-04-06 16:44:32'),
(23, 10, 'create_document_intake', '7b4a6e17-60bb-4e21-aafa-5d16859d4cbf', '0f68d6347ec7a23bd71ceffcfafe929bf68e0eb8b27f4c4162028ab4d63b6cbc', 'COMPLETED', 5, 'DENR-XII-2026-0005', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":5,\"tracking_id\":\"DENR-XII-2026-0005\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0005\",\"operation_id\":\"7b4a6e17-60bb-4e21-aafa-5d16859d4cbf\"}', '2026-04-06 17:10:15', '2026-04-06 17:10:15'),
(24, 11, 'create_document_intake', '0055fee5-3b24-4e0e-998c-e59527faca6b', '1a9e5569ea14b63ce9754a984cf96ac38327a754bb174d1291c6c15bea7b9145', 'COMPLETED', 6, 'DENR-XII-2026-0006', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":6,\"tracking_id\":\"DENR-XII-2026-0006\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0006\",\"operation_id\":\"0055fee5-3b24-4e0e-998c-e59527faca6b\"}', '2026-04-06 17:11:38', '2026-04-06 17:11:38'),
(25, 3, 'create_document_intake', '72ebd9cf-4f08-4ad1-b3d1-6f7c0e52aa4d', '54e9a3a26a1ec8ccd3cec61f8106a052e494e03a230135605c613d0e6b10b8fc', 'COMPLETED', 7, 'DENR-XII-2026-0007', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":7,\"tracking_id\":\"DENR-XII-2026-0007\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0007\",\"operation_id\":\"72ebd9cf-4f08-4ad1-b3d1-6f7c0e52aa4d\"}', '2026-04-06 17:29:15', '2026-04-06 17:29:15'),
(26, 2, 'document_action_workflow', '5a247ed3-27e9-4257-a988-eb91ca18bedf', '18192e4f0e4544ffb8a30c0b2e00de42a25cad0cba7483920b02f9f2c5b761eb', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"5a247ed3-27e9-4257-a988-eb91ca18bedf\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 17:37:02', '2026-04-06 17:37:02'),
(27, 3, 'document_action_workflow', 'b4dfe209-0190-469c-8921-12966336e6ce', '18192e4f0e4544ffb8a30c0b2e00de42a25cad0cba7483920b02f9f2c5b761eb', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"b4dfe209-0190-469c-8921-12966336e6ce\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 17:45:49', '2026-04-06 17:45:49'),
(28, 3, 'create_document_intake', 'b212cedd-f8df-4a1b-917e-df0e7a3b3495', 'b799c4763bfb79a5ce9fe03ec17e70f9a9270847ebeb0d02057719b08b3a6686', 'COMPLETED', 8, 'DENR-XII-2026-0008', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":8,\"tracking_id\":\"DENR-XII-2026-0008\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0008\",\"operation_id\":\"b212cedd-f8df-4a1b-917e-df0e7a3b3495\"}', '2026-04-06 17:51:08', '2026-04-06 17:51:08'),
(29, 3, 'document_action_workflow', '66a3e35b-f379-44c7-8ec3-01dc2d2ff25f', 'e1dd2d919c7a7b22fa0ac25897d723f9b99bced1930b58862ab36a832be86f3a', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"66a3e35b-f379-44c7-8ec3-01dc2d2ff25f\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 17:56:21', '2026-04-06 17:56:21'),
(30, 3, 'document_action_workflow', '9557df5f-21e1-48e2-a17e-bbcca261ed59', 'd0469d9ba54183a55631eeef042027488331a0d6b78b4ec566e3fafd42a667d2', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"9557df5f-21e1-48e2-a17e-bbcca261ed59\",\"message\":\"Document must be received before approval.\"}', '2026-04-06 18:04:16', '2026-04-06 18:04:16'),
(31, 3, 'document_action_workflow', '236c16e6-9803-4791-b7a9-cfdfaaf82a01', 'd0469d9ba54183a55631eeef042027488331a0d6b78b4ec566e3fafd42a667d2', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"236c16e6-9803-4791-b7a9-cfdfaaf82a01\",\"message\":\"Document must be received before approval.\"}', '2026-04-06 18:07:33', '2026-04-06 18:07:33'),
(32, 3, 'document_action_workflow', 'cca11598-df15-4d7c-8757-b4f2c1ea9e8a', '1852cec841d978d743e1a0e1329bf33012a6f1363a8649a1c2ec1d561db9b414', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"cca11598-df15-4d7c-8757-b4f2c1ea9e8a\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 18:07:58', '2026-04-06 18:07:58'),
(33, 2, 'document_action_workflow', '9b27f591-4f84-464a-a04d-5ed7d311af48', 'e1dd2d919c7a7b22fa0ac25897d723f9b99bced1930b58862ab36a832be86f3a', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"9b27f591-4f84-464a-a04d-5ed7d311af48\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 18:16:17', '2026-04-06 18:16:17'),
(34, 2, 'document_action_workflow', 'a146a2e7-1eec-4b83-a00d-a19e873a6ac6', '58bd986fcd80aa6b9631beec52c2eaa8bf64146899cbb352f89e5902a6ef198a', 'FAILED', NULL, NULL, NULL, 403, '{\"ok\":false,\"operation_id\":\"a146a2e7-1eec-4b83-a00d-a19e873a6ac6\",\"message\":\"Document is not awaiting receive.\"}', '2026-04-06 18:17:18', '2026-04-06 18:17:18'),
(35, 2, 'create_document_intake', '684ecaef-1405-40c6-8a7e-643f9eb522b0', '009634d0f20940ae93aaa620d32cf9a6e3345d7c83c9794af19c5548f9b90505', 'COMPLETED', 9, 'DENR-XII-2026-0009', 0, 200, '{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":9,\"tracking_id\":\"DENR-XII-2026-0009\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0009\",\"operation_id\":\"684ecaef-1405-40c6-8a7e-643f9eb522b0\"}', '2026-04-06 18:18:46', '2026-04-06 18:18:46'),
(36, 10, 'document_action_workflow', '78765066-9196-4c48-9be7-32d351429289', '0e82b1ff7f5f0554a8368685b40b8610e9110bfff3f070e62bdf272849c43477', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":2,\"operation_id\":\"78765066-9196-4c48-9be7-32d351429289\"}', '2026-04-06 18:25:41', '2026-04-06 18:25:41'),
(37, 3, 'document_action_workflow', 'dc769103-9c08-42db-8730-4379da7f5e8a', '8eb1398cf0379c4c284d22d3e0f351c2d93b133c1ae1fb3f62c2c2955ec3103d', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":3,\"operation_id\":\"dc769103-9c08-42db-8730-4379da7f5e8a\"}', '2026-04-06 18:26:24', '2026-04-06 18:26:24'),
(38, 3, 'document_action_workflow', '6e8f53f5-1807-4265-8e10-91b78278dfb2', '0fe791168042dfd14053c52f47d8a1466ec14ed02d1967a928f3281213e91604', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":4,\"operation_id\":\"6e8f53f5-1807-4265-8e10-91b78278dfb2\"}', '2026-04-06 18:26:29', '2026-04-06 18:26:29'),
(39, 3, 'document_action_workflow', 'cc45383c-02e7-4ca9-82fa-ed9d12ffadd6', '7060c34fd67c31d0830adecbb13611c57d268299f6eecd9a135619f3d46cb32d', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":5,\"operation_id\":\"cc45383c-02e7-4ca9-82fa-ed9d12ffadd6\"}', '2026-04-06 18:26:37', '2026-04-06 18:26:37'),
(40, 2, 'document_action_workflow', 'dedd345a-17d4-4bad-b08c-485b976b0f85', 'b02436cecd81bb8e8f8932dbfdac94889b0e87083ea55ba4068a49a63c4bcd96', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":6,\"operation_id\":\"dedd345a-17d4-4bad-b08c-485b976b0f85\"}', '2026-04-06 18:27:18', '2026-04-06 18:27:18'),
(41, 2, 'document_action_workflow', '3398361c-8872-426b-b4eb-19f76df58962', '15d5c78d40886a8cc259d3a4057f19a7e484cbd684196c5da0f0a9e95dbf6ba4', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":7,\"operation_id\":\"3398361c-8872-426b-b4eb-19f76df58962\"}', '2026-04-06 18:28:42', '2026-04-06 18:28:42'),
(42, 2, 'document_action_workflow', 'c2bbc0cc-3fd1-415d-a605-f23a2df0aa44', '9895b65ed7a2286a87a8f9cf635547234683154dbd08ec444927c30500ecade7', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":8,\"operation_id\":\"c2bbc0cc-3fd1-415d-a605-f23a2df0aa44\"}', '2026-04-06 18:28:48', '2026-04-06 18:28:48'),
(43, 1, 'document_action_workflow', '03894b5b-85a6-4b1f-83fd-d994812da6a6', 'b7d7a4f707245ff0f4a73a1567122c0232b4ab3606827edcf09899eef8c86f29', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":9,\"operation_id\":\"03894b5b-85a6-4b1f-83fd-d994812da6a6\"}', '2026-04-06 18:31:56', '2026-04-06 18:31:56'),
(44, 1, 'document_action_workflow', 'ba71f9b6-a6c7-447c-9f04-89a716cc7c86', '2c571db532a02c9fea22c28aaacc0355b422201e30fb5ddf4725b8910562684a', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":10,\"operation_id\":\"ba71f9b6-a6c7-447c-9f04-89a716cc7c86\"}', '2026-04-06 18:32:01', '2026-04-06 18:32:01'),
(45, 1, 'document_action_workflow', 'd6674de2-03f7-4f90-abee-7c97bc1a4156', '504dd23c53a18fcccc56275d15643ed19f55137bfa31f9eef01ee65c1089f7cb', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":11,\"operation_id\":\"d6674de2-03f7-4f90-abee-7c97bc1a4156\"}', '2026-04-06 18:32:30', '2026-04-06 18:32:30'),
(46, 74, 'document_action_workflow', 'e2132879-1cdf-4cb7-84a0-517c75f0746f', '92805f78faeb8e238e0928d72ab76eff6ae5371ba2db64a0166fcd4793ed35bd', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":12,\"operation_id\":\"e2132879-1cdf-4cb7-84a0-517c75f0746f\"}', '2026-04-06 18:50:41', '2026-04-06 18:50:41'),
(47, 74, 'document_action_workflow', '44cc8802-ac1b-43dd-8770-ac426586411a', '806816cd0325401707c5dd1c5f24be33b62801541c83a94d44334bb91a910ac6', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":13,\"operation_id\":\"44cc8802-ac1b-43dd-8770-ac426586411a\"}', '2026-04-06 18:50:47', '2026-04-06 18:50:47'),
(48, 74, 'document_action_workflow', '512bb38d-b878-4e3f-a816-d5bb7b054bd3', '4657fb1c3ae3a15a6adb33242734937d32a0aebbb80f42ea02748dfa8c467a53', 'COMPLETED', NULL, NULL, NULL, 200, '{\"ok\":true,\"message\":\"Action completed.\",\"current_version\":14,\"operation_id\":\"512bb38d-b878-4e3f-a816-d5bb7b054bd3\"}', '2026-04-06 18:51:00', '2026-04-06 18:51:00');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `tracking_id` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `arta_category_override` varchar(50) DEFAULT NULL,
  `arta_days_limit_override` int(11) DEFAULT NULL,
  `originating_office_id` int(11) NOT NULL,
  `current_office_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `source_type` enum('INTERNAL','EXTERNAL') NOT NULL DEFAULT 'INTERNAL',
  `external_client_name` varchar(255) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `current_holder_user_id` int(11) DEFAULT NULL,
  `pending_office_id` int(11) DEFAULT NULL,
  `pending_user_id` int(11) DEFAULT NULL,
  `row_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `qr_print_mode` varchar(20) DEFAULT 'Grid-Snap',
  `qr_x_coordinate` decimal(10,2) DEFAULT NULL,
  `qr_y_coordinate` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `tracking_id`, `subject`, `document_type_id`, `arta_category_override`, `arta_days_limit_override`, `originating_office_id`, `current_office_id`, `status`, `source_type`, `external_client_name`, `created_by_user_id`, `current_holder_user_id`, `pending_office_id`, `pending_user_id`, `row_version`, `qr_print_mode`, `qr_x_coordinate`, `qr_y_coordinate`, `created_at`) VALUES
(1, 'DENR-XII-2026-0001', 'Verification of Intake Fix', 1, 'Simple', 3, 5, 5, 'Created', 'INTERNAL', NULL, 78, 78, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 11:33:34'),
(2, 'DENR-XII-2026-0002', 'Test Cross-Role Routing CENRO to PENRO', 8, 'Complex', 7, 5, 5, 'Created', 'INTERNAL', NULL, 10, 10, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 14:02:39'),
(3, 'DENR-XII-2026-0003', 'TEST-WORKFLOW-VALIDATION-DO-NOT-DELETE', 8, 'Simple', 3, 5, 16, 'Approved', 'INTERNAL', NULL, 10, 49, NULL, NULL, 19, 'Grid-Snap', NULL, NULL, '2026-04-06 14:18:48'),
(4, 'DENR-XII-2026-0004', 'OFFLINE-SYNC-TEST-DO-NOT-DELETE', 8, 'Simple', 3, 5, 5, 'Created', 'INTERNAL', NULL, 10, 10, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 16:44:32'),
(5, 'DENR-XII-2026-0005', 'OFFLINE-ROLE-A-BANGA', 8, 'Simple', 3, 5, 49, 'Assigned to Licenses, Patents & Deeds Division', 'INTERNAL', NULL, 10, 74, 9, NULL, 14, 'Grid-Snap', NULL, NULL, '2026-04-06 17:10:15'),
(6, 'DENR-XII-2026-0006', 'OFFLINE-ROLE-B-GLAN', 8, 'Simple', 3, 7, 7, 'Created', 'INTERNAL', NULL, 11, 11, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 17:11:38'),
(7, 'DENR-XII-2026-0007', 'STALE-DATA-SOURCE', 8, 'Simple', 3, 4, 4, 'Created', 'INTERNAL', NULL, 3, 3, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 17:29:15'),
(8, 'DENR-XII-2026-0008', 'CONCURRENCY-CONFLICT-TEST', 6, 'Simple', 3, 4, 4, 'Created', 'INTERNAL', NULL, 3, 3, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 17:51:08'),
(9, 'DENR-XII-2026-0009', 'EXTERNAL-CONFLICT-TEST', 8, 'Simple', 3, 2, 2, 'Created', 'EXTERNAL', 'DENR-COMMUNITY', 2, 2, NULL, NULL, 1, 'Grid-Snap', NULL, NULL, '2026-04-06 18:18:46');

-- --------------------------------------------------------

--
-- Table structure for table `document_attachments`
--

CREATE TABLE `document_attachments` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `version_number` int(11) DEFAULT 1,
  `is_internal_only` tinyint(1) DEFAULT 0,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `arta_days_limit` int(11) NOT NULL,
  `indicator_color` varchar(20) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `category`, `arta_days_limit`, `indicator_color`, `is_custom`, `created_by_role_id`, `is_active`) VALUES
(1, 'Tree Cutting Permit', 'Simple', 3, 'Yellow', 0, NULL, 1),
(2, 'Free Patent Application', 'Complex', 7, 'Pink', 0, NULL, 1),
(3, 'Environmental Compliance Certificate (ECC)', 'Highly Technical', 20, 'Red', 0, NULL, 1),
(4, 'Walk-in External Client Query', 'Simple', 3, 'Yellow', 0, NULL, 1),
(5, 'PACDO Custom Client Request', 'Simple', 3, 'Yellow', 1, 2, 1),
(6, 'Memorandum', 'Simple', 3, 'Yellow', 0, NULL, 1),
(7, 'Endorsement Letter', 'Simple', 3, 'Yellow', 0, NULL, 1),
(8, 'Certification Request', 'Simple', 3, 'Yellow', 0, NULL, 1),
(9, 'Records Request', 'Simple', 3, 'Yellow', 0, NULL, 1),
(10, 'Permit Application', 'Complex', 7, 'Pink', 0, NULL, 1),
(11, 'Permit Renewal', 'Complex', 7, 'Pink', 0, NULL, 1),
(12, 'Compliance Report', 'Complex', 7, 'Pink', 0, NULL, 1),
(13, 'Inspection Report', 'Complex', 7, 'Pink', 0, NULL, 1),
(14, 'Legal Opinion Request', 'Complex', 7, 'Pink', 0, NULL, 1),
(15, 'Notice of Violation', 'Complex', 7, 'Pink', 0, NULL, 1),
(16, 'Site Validation Report', 'Complex', 7, 'Pink', 0, NULL, 1),
(17, 'GIS Accomplishment Report', 'Complex', 7, 'Pink', 0, NULL, 1),
(18, 'ECC Application', 'Highly Technical', 20, 'Red', 0, NULL, 1),
(19, 'Foreshore Lease Evaluation', 'Highly Technical', 20, 'Red', 0, NULL, 1),
(20, 'Wildlife Special Permit', 'Highly Technical', 20, 'Red', 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `document_type_requests`
--

CREATE TABLE `document_type_requests` (
  `id` int(11) NOT NULL,
  `requested_name` varchar(100) NOT NULL,
  `requested_category` varchar(50) NOT NULL,
  `requested_days` int(11) NOT NULL,
  `requested_color` varchar(20) NOT NULL,
  `justification` text DEFAULT NULL,
  `requested_by_user_id` int(11) NOT NULL,
  `requested_by_office_id` int(11) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `linked_document_type_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `parent_office_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `level`, `parent_office_id`) VALUES
(1, 'Office of the Regional Executive Director (ORED)', 'Regional', NULL),
(2, 'Public Affairs and Communication Development Office (PACDO)', 'Regional', 1),
(3, 'Planning and Management Division (PMD)', 'Division', 50),
(4, 'PENRO SOUTH COTABATO', 'Provincial', 1),
(5, 'CENRO Banga', 'Community', 4),
(6, 'PENRO SARANGANI', 'Provincial', 1),
(7, 'CENRO Glan', 'Community', 6),
(8, 'PASU - Mt. Matutum Protected Landscape', 'Protected Area', 4),
(9, 'Licenses, Patents & Deeds Division', 'Division', 49),
(10, 'Surveys & Mapping Division', 'Division', 49),
(11, 'Conservation & Devt. Division', 'Division', 49),
(12, 'Enforcement Division', 'Division', 49),
(13, 'Legal Division', 'Division', 50),
(14, 'Administrative Division', 'Division', 50),
(15, 'Finance Division', 'Division', 50),
(16, 'Forest Utilization', 'Section', 9),
(17, 'Wildlife Resource Permitting', 'Section', 9),
(18, 'Water Resources Utilization', 'Section', 9),
(19, 'Patents and Deeds', 'Section', 9),
(20, 'Surveys & Control', 'Section', 10),
(21, 'Land Evaluation Surveys', 'Section', 10),
(22, 'Agretacion Surveys & Corrections', 'Section', 10),
(23, 'Original & Other Surveys', 'Section', 10),
(24, 'Land Records', 'Section', 10),
(25, 'PA Management & Biodiversity Conservation', 'Section', 11),
(26, 'Production Forest Management', 'Section', 11),
(27, 'Coastal Resources & Foreshore Management', 'Section', 11),
(28, 'Surveillance & Intelligence', 'Section', 12),
(29, 'Compliance, Monitoring & Investigation', 'Section', 12),
(30, 'Planning & Programming', 'Section', 3),
(31, 'Monitoring & Evaluation', 'Section', 3),
(32, 'Regional ICT Unit', 'Section', 3),
(33, 'Personnel', 'Section', 14),
(34, 'HRDM (Human Resource Development Management)', 'Section', 14),
(35, 'Procurement', 'Section', 14),
(36, 'General Services', 'Section', 14),
(37, 'Records', 'Section', 14),
(38, 'Cash', 'Section', 15),
(39, 'Accounting', 'Section', 15),
(40, 'Budget', 'Section', 15),
(41, 'PENRO COTABATO', 'Provincial', 1),
(42, 'PENRO SULTAN KUDARAT', 'Provincial', 1),
(43, 'CENRO Midyap', 'Community', 41),
(44, 'CENRO Matalam', 'Community', 41),
(45, 'CENRO Tacurong City', 'Community', 42),
(46, 'CENRO Kalamansig', 'Community', 42),
(47, 'CENRO General Santos City', 'Community', 4),
(48, 'CENRO Kiamba', 'Community', 6),
(49, 'Assistant Regional Director for Technical Services (ARD TS)', 'Regional', 1),
(50, 'Assistant Regional Director for Management Services (ARD MS)', 'Regional', 1);

-- --------------------------------------------------------

--
-- Table structure for table `offline_sync_logs`
--

CREATE TABLE `offline_sync_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `role_key` varchar(48) NOT NULL DEFAULT '',
  `event_type` varchar(64) NOT NULL,
  `route_kind` varchar(64) NOT NULL DEFAULT '',
  `action_name` varchar(64) NOT NULL DEFAULT '',
  `operation_id` varchar(80) NOT NULL DEFAULT '',
  `request_url` varchar(255) NOT NULL DEFAULT '',
  `http_status` smallint(5) UNSIGNED DEFAULT NULL,
  `attempt_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `queue_pending` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `queue_failed` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `queue_blocked` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` varchar(32) NOT NULL DEFAULT 'client',
  `message` varchar(255) NOT NULL DEFAULT '',
  `payload_json` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `offline_sync_logs`
--

INSERT INTO `offline_sync_logs` (`id`, `user_id`, `office_id`, `role_key`, `event_type`, `route_kind`, `action_name`, `operation_id`, `request_url`, `http_status`, `attempt_count`, `queue_pending`, `queue_failed`, `queue_blocked`, `source`, `message`, `payload_json`, `created_at`) VALUES
(1, 78, 5, 'CENRO', 'ACTION_SUCCESS', 'intake_create', '', 'a70b46fc-c7bd-44eb-b666-125a185caafc', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"document_id\":1,\"tracking_id\":\"DENR-XII-2026-0001\",\"attachment_count\":0}', '2026-04-06 11:33:34'),
(2, 10, 5, 'CENRO', 'ACTION_SUCCESS', 'intake_create', '', '52c51eeb-c8b9-46e7-bc48-1a1a86cf7b71', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"document_id\":2,\"tracking_id\":\"DENR-XII-2026-0002\",\"attachment_count\":0}', '2026-04-06 14:02:39'),
(3, 10, 5, 'CENRO', 'ACTION_SUCCESS', 'intake_create', '', '2806476c-dbf2-429e-b0e0-f3ca8b70a77c', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"document_id\":3,\"tracking_id\":\"DENR-XII-2026-0003\",\"attachment_count\":0}', '2026-04-06 14:18:48'),
(4, 10, 5, 'CENRO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'b03a76ee-db3b-46a8-9428-5f38ab22c79a', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"current_version\":2}', '2026-04-06 14:24:06'),
(5, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '00b7a9f4-dfcc-4c44-8f76-e90b1afdc9e5', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":3}', '2026-04-06 14:25:45'),
(6, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '685c281f-67c8-41b6-aa9c-09ffba7ed239', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":4}', '2026-04-06 14:26:39'),
(7, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'd4c6f843-066b-4917-bb79-f3d5725528a6', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":5}', '2026-04-06 14:27:22'),
(8, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '8949f4b3-fc21-48fe-ba26-1315370525ba', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":6}', '2026-04-06 14:31:09'),
(9, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '06358019-565d-4f06-96fb-04e756f9cf7d', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":7}', '2026-04-06 14:31:58'),
(10, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'ff003425-6278-4bdb-afea-d5de94090679', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":8}', '2026-04-06 14:32:50'),
(11, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '8b4a12f2-60e9-4ca8-8bb3-f5767f7b155b', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":9}', '2026-04-06 14:35:43'),
(12, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '2b41da09-e6f2-4cbb-b935-9c88b3f603b4', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":10}', '2026-04-06 14:36:10'),
(13, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'FORWARD', '09557ba3-9c45-40a2-be3d-22bea7573a1e', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":11}', '2026-04-06 14:38:34'),
(14, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '8cc0124d-7e6f-4769-99c1-aec1d2cc2abf', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":12}', '2026-04-06 16:24:53'),
(15, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '9a09c82a-2fae-450a-9c1c-9385d2037f96', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":13}', '2026-04-06 16:25:21'),
(16, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'a39c7a7c-0a15-4b0c-918f-bd2bcd0060ed', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":14}', '2026-04-06 16:26:18'),
(17, 27, 9, 'DIVISION_CHIEF', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '4d3ddbf2-5f12-41ee-b3d3-962bc110f418', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"DIVISION_CHIEF\",\"current_version\":15}', '2026-04-06 16:28:13'),
(18, 27, 9, 'DIVISION_CHIEF', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '6d67cd53-fdbf-45cc-825d-c6b2f731c69a', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"DIVISION_CHIEF\",\"current_version\":16}', '2026-04-06 16:28:42'),
(19, 27, 9, 'DIVISION_CHIEF', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'a13eaa5a-6a83-407b-ac49-88546203be44', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"DIVISION_CHIEF\",\"current_version\":17}', '2026-04-06 16:30:10'),
(20, 49, 16, 'SECTION_STAFF', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', 'bae91e73-3407-4f94-940f-424cd4595e75', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"SECTION_STAFF\",\"current_version\":18}', '2026-04-06 16:32:27'),
(21, 49, 16, 'SECTION_STAFF', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '512a67e8-7e49-4952-bb89-9a5bd33f5982', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"SECTION_STAFF\",\"current_version\":19}', '2026-04-06 16:32:55'),
(22, 10, 5, 'CENRO', 'QUEUED', 'intake_create', '', '89ef3df5-656a-4c8a-8246-1c155b747ba7', 'http://localhost/edats/actions/create-document.php', NULL, 0, 1, 0, 0, 'client-outbox', 'offline', '{\"online\":false,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\"}', '2026-04-06 16:43:58'),
(23, 10, 5, 'CENRO', 'SYNC_ATTEMPT', 'intake_create', '', '89ef3df5-656a-4c8a-8246-1c155b747ba7', 'http://localhost/edats/actions/create-document.php', NULL, 1, 0, 0, 0, 'client-outbox', '', '{\"online\":true,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\"}', '2026-04-06 16:44:32'),
(24, 10, 5, 'CENRO', 'SYNC_SUCCESS', 'intake_create', '', '89ef3df5-656a-4c8a-8246-1c155b747ba7', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":true,\"role_key\":\"CENRO\",\"document_id\":4,\"tracking_id\":\"DENR-XII-2026-0004\",\"attachment_count\":0}', '2026-04-06 16:44:32'),
(25, 10, 5, 'CENRO', 'SYNC_SUCCEEDED', 'intake_create', '', '89ef3df5-656a-4c8a-8246-1c155b747ba7', 'http://localhost/edats/actions/create-document.php', 200, 1, 0, 0, 0, 'client-outbox', '', '{\"online\":true,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\",\"response\":{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":4,\"tracking_id\":\"DENR-XII-2026-0004\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0004\",\"operation_id\":\"89ef3df5-656a-4c8a-8246-1c155b747ba7\"}}', '2026-04-06 16:44:32'),
(26, 10, 5, 'CENRO', 'QUEUED', 'intake_create', '', '7b4a6e17-60bb-4e21-aafa-5d16859d4cbf', 'http://localhost/edats/actions/create-document.php', NULL, 0, 1, 0, 0, 'client-outbox', 'offline', '{\"online\":false,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\"}', '2026-04-06 17:09:44'),
(27, 10, 5, 'CENRO', 'SYNC_ATTEMPT', 'intake_create', '', '7b4a6e17-60bb-4e21-aafa-5d16859d4cbf', 'http://localhost/edats/actions/create-document.php', NULL, 1, 0, 0, 0, 'client-outbox', '', '{\"online\":true,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\"}', '2026-04-06 17:10:15'),
(28, 10, 5, 'CENRO', 'SYNC_SUCCESS', 'intake_create', '', '7b4a6e17-60bb-4e21-aafa-5d16859d4cbf', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":true,\"role_key\":\"CENRO\",\"document_id\":5,\"tracking_id\":\"DENR-XII-2026-0005\",\"attachment_count\":0}', '2026-04-06 17:10:15'),
(29, 10, 5, 'CENRO', 'SYNC_SUCCEEDED', 'intake_create', '', '7b4a6e17-60bb-4e21-aafa-5d16859d4cbf', 'http://localhost/edats/actions/create-document.php', 200, 1, 0, 0, 0, 'client-outbox', '', '{\"online\":true,\"phase\":\"phase7\",\"rollout_stage\":\"pilot\",\"role_key\":\"CENRO\",\"response\":{\"ok\":true,\"message\":\"Document intake created successfully.\",\"document_id\":5,\"tracking_id\":\"DENR-XII-2026-0005\",\"attachment_count\":0,\"tracking_slip_url\":\"/edats/tracking-slip.php?tracking_id=DENR-XII-2026-0005\",\"operation_id\":\"7b4a6e17-60bb-4e21-aafa-5d16859d4cbf\"}}', '2026-04-06 17:10:15'),
(30, 11, 7, 'CENRO', 'ACTION_SUCCESS', 'intake_create', '', '0055fee5-3b24-4e0e-998c-e59527faca6b', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"document_id\":6,\"tracking_id\":\"DENR-XII-2026-0006\",\"attachment_count\":0}', '2026-04-06 17:11:38'),
(31, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'intake_create', '', '72ebd9cf-4f08-4ad1-b3d1-6f7c0e52aa4d', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"document_id\":7,\"tracking_id\":\"DENR-XII-2026-0007\",\"attachment_count\":0}', '2026-04-06 17:29:15'),
(32, 2, 2, 'PACDO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', '5a247ed3-27e9-4257-a988-eb91ca18bedf', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 17:37:02'),
(33, 3, 4, 'PENRO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', 'b4dfe209-0190-469c-8921-12966336e6ce', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 17:45:49'),
(34, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'intake_create', '', 'b212cedd-f8df-4a1b-917e-df0e7a3b3495', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"document_id\":8,\"tracking_id\":\"DENR-XII-2026-0008\",\"attachment_count\":0}', '2026-04-06 17:51:08'),
(35, 3, 4, 'PENRO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', '66a3e35b-f379-44c7-8ec3-01dc2d2ff25f', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 17:56:21'),
(36, 3, 4, 'PENRO', 'ACTION_FORBIDDEN', 'document_action', 'APPROVE', '9557df5f-21e1-48e2-a17e-bbcca261ed59', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document must be received before approval.', '{\"is_outbox_sync\":false}', '2026-04-06 18:04:16'),
(37, 3, 4, 'PENRO', 'ACTION_FORBIDDEN', 'document_action', 'APPROVE', '236c16e6-9803-4791-b7a9-cfdfaaf82a01', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document must be received before approval.', '{\"is_outbox_sync\":false}', '2026-04-06 18:07:33'),
(38, 3, 4, 'PENRO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', 'cca11598-df15-4d7c-8757-b4f2c1ea9e8a', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 18:07:58'),
(39, 2, 2, 'PACDO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', '9b27f591-4f84-464a-a04d-5ed7d311af48', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 18:16:17'),
(40, 2, 2, 'PACDO', 'ACTION_FORBIDDEN', 'document_action', 'RECEIVE', 'a146a2e7-1eec-4b83-a00d-a19e873a6ac6', '/edats/actions/document-action.php', 403, 0, 0, 0, 0, 'server-document-action', 'Document is not awaiting receive.', '{\"is_outbox_sync\":false}', '2026-04-06 18:17:18'),
(41, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'intake_create', '', '684ecaef-1405-40c6-8a7e-643f9eb522b0', '/edats/actions/create-document.php', 200, 0, 0, 0, 0, 'server-create-document', 'Document intake created successfully.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"document_id\":9,\"tracking_id\":\"DENR-XII-2026-0009\",\"attachment_count\":0}', '2026-04-06 18:18:46'),
(42, 10, 5, 'CENRO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', '78765066-9196-4c48-9be7-32d351429289', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"CENRO\",\"current_version\":2}', '2026-04-06 18:25:41'),
(43, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', 'dc769103-9c08-42db-8730-4379da7f5e8a', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":3}', '2026-04-06 18:26:24'),
(44, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '6e8f53f5-1807-4265-8e10-91b78278dfb2', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":4}', '2026-04-06 18:26:29'),
(45, 3, 4, 'PENRO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'cc45383c-02e7-4ca9-82fa-ed9d12ffadd6', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PENRO\",\"current_version\":5}', '2026-04-06 18:26:37'),
(46, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', 'dedd345a-17d4-4bad-b08c-485b976b0f85', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":6}', '2026-04-06 18:27:18'),
(47, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '3398361c-8872-426b-b4eb-19f76df58962', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":7}', '2026-04-06 18:28:42'),
(48, 2, 2, 'PACDO', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'c2bbc0cc-3fd1-415d-a605-f23a2df0aa44', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"PACDO\",\"current_version\":8}', '2026-04-06 18:28:48'),
(49, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', '03894b5b-85a6-4b1f-83fd-d994812da6a6', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":9}', '2026-04-06 18:31:56'),
(50, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'APPROVE', 'ba71f9b6-a6c7-447c-9f04-89a716cc7c86', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":10}', '2026-04-06 18:32:01'),
(51, 1, 1, 'ORED', 'ACTION_SUCCESS', 'document_action', 'FORWARD', 'd6674de2-03f7-4f90-abee-7c97bc1a4156', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ORED\",\"current_version\":11}', '2026-04-06 18:32:30'),
(52, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'RECEIVE', 'e2132879-1cdf-4cb7-84a0-517c75f0746f', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":12}', '2026-04-06 18:50:41'),
(53, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'APPROVE', '44cc8802-ac1b-43dd-8770-ac426586411a', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":13}', '2026-04-06 18:50:47'),
(54, 74, 49, 'ARD_TS', 'ACTION_SUCCESS', 'document_action', 'FORWARD', '512bb38d-b878-4e3f-a816-d5bb7b054bd3', '/edats/actions/document-action.php', 200, 0, 0, 0, 0, 'server-document-action', 'Action completed.', '{\"is_outbox_sync\":false,\"role_key\":\"ARD_TS\",\"current_version\":14}', '2026-04-06 18:51:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'ORED', 'Office of the Regional Executive Director'),
(2, 'PACDO', 'Public Affairs and Communication Development Office'),
(3, 'PENRO', 'Provincial Environment and Natural Resources Officer'),
(4, 'CENRO', 'Community Environment and Natural Resources Officer'),
(5, 'DIVISION_CHIEF', 'Division Chief / Section Chief supervisor'),
(6, 'SECTION_STAFF', 'Regular Section Staff / Receiving Clerk'),
(7, 'PASU', 'Protected Area Superintendent'),
(8, 'ARD_TS', 'Assistant Regional Director for Technical Services'),
(9, 'ARD_MS', 'Assistant Regional Director for Management Services'),
(10, 'SUPER_ADMIN', 'System Super Administrator (global user/data/analytics/network/theme control)');

-- --------------------------------------------------------

--
-- Table structure for table `role_unit_mappings`
--

CREATE TABLE `role_unit_mappings` (
  `id` int(11) NOT NULL,
  `parent_role_id` int(11) NOT NULL,
  `child_role_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `unit_name` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_unit_mappings`
--

INSERT INTO `role_unit_mappings` (`id`, `parent_role_id`, `child_role_id`, `office_id`, `unit_name`, `created_at`) VALUES
(1, 3, 4, 5, 'PENRO SOUTH COTABATO -> CENRO Banga', '2026-03-15 23:23:54'),
(2, 3, 4, 7, 'PENRO SARANGANI -> CENRO Glan', '2026-03-15 23:23:54'),
(3, 3, 4, 43, 'PENRO COTABATO -> CENRO Midyap', '2026-03-15 23:23:54'),
(4, 3, 4, 44, 'PENRO COTABATO -> CENRO Matalam', '2026-03-15 23:23:54'),
(5, 3, 4, 45, 'PENRO SULTAN KUDARAT -> CENRO Tacurong City', '2026-03-15 23:23:54'),
(6, 3, 4, 46, 'PENRO SULTAN KUDARAT -> CENRO Kalamansig', '2026-03-15 23:23:54'),
(7, 3, 4, 47, 'PENRO SOUTH COTABATO -> CENRO General Santos City', '2026-03-15 23:23:54'),
(8, 3, 4, 48, 'PENRO SARANGANI -> CENRO Kiamba', '2026-03-15 23:23:54'),
(16, 5, 6, 16, 'Licenses, Patents & Deeds Division -> Forest Utilization', '2026-03-15 23:23:54'),
(17, 5, 6, 17, 'Licenses, Patents & Deeds Division -> Wildlife Resource Permitting', '2026-03-15 23:23:54'),
(18, 5, 6, 18, 'Licenses, Patents & Deeds Division -> Water Resources Utilization', '2026-03-15 23:23:54'),
(19, 5, 6, 19, 'Licenses, Patents & Deeds Division -> Patents and Deeds', '2026-03-15 23:23:54'),
(20, 5, 6, 20, 'Surveys & Mapping Division -> Surveys & Control', '2026-03-15 23:23:54'),
(21, 5, 6, 21, 'Surveys & Mapping Division -> Land Evaluation Surveys', '2026-03-15 23:23:54'),
(22, 5, 6, 22, 'Surveys & Mapping Division -> Agretacion Surveys & Corrections', '2026-03-15 23:23:54'),
(23, 5, 6, 23, 'Surveys & Mapping Division -> Original & Other Surveys', '2026-03-15 23:23:54'),
(24, 5, 6, 24, 'Surveys & Mapping Division -> Land Records', '2026-03-15 23:23:54'),
(25, 5, 6, 25, 'Conservation & Devt. Division -> PA Management & Biodiversity Conservation', '2026-03-15 23:23:54'),
(26, 5, 6, 26, 'Conservation & Devt. Division -> Production Forest Management', '2026-03-15 23:23:54'),
(27, 5, 6, 27, 'Conservation & Devt. Division -> Coastal Resources & Foreshore Management', '2026-03-15 23:23:54'),
(28, 5, 6, 28, 'Enforcement Division -> Surveillance & Intelligence', '2026-03-15 23:23:54'),
(29, 5, 6, 29, 'Enforcement Division -> Compliance, Monitoring & Investigation', '2026-03-15 23:23:54'),
(30, 5, 6, 30, 'Planning and Management Division (PMD) -> Planning & Programming', '2026-03-15 23:23:54'),
(31, 5, 6, 31, 'Planning and Management Division (PMD) -> Monitoring & Evaluation', '2026-03-15 23:23:54'),
(32, 5, 6, 32, 'Planning and Management Division (PMD) -> Regional ICT Unit', '2026-03-15 23:23:54'),
(33, 5, 6, 33, 'Administrative Division -> Personnel', '2026-03-15 23:23:54'),
(34, 5, 6, 34, 'Administrative Division -> HRDM (Human Resource Development Management)', '2026-03-15 23:23:54'),
(35, 5, 6, 35, 'Administrative Division -> Procurement', '2026-03-15 23:23:54'),
(36, 5, 6, 36, 'Administrative Division -> General Services', '2026-03-15 23:23:54'),
(37, 5, 6, 37, 'Administrative Division -> Records', '2026-03-15 23:23:54'),
(38, 5, 6, 38, 'Finance Division -> Cash', '2026-03-15 23:23:54'),
(39, 5, 6, 39, 'Finance Division -> Accounting', '2026-03-15 23:23:54'),
(40, 5, 6, 40, 'Finance Division -> Budget', '2026-03-15 23:23:54'),
(41, 8, 5, 9, 'ARD_TS -> Licenses, Patents & Deeds Division', '2026-03-31 11:39:06'),
(42, 8, 5, 10, 'ARD_TS -> Surveys & Mapping Division', '2026-03-31 11:39:06'),
(43, 8, 5, 11, 'ARD_TS -> Conservation & Devt. Division', '2026-03-31 11:39:06'),
(44, 8, 5, 12, 'ARD_TS -> Enforcement Division', '2026-03-31 11:39:06'),
(48, 9, 5, 3, 'ARD_MS -> Planning and Management Division (PMD)', '2026-03-31 11:39:06'),
(49, 9, 5, 13, 'ARD_MS -> Legal Division', '2026-03-31 11:39:06'),
(50, 9, 5, 14, 'ARD_MS -> Administrative Division', '2026-03-31 11:39:06'),
(51, 9, 5, 15, 'ARD_MS -> Finance Division', '2026-03-31 11:39:06');

-- --------------------------------------------------------

--
-- Table structure for table `security_audit_logs`
--

CREATE TABLE `security_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `event_status` varchar(30) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_audit_logs`
--

INSERT INTO `security_audit_logs` (`id`, `user_id`, `email`, `event_type`, `event_status`, `ip_address`, `user_agent`, `remarks`, `created_at`) VALUES
(1, 78, 'qa.tester@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 11:13:53'),
(2, 78, 'qa.tester@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 13:57:03'),
(3, 78, 'qa.tester@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 13:57:13'),
(4, 78, 'qa.tester@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 13:57:31'),
(5, NULL, 'penro.southcotabato@denr.gov.ph', 'LOGIN_USER_NOT_FOUND', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Login email not found or inactive account.', '2026-04-06 13:57:43'),
(6, 75, 'ard.ms.regional@denr.gov.ph', 'PASSWORD_RESET_OTP_REQUEST', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password reset OTP sent.', '2026-04-06 13:59:24'),
(7, 75, 'ard.ms.regional@denr.gov.ph', 'PASSWORD_RESET_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password reset completed.', '2026-04-06 13:59:36'),
(8, 74, 'ard.ts.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 13:59:44'),
(9, 49, 'staff.forest.utilization@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:00:15'),
(10, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:01:32'),
(11, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:03:30'),
(12, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:05:07'),
(13, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:11:03'),
(14, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:18:03'),
(15, 79, 'super-admin@gmail.com', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:21:34'),
(16, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:25:17'),
(17, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:30:20'),
(18, 1, 'ored.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:35:09'),
(19, 79, 'super-admin@gmail.com', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:42:11'),
(20, 79, 'super-admin@gmail.com', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 14:51:09'),
(21, 79, 'super-admin@gmail.com', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:14:45'),
(22, 79, 'super-admin@gmail.com', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:15:38'),
(23, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:37:11'),
(24, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:37:36'),
(25, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:38:51'),
(26, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 15:39:59'),
(27, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:00:45'),
(28, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:05:56'),
(29, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 16:24:13'),
(30, 74, 'ard.ts.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:24:24'),
(31, 27, 'chief.licenses.patents.deeds.division@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:27:41'),
(32, 49, 'staff.forest.utilization@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:32:00'),
(33, 25, 'pasu.mt.matutum.protected.landscape@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:36:30'),
(34, 75, 'ard.ms.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:37:02'),
(35, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 16:41:26'),
(36, 11, 'cenro.glan@denr.gov.ph', 'PASSWORD_RESET_OTP_REQUEST', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password reset OTP sent.', '2026-04-06 16:58:13'),
(37, 11, 'cenro.glan@denr.gov.ph', 'PASSWORD_RESET_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password reset completed.', '2026-04-06 16:58:25'),
(38, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:04:55'),
(39, 11, 'cenro.glan@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:10:33'),
(40, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:18:06'),
(41, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:28:41'),
(42, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:32:48'),
(43, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 17:44:27'),
(44, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 17:59:50'),
(45, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:00:25'),
(46, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 18:01:07'),
(47, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 18:01:22'),
(48, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 18:01:38'),
(49, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:01:54'),
(50, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_PASSWORD_FAILED', 'FAILED', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Invalid password.', '2026-04-06 18:05:13'),
(51, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:05:35'),
(52, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:08:30'),
(53, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:09:15'),
(54, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:10:37'),
(55, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:11:09'),
(56, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:12:50'),
(57, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:14:06'),
(58, 10, 'cenro.banga@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:25:16'),
(59, 3, 'penro.south.cotabato@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:25:51'),
(60, 2, 'pacdo.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:26:52'),
(61, 1, 'ored.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:29:24'),
(62, 74, 'ard.ts.regional@denr.gov.ph', 'LOGIN_SUCCESS', 'SUCCESS', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'Password verified. MFA login step is currently disabled.', '2026-04-06 18:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `tracking_slips`
--

CREATE TABLE `tracking_slips` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `from_office_id` int(11) NOT NULL,
  `receiving_office_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `date_time_received` datetime NOT NULL,
  `action_required` varchar(255) DEFAULT NULL,
  `receive_method` enum('AUTO_OPEN','MANUAL') NOT NULL DEFAULT 'MANUAL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking_slips`
--

INSERT INTO `tracking_slips` (`id`, `document_id`, `from_office_id`, `receiving_office_id`, `received_by`, `date_time_received`, `action_required`, `receive_method`) VALUES
(1, 3, 5, 4, 3, '2026-04-06 14:25:45', 'Forwarded to next office.', 'MANUAL'),
(2, 3, 4, 2, 2, '2026-04-06 14:31:09', 'Forwarded to next office.', 'MANUAL'),
(3, 3, 2, 1, 1, '2026-04-06 14:35:43', 'Forwarded to next office.', 'MANUAL'),
(4, 3, 1, 49, 74, '2026-04-06 16:24:53', 'ORED instruction: assigned to concerned ARD.', 'MANUAL'),
(5, 3, 49, 9, 27, '2026-04-06 16:28:13', 'Forwarded for processing.', 'MANUAL'),
(6, 3, 9, 16, 49, '2026-04-06 16:32:27', 'Forwarded to next office.', 'MANUAL'),
(7, 5, 5, 4, 3, '2026-04-06 18:26:24', 'Forwarded to next office.', 'MANUAL'),
(8, 5, 4, 2, 2, '2026-04-06 18:27:18', 'Forwarded to next office.', 'MANUAL'),
(9, 5, 2, 1, 1, '2026-04-06 18:31:56', 'Forwarded to next office.', 'MANUAL'),
(10, 5, 1, 49, 74, '2026-04-06 18:50:41', 'ORED instruction: assigned to concerned ARD.', 'MANUAL');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `is_seeded_demo` tinyint(1) NOT NULL DEFAULT 0,
  `password_changed_at` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `mfa_otp_code` varchar(6) DEFAULT NULL,
  `mfa_otp_expires_at` datetime DEFAULT NULL,
  `mfa_failed_attempts` int(11) NOT NULL DEFAULT 0,
  `mfa_locked_until` datetime DEFAULT NULL,
  `mfa_resend_count` int(11) NOT NULL DEFAULT 0,
  `mfa_resend_window_started_at` datetime DEFAULT NULL,
  `mfa_last_sent_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `office_id`, `role_id`, `first_name`, `last_name`, `email`, `password_hash`, `must_change_password`, `is_seeded_demo`, `password_changed_at`, `failed_login_attempts`, `locked_until`, `last_login_at`, `last_login_ip`, `otp_code`, `otp_expires_at`, `mfa_otp_code`, `mfa_otp_expires_at`, `mfa_failed_attempts`, `mfa_locked_until`, `mfa_resend_count`, `mfa_resend_window_started_at`, `mfa_last_sent_at`, `is_active`) VALUES
(1, 1, 1, 'Regional', 'Director', 'ored.regional@denr.gov.ph', '$2y$10$fFe1V1EE0x201p0JsNLPU.QupmsPkEkNPlrRBqcVU1PKo4OEme7mW', 0, 0, NULL, 0, NULL, '2026-04-06 18:29:24', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(2, 2, 2, 'Regional', 'PACDO', 'pacdo.regional@denr.gov.ph', '$2y$10$TbKQ1UNatLM9oOHd5ezxBOoJJUqxY4u5bW3FMmGiKKG74/0jMAn3.', 0, 0, NULL, 0, NULL, '2026-04-06 18:26:52', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(3, 4, 3, 'PENRO', 'SOUTH COTABATO', 'penro.south.cotabato@denr.gov.ph', '$2y$10$Ql0ebySds79U9UbY/1cabO3FbiflkZp.NPCR32tCe3Ashydc0Wi/y', 0, 0, NULL, 0, NULL, '2026-04-06 18:25:51', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(4, 6, 3, 'PENRO', 'SARANGANI', 'penro.sarangani@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(5, 41, 3, 'PENRO', 'COTABATO', 'penro.cotabato@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(6, 42, 3, 'PENRO', 'SULTAN KUDARAT', 'penro.sultan.kudarat@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(10, 5, 4, 'CENRO', 'Banga', 'cenro.banga@denr.gov.ph', '$2y$10$QYM5jAA58.358Vhjhpw0Juei1CQhKMkJRy/WP58BFIqpLLLZoqr6W', 0, 0, NULL, 0, NULL, '2026-04-06 18:25:16', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(11, 7, 4, 'CENRO', 'Glan', 'cenro.glan@denr.gov.ph', '$2y$10$6Bmkl8.X9GX7AndQBZtu5O1Sdsqh793dZTrnB4d/CWeljHCyXUcai', 0, 0, NULL, 0, NULL, '2026-04-06 17:10:33', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(12, 43, 4, 'CENRO', 'Midyap', 'cenro.midyap@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(13, 44, 4, 'CENRO', 'Matalam', 'cenro.matalam@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(14, 45, 4, 'CENRO', 'Tacurong City', 'cenro.tacurong.city@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(15, 46, 4, 'CENRO', 'Kalamansig', 'cenro.kalamansig@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(16, 47, 4, 'CENRO', 'General Santos City', 'cenro.general.santos.city@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(17, 48, 4, 'CENRO', 'Kiamba', 'cenro.kiamba@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(25, 8, 7, 'PASU', 'Superintendent', 'pasu.mt.matutum.protected.landscape@denr.gov.ph', '$2y$10$ZcXMngKAdFrZZ8n6dKjV0O/1ptkgXzkxxhpdMhhmeUVLwvKJkT2ua', 0, 0, NULL, 0, NULL, '2026-04-06 16:36:30', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(26, 3, 5, 'Division Chief', 'Planning and Management Division (PMD)', 'chief.planning.and.management.division.pmd@denr.gov.ph', '$2y$10$PGfrmY8re8.wX8QjQLkrDuvkG5wyAtPdiRBHDarzdUDFezjZs97mS', 0, 0, NULL, 0, NULL, '2026-03-31 17:00:24', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(27, 9, 5, 'Division Chief', 'Licenses, Patents & Deeds Division', 'chief.licenses.patents.deeds.division@denr.gov.ph', '$2y$10$EwPbFmSWg8bnCtxkq6Lz/u5LkBLGnJJ8tbfdXrSjFoVXSxptU7RvO', 0, 0, NULL, 0, NULL, '2026-04-06 16:27:41', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(28, 10, 5, 'Division Chief', 'Surveys & Mapping Division', 'chief.surveys.mapping.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(29, 11, 5, 'Division Chief', 'Conservation & Devt. Division', 'chief.conservation.devt.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(30, 12, 5, 'Division Chief', 'Enforcement Division', 'chief.enforcement.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(31, 13, 5, 'Division Chief', 'Legal Division', 'chief.legal.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(32, 14, 5, 'Division Chief', 'Administrative Division', 'chief.administrative.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(33, 15, 5, 'Division Chief', 'Finance Division', 'chief.finance.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(41, 3, 6, 'Section Staff', 'Planning and Management Division (PMD)', 'staff.planning.and.management.division.pmd@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(42, 9, 6, 'Section Staff', 'Licenses, Patents & Deeds Division', 'staff.licenses.patents.deeds.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(43, 10, 6, 'Section Staff', 'Surveys & Mapping Division', 'staff.surveys.mapping.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(44, 11, 6, 'Section Staff', 'Conservation & Devt. Division', 'staff.conservation.devt.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(45, 12, 6, 'Section Staff', 'Enforcement Division', 'staff.enforcement.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(46, 13, 6, 'Section Staff', 'Legal Division', 'staff.legal.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(47, 14, 6, 'Section Staff', 'Administrative Division', 'staff.administrative.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(48, 15, 6, 'Section Staff', 'Finance Division', 'staff.finance.division@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(49, 16, 6, 'Section Staff', 'Forest Utilization', 'staff.forest.utilization@denr.gov.ph', '$2y$10$3RI7uof49nXb3VxvoW0ajuoCF.ffnwJngxEuBwULE2FDnq2ECtrtG', 0, 0, NULL, 0, NULL, '2026-04-06 16:32:00', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(50, 17, 6, 'Section Staff', 'Wildlife Resource Permitting', 'staff.wildlife.resource.permitting@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(51, 18, 6, 'Section Staff', 'Water Resources Utilization', 'staff.water.resources.utilization@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(52, 19, 6, 'Section Staff', 'Patents and Deeds', 'staff.patents.and.deeds@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(53, 20, 6, 'Section Staff', 'Surveys & Control', 'staff.surveys.control@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(54, 21, 6, 'Section Staff', 'Land Evaluation Surveys', 'staff.land.evaluation.surveys@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(55, 22, 6, 'Section Staff', 'Agretacion Surveys & Corrections', 'staff.agretacion.surveys.corrections@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(56, 23, 6, 'Section Staff', 'Original & Other Surveys', 'staff.original.other.surveys@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(57, 24, 6, 'Section Staff', 'Land Records', 'staff.land.records@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(58, 25, 6, 'Section Staff', 'PA Management & Biodiversity Conservation', 'staff.pa.management.biodiversity.conservation@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(59, 26, 6, 'Section Staff', 'Production Forest Management', 'staff.production.forest.management@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(60, 27, 6, 'Section Staff', 'Coastal Resources & Foreshore Management', 'staff.coastal.resources.foreshore.management@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(61, 28, 6, 'Section Staff', 'Surveillance & Intelligence', 'staff.surveillance.intelligence@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(62, 29, 6, 'Section Staff', 'Compliance, Monitoring & Investigation', 'staff.compliance.monitoring.investigation@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(63, 30, 6, 'Section Staff', 'Planning & Programming', 'staff.planning.programming@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(64, 31, 6, 'Section Staff', 'Monitoring & Evaluation', 'staff.monitoring.evaluation@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(65, 32, 6, 'Section Staff', 'Regional ICT Unit', 'staff.regional.ict.unit@denr.gov.ph', '$2y$10$nGoDMUy5a7tRTprDjuR/ZOveVAKYSqDWwVCEyxZMUJKHknZcWJ5z6', 0, 0, NULL, 0, NULL, '2026-03-30 09:31:56', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(66, 33, 6, 'Section Staff', 'Personnel', 'staff.personnel@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(67, 34, 6, 'Section Staff', 'HRDM (Human Resource Development Management)', 'staff.hrdm.human.resource.development.management@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(68, 35, 6, 'Section Staff', 'Procurement', 'staff.procurement@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(69, 36, 6, 'Section Staff', 'General Services', 'staff.general.services@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(70, 37, 6, 'Section Staff', 'Records', 'staff.records@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(71, 38, 6, 'Section Staff', 'Cash', 'staff.cash@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(72, 39, 6, 'Section Staff', 'Accounting', 'staff.accounting@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(73, 40, 6, 'Section Staff', 'Budget', 'staff.budget@denr.gov.ph', '$2y$10$3TWzm8MsR6iVLN3JGe1tkO2NG2H/ehR2zg5LpYCC3rTYHUnNyb1FK', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(74, 49, 8, 'Assistant', 'Regional Director TS', 'ard.ts.regional@denr.gov.ph', '$2y$10$d..nMN0Ammt25i105FvJ0.rjjyIg6MrhHuPeRm.4FvLrUVRqJvFnW', 1, 1, NULL, 0, NULL, '2026-04-06 18:49:55', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(75, 50, 9, 'Assistant', 'Regional Director MS', 'ard.ms.regional@denr.gov.ph', '$2y$10$EZ/sdGN/niThdQkFtYL7IeH2Dz6tbhQEVwq4U336aLu2Ylj9CajW2', 1, 1, NULL, 0, NULL, '2026-04-06 16:37:02', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(76, 1, 6, 'Test', 'User', 'test9203@denr.gov.ph', '$2y$10$tOC3vvwEYdxs8o7lBFI0nO2Z6.3jznPLJBSnN7rDw68pvU7f/kks2', 0, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(77, 1, 6, 'Test', 'User', 'testuser@denr.gov.ph', '$2y$10$zEpgxxtVi.R7sAyNLy8Y9uTDOF2451BE6BfB.Obk2PPK4ZTou/0ii', 0, 0, NULL, 0, NULL, '2026-04-03 17:01:10', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(78, 5, 4, 'QA', 'Tester', 'qa.tester@denr.gov.ph', '$2y$10$iOwEdf2vBJuppFjNus.CMO4N2g8FFcbY.DVN2i/6NrrrxFJ57JLf.', 0, 0, NULL, 3, NULL, '2026-04-06 11:13:53', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1),
(79, 32, 10, 'SUPER', 'ADMIN', 'super-admin@gmail.com', '$2y$10$n4td8EQs8Zp80eANYuZMBumXftOJ6fPCNDJ0KOiIZBpvJGlNume5y', 0, 0, NULL, 0, NULL, '2026-04-06 15:15:38', '::1', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `workflow_transitions`
--

CREATE TABLE `workflow_transitions` (
  `id` int(11) NOT NULL,
  `action_type` varchar(30) NOT NULL,
  `allowed_from_role_id` int(11) DEFAULT NULL,
  `allowed_to_role_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workflow_transitions`
--

INSERT INTO `workflow_transitions` (`id`, `action_type`, `allowed_from_role_id`, `allowed_to_role_id`, `description`, `is_active`, `created_at`) VALUES
(1, 'FORWARD', 6, 5, 'Section Staff to Division Chief', 1, '2026-03-14 22:31:04'),
(2, 'FORWARD', 5, 2, 'Division Chief to PACDO', 1, '2026-03-14 22:31:04'),
(3, 'FORWARD', 4, 3, 'CENRO to PENRO', 1, '2026-03-14 22:31:04'),
(4, 'FORWARD', 7, 3, 'PASU to PENRO', 1, '2026-03-14 22:31:04'),
(5, 'FORWARD', 3, 2, 'PENRO to PACDO', 1, '2026-03-14 22:31:04'),
(6, 'FORWARD', 2, 1, 'PACDO to ORED', 1, '2026-03-14 22:31:04'),
(7, 'FORWARD', 3, 4, 'PENRO route down to CENRO', 1, '2026-03-14 22:31:04'),
(8, 'FORWARD', 3, 7, 'PENRO route down to PASU', 1, '2026-03-14 22:31:04'),
(9, 'RETURN', 2, 3, 'PACDO return to PENRO', 1, '2026-03-14 22:31:04'),
(10, 'RETURN', 1, 2, 'ORED return to PACDO', 1, '2026-03-14 22:31:04'),
(11, 'RETURN', 5, 6, 'Division Chief return to Section Staff', 1, '2026-03-14 22:31:04'),
(12, 'REROUTE', 5, 6, 'Division Chief ad-hoc reroute to staff', 1, '2026-03-14 22:31:04'),
(13, 'OVERRIDE', 1, NULL, 'ORED executive override', 1, '2026-03-14 22:31:04'),
(14, 'RECEIVE', NULL, 4, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(15, 'RECEIVE', NULL, 5, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(16, 'RECEIVE', NULL, 1, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(17, 'RECEIVE', NULL, 2, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(18, 'RECEIVE', NULL, 7, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(19, 'RECEIVE', NULL, 3, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(20, 'RECEIVE', NULL, 6, 'Receive into custody', 1, '2026-03-14 22:31:04'),
(21, 'FORWARD', 1, 5, 'ORED assign to Division Chief', 1, '2026-03-15 00:11:04'),
(22, 'FORWARD', 5, 1, 'Division Chief escalate to ORED', 1, '2026-03-15 00:11:04'),
(23, 'FORWARD', 1, 2, 'ORED route signed item to PACDO', 1, '2026-03-15 00:11:04'),
(24, 'FORWARD', 5, 6, 'Division Chief assign to Section Staff', 1, '2026-03-15 00:11:04'),
(25, 'RETURN', 3, 4, 'PENRO return to CENRO', 1, '2026-03-15 00:11:04'),
(26, 'RETURN', 3, 7, 'PENRO return to PASU', 1, '2026-03-15 00:11:04'),
(27, 'RETURN', 2, 4, 'PACDO return directly to CENRO', 1, '2026-03-15 00:11:04'),
(28, 'RETURN', 2, 7, 'PACDO return directly to PASU', 1, '2026-03-15 00:11:04'),
(29, 'APPROVE', 5, NULL, 'DIVISION_CHIEF can approve', 1, '2026-03-15 00:11:04'),
(30, 'APPROVE', 1, NULL, 'ORED can approve', 1, '2026-03-15 00:11:04'),
(31, 'APPROVE', 2, NULL, 'PACDO can approve', 1, '2026-03-15 00:11:04'),
(32, 'APPROVE', 3, NULL, 'PENRO can approve', 1, '2026-03-15 00:11:04'),
(36, 'SIGN', 1, NULL, 'ORED can sign', 1, '2026-03-15 00:11:04'),
(37, 'PENDING', 4, NULL, 'CENRO can mark pending', 1, '2026-03-15 00:11:04'),
(38, 'PENDING', 5, NULL, 'DIVISION_CHIEF can mark pending', 1, '2026-03-15 00:11:04'),
(39, 'PENDING', 1, NULL, 'ORED can mark pending', 1, '2026-03-15 00:11:04'),
(40, 'PENDING', 2, NULL, 'PACDO can mark pending', 1, '2026-03-15 00:11:04'),
(41, 'PENDING', 7, NULL, 'PASU can mark pending', 1, '2026-03-15 00:11:04'),
(42, 'PENDING', 3, NULL, 'PENRO can mark pending', 1, '2026-03-15 00:11:04'),
(43, 'PENDING', 6, NULL, 'SECTION_STAFF can mark pending', 1, '2026-03-15 00:11:04'),
(44, 'RELEASE', 2, 3, 'PACDO release to PENRO', 1, '2026-03-15 00:11:04'),
(45, 'RELEASE', 2, 4, 'PACDO release to CENRO', 1, '2026-03-15 00:11:04'),
(46, 'RELEASE', 2, 7, 'PACDO release to PASU', 1, '2026-03-15 00:11:04'),
(47, 'RECEIVE', NULL, 9, 'Receive into custody (ARD_MS)', 1, '2026-03-31 11:39:06'),
(48, 'RECEIVE', NULL, 8, 'Receive into custody (ARD_TS)', 1, '2026-03-31 11:39:06'),
(50, 'APPROVE', 9, NULL, 'ARD_MS can approve', 1, '2026-03-31 11:39:06'),
(51, 'APPROVE', 8, NULL, 'ARD_TS can approve', 1, '2026-03-31 11:39:06'),
(53, 'PENDING', 9, NULL, 'ARD_MS can mark pending', 1, '2026-03-31 11:39:06'),
(54, 'PENDING', 8, NULL, 'ARD_TS can mark pending', 1, '2026-03-31 11:39:06'),
(56, 'FORWARD', 1, 8, 'ORED assign to ARD TS', 1, '2026-03-31 11:39:06'),
(57, 'FORWARD', 1, 9, 'ORED assign to ARD MS', 1, '2026-03-31 11:39:06'),
(58, 'FORWARD', 8, 5, 'ARD TS assign to Division Chief', 1, '2026-03-31 11:39:06'),
(59, 'FORWARD', 9, 5, 'ARD MS assign to Division Chief', 1, '2026-03-31 11:39:06'),
(60, 'FORWARD', 5, 8, 'Division Chief return to ARD TS', 1, '2026-03-31 11:39:06'),
(61, 'FORWARD', 5, 9, 'Division Chief return to ARD MS', 1, '2026-03-31 11:39:06'),
(62, 'FORWARD', 8, 1, 'ARD TS elevate to ORED', 1, '2026-03-31 11:39:06'),
(63, 'FORWARD', 9, 1, 'ARD MS elevate to ORED', 1, '2026-03-31 11:39:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_office_id` (`destination_office_id`),
  ADD KEY `fk_activity_logs_destination_user` (`destination_user_id`),
  ADD KEY `idx_activity_scope_created` (`action_scope`,`created_at`),
  ADD KEY `idx_activity_doc_visible` (`document_id`,`is_visible_on_slip`),
  ADD KEY `idx_activity_logs_doc_action_created` (`document_id`,`action_type`,`created_at`);

--
-- Indexes for table `api_idempotency_operations`
--
ALTER TABLE `api_idempotency_operations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_idempotency_operation` (`user_id`,`action_key`,`operation_id`),
  ADD KEY `idx_idempotency_status_updated` (`status`,`updated_at`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_id` (`tracking_id`),
  ADD KEY `document_type_id` (`document_type_id`),
  ADD KEY `originating_office_id` (`originating_office_id`),
  ADD KEY `fk_documents_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_documents_current_holder_user` (`current_holder_user_id`),
  ADD KEY `fk_documents_pending_user` (`pending_user_id`),
  ADD KEY `idx_documents_source_type` (`source_type`),
  ADD KEY `idx_documents_pending_office` (`pending_office_id`),
  ADD KEY `idx_documents_status_created` (`status`,`created_at`),
  ADD KEY `idx_documents_scope_status_created` (`current_office_id`,`pending_office_id`,`status`,`created_at`);

--
-- Indexes for table `document_attachments`
--
ALTER TABLE `document_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_document_types_created_by_role` (`created_by_role_id`);

--
-- Indexes for table `document_type_requests`
--
ALTER TABLE `document_type_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dtr_reviewed_by_user` (`reviewed_by_user_id`),
  ADD KEY `fk_dtr_linked_document_type` (`linked_document_type_id`),
  ADD KEY `idx_dtr_status_created` (`status`,`created_at`),
  ADD KEY `idx_dtr_requester_status` (`requested_by_user_id`,`status`,`created_at`),
  ADD KEY `idx_dtr_office_status` (`requested_by_office_id`,`status`,`created_at`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_office_id` (`parent_office_id`);

--
-- Indexes for table `offline_sync_logs`
--
ALTER TABLE `offline_sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_offline_sync_logs_created` (`created_at`),
  ADD KEY `idx_offline_sync_logs_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_offline_sync_logs_event_created` (`event_type`,`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_unit_mappings`
--
ALTER TABLE `role_unit_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_unit_mapping` (`parent_role_id`,`child_role_id`,`office_id`),
  ADD KEY `fk_role_unit_child` (`child_role_id`),
  ADD KEY `fk_role_unit_office` (`office_id`);

--
-- Indexes for table `security_audit_logs`
--
ALTER TABLE `security_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_audit_event_created` (`event_type`,`created_at`),
  ADD KEY `idx_security_audit_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_security_audit_email_created` (`email`,`created_at`);

--
-- Indexes for table `tracking_slips`
--
ALTER TABLE `tracking_slips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_office_id` (`from_office_id`),
  ADD KEY `receiving_office_id` (`receiving_office_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_tracking_slips_doc_receive_office` (`document_id`,`receiving_office_id`,`date_time_received`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_users_locked_until` (`locked_until`),
  ADD KEY `idx_users_mfa_locked_until` (`mfa_locked_until`),
  ADD KEY `idx_users_must_change_password` (`must_change_password`);

--
-- Indexes for table `workflow_transitions`
--
ALTER TABLE `workflow_transitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_workflow_transition` (`action_type`,`allowed_from_role_id`,`allowed_to_role_id`),
  ADD KEY `fk_workflow_trans_from_role` (`allowed_from_role_id`),
  ADD KEY `fk_workflow_trans_to_role` (`allowed_to_role_id`),
  ADD KEY `idx_workflow_transition_action` (`action_type`,`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `api_idempotency_operations`
--
ALTER TABLE `api_idempotency_operations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `document_attachments`
--
ALTER TABLE `document_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `document_type_requests`
--
ALTER TABLE `document_type_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `offline_sync_logs`
--
ALTER TABLE `offline_sync_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_unit_mappings`
--
ALTER TABLE `role_unit_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `security_audit_logs`
--
ALTER TABLE `security_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tracking_slips`
--
ALTER TABLE `tracking_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `workflow_transitions`
--
ALTER TABLE `workflow_transitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_3` FOREIGN KEY (`destination_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `fk_activity_logs_destination_user` FOREIGN KEY (`destination_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`originating_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`current_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `fk_documents_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_documents_current_holder_user` FOREIGN KEY (`current_holder_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_documents_pending_office` FOREIGN KEY (`pending_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `fk_documents_pending_user` FOREIGN KEY (`pending_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_attachments`
--
ALTER TABLE `document_attachments`
  ADD CONSTRAINT `document_attachments_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `document_types`
--
ALTER TABLE `document_types`
  ADD CONSTRAINT `fk_document_types_created_by_role` FOREIGN KEY (`created_by_role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `document_type_requests`
--
ALTER TABLE `document_type_requests`
  ADD CONSTRAINT `fk_dtr_linked_document_type` FOREIGN KEY (`linked_document_type_id`) REFERENCES `document_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dtr_requested_by_office` FOREIGN KEY (`requested_by_office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dtr_requested_by_user` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dtr_reviewed_by_user` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`parent_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_unit_mappings`
--
ALTER TABLE `role_unit_mappings`
  ADD CONSTRAINT `fk_role_unit_child` FOREIGN KEY (`child_role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_role_unit_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `fk_role_unit_parent` FOREIGN KEY (`parent_role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `security_audit_logs`
--
ALTER TABLE `security_audit_logs`
  ADD CONSTRAINT `fk_security_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tracking_slips`
--
ALTER TABLE `tracking_slips`
  ADD CONSTRAINT `tracking_slips_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tracking_slips_ibfk_2` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `tracking_slips_ibfk_3` FOREIGN KEY (`receiving_office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `tracking_slips_ibfk_4` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `workflow_transitions`
--
ALTER TABLE `workflow_transitions`
  ADD CONSTRAINT `fk_workflow_trans_from_role` FOREIGN KEY (`allowed_from_role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_workflow_trans_to_role` FOREIGN KEY (`allowed_to_role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
