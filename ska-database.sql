SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `ska_db`
--
CREATE DATABASE IF NOT EXISTS `ska_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ska_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'staff',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(2, 'Super Admin', 'admin', '$2y$10$EAK.ostDlbsWTAQFnCvOtenpUVdeTpxPZ7Kfk4cn3w6n1nznBVQtq', 'admin', '2025-12-08 13:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `duration` varchar(30) NOT NULL,
  `fees` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1-Active\r\n0-Inactive',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `duration`, `fees`, `is_active`, `created_at`) VALUES
(397, 'CLASSNUR', 'CLASS - NURSERY', '60 Months', 700.00, 1, NULL),
(398, 'CLASSLKG', 'CLASS - LKG (Lower Kindergarten)', '60 Months', 700.00, 1, NULL),
(399, 'CLASSUKG', 'CLASS - UKG (Upper Kindergarten)', '60 Months', 700.00, 1, NULL),
(400, 'CLASS1', 'CLASS - 1', '60 Months', 700.00, 1, NULL),
(401, 'CLASS2', 'CLASS - 2', '60 Months', 700.00, 1, NULL),
(402, 'CLASS3', 'CLASS - 3', '60 Months', 700.00, 1, NULL),
(403, 'CLASS4', 'CLASS - 4', '60 Months', 700.00, 1, NULL),
(404, 'CLASS5', 'CLASS - 5', '60 Months', 700.00, 1, NULL),
(405, 'CLASS6', 'CLASS - 6', '60 Months', 700.00, 1, NULL),
(406, 'CLASS7', 'CLASS - 7', '60 Months', 700.00, 1, NULL),
(407, 'CLASS8', 'CLASS - 8', '60 Months', 700.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedule`
--

DROP TABLE IF EXISTS `exam_schedule`;
CREATE TABLE `exam_schedule` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `exam_date` date NOT NULL,
  `exam_time` varchar(50) NOT NULL,
  `is_scheduled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_schedule`
--

INSERT INTO `exam_schedule` (`id`, `course_code`, `subject_code`, `exam_date`, `exam_time`, `is_scheduled`, `created_at`) VALUES
(5, 'ADCA', 'ADCA101', '2025-12-27', '15:59', 1, '2025-12-25 04:55:27'),
(6, 'ADCTT', 'ADCTT103', '2025-12-31', '16:01', 0, '2025-12-25 04:56:07'),
(7, 'ADCA', 'ADCA102', '2026-01-02', '13:18', 1, '2025-12-25 05:13:58'),
(8, 'ADCA', 'ADCA102', '2025-12-28', '13:20', 1, '2025-12-25 05:15:48');

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(50) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payment_mode` varchar(20) DEFAULT 'Cash',
  `receipt_no` varchar(30) DEFAULT NULL,
  `payment_date` date DEFAULT curdate(),
  `added_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `reg_no`, `amount`, `payment_mode`, `receipt_no`, `payment_date`, `added_by`, `created_at`) VALUES
(1, 'CEC250001', 100.00, 'Online', 'R251209316', '2025-12-09', 'RR', '2025-12-09 14:42:08'),
(2, 'CEC250004', 400.00, 'Cash', 'R251209931', '2025-12-09', 'RR', '2025-12-09 14:45:04'),
(3, 'CEC250004', 340.00, 'Cash', 'R251209746', '2025-12-09', 'RR', '2025-12-09 14:46:55'),
(4, 'CEC250006', 2000.00, 'Cash', 'R251209958', '2025-12-09', 'RR', '2025-12-09 20:29:25'),
(5, 'CEC250006', 2000.00, 'Cash', 'R251209419', '2025-12-09', 'RR', '2025-12-09 20:29:53'),
(6, 'CEC250006', 1990.00, 'Cash', 'R251209661', '2025-12-09', 'RR', '2025-12-09 21:19:39'),
(7, 'CEC250007', 4500.00, 'Online', 'R251209463', '2025-12-09', 'RR', '2025-12-09 21:45:12'),
(8, 'CEC250007', 1000.00, 'Cash', 'R251210741', '2025-12-10', 'Super Admin', '2025-12-10 13:33:35'),
(9, 'CEC250007', 1000.00, 'Cash', 'R251210676', '2025-12-10', 'Super Admin', '2025-12-10 13:33:41'),
(10, 'CEC250007', 100.00, 'Cash', 'R251210582', '2025-12-10', 'Super Admin', '2025-12-10 13:33:47'),
(11, 'CEC250007', 100.00, 'Cash', 'R251210952', '2025-12-10', 'Super Admin', '2025-12-10 13:33:53'),
(12, 'CEC250007', 100.00, 'Cash', 'R251210229', '2025-12-10', 'Super Admin', '2025-12-10 13:33:53'),
(13, 'CEC250007', 100.00, 'Cash', 'R251210577', '2025-12-10', 'Super Admin', '2025-12-10 13:33:54'),
(14, 'CEC250007', 100.00, 'Cash', 'R251210668', '2025-12-10', 'Super Admin', '2025-12-10 13:33:54'),
(15, 'CEC250007', 100.00, 'Cash', 'R251210776', '2025-12-10', 'Super Admin', '2025-12-10 13:33:54'),
(16, 'CEC250007', 100.00, 'Cash', 'R251210380', '2025-12-10', 'Super Admin', '2025-12-10 13:33:54'),
(17, 'CEC250007', 100.00, 'Cash', 'R251210181', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(18, 'CEC250007', 100.00, 'Cash', 'R251210331', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(19, 'CEC250007', 100.00, 'Cash', 'R251210745', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(20, 'CEC250007', 100.00, 'Cash', 'R251210432', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(21, 'CEC250007', 100.00, 'Cash', 'R251210224', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(22, 'CEC250007', 100.00, 'Cash', 'R251210275', '2025-12-10', 'Super Admin', '2025-12-10 13:33:55'),
(23, 'CEC250007', 100.00, 'Cash', 'R251210991', '2025-12-10', 'Super Admin', '2025-12-10 13:33:56'),
(24, 'CEC250007', 100.00, 'Cash', 'R251210334', '2025-12-10', 'Super Admin', '2025-12-10 13:33:56'),
(25, 'CEC250007', 100.00, 'Cash', 'R251210541', '2025-12-10', 'Super Admin', '2025-12-10 13:33:56'),
(26, 'CEC250007', 100.00, 'Cash', 'R251210113', '2025-12-10', 'Super Admin', '2025-12-10 13:33:56'),
(27, 'CEC250007', 100.00, 'Cash', 'R251210709', '2025-12-10', 'Super Admin', '2025-12-10 13:33:56'),
(28, 'CEC250007', 100.00, 'Cash', 'R251210301', '2025-12-10', 'Super Admin', '2025-12-10 13:33:57'),
(29, 'CEC250007', 100.00, 'Cash', 'R251210300', '2025-12-10', 'Super Admin', '2025-12-10 13:33:57'),
(30, 'CEC250007', 100.00, 'Cash', 'R251210347', '2025-12-10', 'Super Admin', '2025-12-10 13:33:57'),
(31, 'CEC250007', 800.00, 'Cash', 'R251210175', '2025-12-10', 'Rahul', '2025-12-10 13:36:37'),
(32, 'CEC250007', 40.00, 'Cash', 'R251210842', '2025-12-10', 'Rahul', '2025-12-10 13:51:45'),
(33, 'CEC250007', 30.00, 'Cash', 'R251210675', '2025-12-10', 'Super Admin', '2025-12-10 13:54:10'),
(34, 'CEC250007', 10.00, 'UPI', '11', '2025-12-10', 'Super Admin', '2025-12-10 18:44:16'),
(35, 'CEC250007', 10.00, 'UPI', 'R251210765', '2025-12-10', 'Super Admin', '2025-12-10 23:56:47'),
(36, 'JKYFD8652/2025127098', 100.00, 'Cheque', 'Receipt1234', '2025-12-22', 'Rahul Sir', '2025-12-22 19:11:30'),
(37, 'JKYFD8652/2025127098', 10.00, 'Cheque', 'Receipt1234', '2025-12-22', 'Rahul Sir', '2025-12-22 19:13:06'),
(38, 'SKA800613/25127109', 200.00, 'Cash', 'R251227420', '2025-12-27', 'Super Admin', '2025-12-27 14:52:22');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_uploads`
--

DROP TABLE IF EXISTS `gallery_uploads`;
CREATE TABLE `gallery_uploads` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `caption` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery_uploads`
--

INSERT INTO `gallery_uploads` (`id`, `filename`, `original_name`, `file_size`, `upload_date`, `caption`) VALUES
(55, '694f6d6db16e8_1766813037.jpg', 'celebration-3.jpg', 173208, '2025-12-27 05:23:57', ''),
(56, '694f6d7ac6cc8_1766813050.jpg', 'celebration-2.jpg', 213942, '2025-12-27 05:24:10', ''),
(57, '694f6d89c20d3_1766813065.jpg', 'ska-tour.jpg', 173877, '2025-12-27 05:24:25', ''),
(58, '694f6d96ac579_1766813078.jpg', 'ska-banner-ai.jpg', 200065, '2025-12-27 05:24:38', ''),
(59, '694f6d9e62dcf_1766813086.jpg', 'ska-kids.jpg', 181772, '2025-12-27 05:24:46', ''),
(60, '694f6da8f2834_1766813096.jpg', 'ska-banner.jpg', 125441, '2025-12-27 05:24:56', '');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `subject_code` varchar(50) NOT NULL,
  `exam_held_on` varchar(20) DEFAULT NULL,
  `theory_marks` int(11) DEFAULT 0,
  `total_theory_marks` int(11) DEFAULT 0,
  `result_status` varchar(10) DEFAULT 'Pending',
  `result_date` timestamp NULL DEFAULT NULL,
  `update_time_stamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `identity_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `study_center_code` varchar(20) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `course_code` varchar(200) DEFAULT NULL,
  `session_year` varchar(20) DEFAULT NULL,
  `enquiry_source` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `total_fees` decimal(10,2) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `id_card_generated` varchar(10) DEFAULT 'No' COMMENT 'Yes/No - ID card generation status',
  `marksheet_gen` varchar(10) NOT NULL DEFAULT 'No',
  `certificate_gen` varchar(10) NOT NULL DEFAULT 'No',
  `admit_card_gen` varchar(10) NOT NULL DEFAULT 'No',
  `marksheet_gen_date` date DEFAULT NULL,
  `certificate_gen_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_no`, `student_name`, `father_name`, `mother_name`, `dob`, `gender`, `category`, `marital_status`, `identity_type`, `id_number`, `qualification`, `mobile`, `email`, `address`, `pincode`, `state`, `district`, `city`, `study_center_code`, `religion`, `course_code`, `session_year`, `enquiry_source`, `photo`, `total_fees`, `admission_date`, `status`, `id_card_generated`, `marksheet_gen`, `certificate_gen`, `admit_card_gen`, `marksheet_gen_date`, `certificate_gen_date`) VALUES
(7109, 'SKA800613/25127109', 'Rahul Raj', 'U N Lahotia', 'Hansa Devi', '2015-12-23', 'Male', 'SC', 'Unmarried', 'AADHAR', '3004519826301', '10th Passed', '8405913144', 'rraj56803@gmail.com', 'Ward No-15, Raj Bhawan, Lalganj', '852137', 'Bihar', 'Supaul', 'Surpatganj', 'SKA800613', 'Hinduism', 'CLASS5', '2026-2027', 'Banner', NULL, 700.00, '2025-12-27', 'Active', 'Yes', 'Yes', 'Yes', 'Yes', '2025-12-27', '2025-12-27');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(250) NOT NULL,
  `photo_type` varchar(50) DEFAULT 'Photo' COMMENT 'Is Photo or Aadhar or PAN',
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `reg_no`, `photo_type`, `filename`, `original_name`, `file_size`, `upload_date`) VALUES
(7, 'JKYFD8789/25127091', 'PAN', 'pan7107_20251226_1766756621.jpeg', '16986754342.jpeg', 73760, '2025-12-26 13:43:41'),
(10, 'JKYFD8789/25127091', 'AADHAR', 'aadhar7107_20251226_1766760413.jpeg', '16989368383.jpeg', 17865, '2025-12-26 14:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `study_centers`
--

DROP TABLE IF EXISTS `study_centers`;
CREATE TABLE `study_centers` (
  `id` int(11) NOT NULL,
  `center_code` varchar(20) NOT NULL,
  `center_name` varchar(255) NOT NULL,
  `district` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT 'Bihar',
  `address` text DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1-Active\r\n0-Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_centers`
--

INSERT INTO `study_centers` (`id`, `center_code`, `center_name`, `district`, `state`, `address`, `pincode`, `phone`, `is_active`, `created_at`) VALUES
(1, 'SKA800613 ', 'Sri Krishna Academy (Karjain)', 'Supaul', 'Bihar', 'karjain, Supaul', '852215', '9430522843', 1, '2025-12-08 12:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `theory_marks` int(11) DEFAULT 0,
  `is_active` int(1) NOT NULL DEFAULT 1 COMMENT '1 - Active\r\n0 - Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course_code`, `subject_code`, `subject_name`, `theory_marks`, `is_active`, `created_at`) VALUES
(132, 'CLASSNUR', 'ENGNUR', 'English', 100, 1, '2025-12-27 06:39:08'),
(133, 'CLASSNUR', 'HINNUR', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(134, 'CLASSNUR', 'MATNUR', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(135, 'CLASSNUR', 'SCINUR', 'Science', 100, 1, '2025-12-27 06:39:08'),
(136, 'CLASSNUR', 'SSTNUR', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(137, 'CLASSNUR', 'GKNUR', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(138, 'CLASSNUR', 'COMNUR', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(139, 'CLASSNUR', 'ARTNUR', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(140, 'CLASSLKG', 'ENGLKG', 'English', 100, 1, '2025-12-27 06:39:08'),
(141, 'CLASSLKG', 'HINLKG', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(142, 'CLASSLKG', 'MATLKG', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(143, 'CLASSLKG', 'SCILKG', 'Science', 100, 1, '2025-12-27 06:39:08'),
(144, 'CLASSLKG', 'SSTLKG', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(145, 'CLASSLKG', 'GKLKG', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(146, 'CLASSLKG', 'COMLKG', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(147, 'CLASSLKG', 'ARTLKG', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(148, 'CLASSUKG', 'ENGUKG', 'English', 100, 1, '2025-12-27 06:39:08'),
(149, 'CLASSUKG', 'HINUKG', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(150, 'CLASSUKG', 'MATUKG', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(151, 'CLASSUKG', 'SCIUKG', 'Science', 100, 1, '2025-12-27 06:39:08'),
(152, 'CLASSUKG', 'SSTUKG', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(153, 'CLASSUKG', 'GKUKG', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(154, 'CLASSUKG', 'COMUKG', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(155, 'CLASSUKG', 'ARTUKG', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(156, 'CLASS1', 'ENG01', 'English', 100, 1, '2025-12-27 06:39:08'),
(157, 'CLASS1', 'HIN01', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(158, 'CLASS1', 'MAT01', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(159, 'CLASS1', 'SCI01', 'Science', 100, 1, '2025-12-27 06:39:08'),
(160, 'CLASS1', 'SST01', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(161, 'CLASS1', 'GK01', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(162, 'CLASS1', 'COM01', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(163, 'CLASS1', 'ART01', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(164, 'CLASS2', 'ENG02', 'English', 100, 1, '2025-12-27 06:39:08'),
(165, 'CLASS2', 'HIN02', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(166, 'CLASS2', 'MAT02', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(167, 'CLASS2', 'SCI02', 'Science', 100, 1, '2025-12-27 06:39:08'),
(168, 'CLASS2', 'SST02', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(169, 'CLASS2', 'GK02', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(170, 'CLASS2', 'COM02', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(171, 'CLASS2', 'ART02', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(172, 'CLASS3', 'ENG03', 'English', 100, 1, '2025-12-27 06:39:08'),
(173, 'CLASS3', 'HIN03', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(174, 'CLASS3', 'MAT03', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(175, 'CLASS3', 'SCI03', 'Science', 100, 1, '2025-12-27 06:39:08'),
(176, 'CLASS3', 'SST03', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(177, 'CLASS3', 'GK03', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(178, 'CLASS3', 'COM03', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(179, 'CLASS3', 'ART03', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(180, 'CLASS4', 'ENG04', 'English', 100, 1, '2025-12-27 06:39:08'),
(181, 'CLASS4', 'HIN04', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(182, 'CLASS4', 'MAT04', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(183, 'CLASS4', 'SCI04', 'Science', 100, 1, '2025-12-27 06:39:08'),
(184, 'CLASS4', 'SST04', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(185, 'CLASS4', 'GK04', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(186, 'CLASS4', 'COM04', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(187, 'CLASS4', 'ART04', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(188, 'CLASS5', 'ENG05', 'English', 100, 1, '2025-12-27 06:39:08'),
(189, 'CLASS5', 'HIN05', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(190, 'CLASS5', 'MAT05', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(191, 'CLASS5', 'SCI05', 'Science', 100, 1, '2025-12-27 06:39:08'),
(192, 'CLASS5', 'SST05', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(193, 'CLASS5', 'GK05', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(194, 'CLASS5', 'COM05', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(195, 'CLASS5', 'ART05', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(196, 'CLASS6', 'ENG06', 'English', 100, 1, '2025-12-27 06:39:08'),
(197, 'CLASS6', 'HIN06', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(198, 'CLASS6', 'MAT06', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(199, 'CLASS6', 'SCI06', 'Science', 100, 1, '2025-12-27 06:39:08'),
(200, 'CLASS6', 'SST06', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(201, 'CLASS6', 'GK06', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(202, 'CLASS6', 'COM06', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(203, 'CLASS6', 'ART06', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(204, 'CLASS7', 'ENG07', 'English', 100, 1, '2025-12-27 06:39:08'),
(205, 'CLASS7', 'HIN07', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(206, 'CLASS7', 'MAT07', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(207, 'CLASS7', 'SCI07', 'Science', 100, 1, '2025-12-27 06:39:08'),
(208, 'CLASS7', 'SST07', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(209, 'CLASS7', 'GK07', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(210, 'CLASS7', 'COM07', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(211, 'CLASS7', 'ART07', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(212, 'CLASS8', 'ENG08', 'English', 100, 1, '2025-12-27 06:39:08'),
(213, 'CLASS8', 'HIN08', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(214, 'CLASS8', 'MAT08', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(215, 'CLASS8', 'SCI08', 'Science', 100, 1, '2025-12-27 06:39:08'),
(216, 'CLASS8', 'SST08', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(217, 'CLASS8', 'GK08', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(218, 'CLASS8', 'COM08', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(219, 'CLASS8', 'ART08', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(220, 'CLASS9', 'ENG09', 'English', 100, 1, '2025-12-27 06:39:08'),
(221, 'CLASS9', 'HIN09', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(222, 'CLASS9', 'MAT09', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(223, 'CLASS9', 'SCI09', 'Science', 100, 1, '2025-12-27 06:39:08'),
(224, 'CLASS9', 'SST09', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(225, 'CLASS9', 'GK09', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(226, 'CLASS9', 'COM09', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(227, 'CLASS9', 'ART09', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08'),
(228, 'CLASS10', 'ENG10', 'English', 100, 1, '2025-12-27 06:39:08'),
(229, 'CLASS10', 'HIN10', 'Hindi', 100, 1, '2025-12-27 06:39:08'),
(230, 'CLASS10', 'MAT10', 'Mathematics', 100, 1, '2025-12-27 06:39:08'),
(231, 'CLASS10', 'SCI10', 'Science', 100, 1, '2025-12-27 06:39:08'),
(232, 'CLASS10', 'SST10', 'Social Science', 100, 1, '2025-12-27 06:39:08'),
(233, 'CLASS10', 'GK10', 'General Knowledge', 100, 1, '2025-12-27 06:39:08'),
(234, 'CLASS10', 'COM10', 'Computer', 100, 1, '2025-12-27 06:39:08'),
(235, 'CLASS10', 'ART10', 'Drawing & Craft', 100, 1, '2025-12-27 06:39:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery_uploads`
--
ALTER TABLE `gallery_uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `study_centers`
--
ALTER TABLE `study_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `center_code` (`center_code`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=415;

--
-- AUTO_INCREMENT for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `gallery_uploads`
--
ALTER TABLE `gallery_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7110;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `study_centers`
--
ALTER TABLE `study_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;
COMMIT;
