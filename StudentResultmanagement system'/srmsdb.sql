-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 22, 2026 at 03:59 AM
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
-- Database: `srmsdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `college_school` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `image`, `college_school`, `address`) VALUES
(1, 'admin', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'admin_6999d970e94ee.png', 'Patan Multiple Campus', 'Patan Dhoka, Lalitpurdsa');

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `batch_id` int(11) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `start_year` year(4) DEFAULT NULL,
  `end_year` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch`
--

INSERT INTO `batch` (`batch_id`, `batch_name`, `start_year`, `end_year`) VALUES
(1, 'Batch 2022-2026', '2022', '2026'),
(2, 'Batch 2023-2027', '2023', '2027'),
(3, 'Batch 2024-2028', '2024', '2028'),
(4, 'Batch 2021-2025', '2021', '2025'),
(5, 'Batch 2022-2024', '2022', '2024'),
(6, 'Batch 2023-2025', '2023', '2025');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `contact_id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `suggestion` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `reply_date` datetime DEFAULT NULL,
  `contact_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`contact_id`, `name`, `email`, `suggestion`, `admin_reply`, `reply_date`, `contact_date`) VALUES
(1, 'Nimesh', 'Nimesh@gmail.com', 'Hello...', NULL, NULL, '2026-02-21 17:00:16'),
(2, 'Raju', 'Raju@gmail.com', 'OK', NULL, NULL, '2026-02-21 17:00:33'),
(3, 'Kishor', 'Kishor123@gmail.com', '123', 'wow', '2026-02-21 22:47:15', '2026-02-21 17:00:55');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `total_semesters` int(11) DEFAULT 8,
  `duration_years` int(11) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_name`, `dept_id`, `total_semesters`, `duration_years`) VALUES
(1, 'Bachelor of Arts in Sociology', 1, 8, 4),
(2, 'Bachelor of Arts in Psychology', 1, 8, 4),
(3, 'Bachelor of Arts in English Literature', 1, 8, 4),
(4, 'Master of Arts in Anthropology', 1, 4, 2),
(5, 'Bachelor of Education in English', 2, 8, 4),
(6, 'Bachelor of Education in Mathematics', 2, 8, 4),
(7, 'Bachelor of Education in Health', 2, 8, 4),
(8, 'Master of Education in Educational Leadership', 2, 4, 2),
(9, 'Bachelor of Business Administration', 3, 8, 4),
(10, 'Bachelor of Hotel Management', 3, 8, 4),
(11, 'Bachelor of Public Administration', 3, 8, 4),
(12, 'Master of Business Administration', 3, 4, 2),
(13, 'Bachelor of Science in Physics', 4, 8, 4),
(14, 'Bachelor of Science in Chemistry', 4, 8, 4),
(15, 'Bachelor of Science in Mathematics', 4, 8, 4),
(16, 'Master of Science in Environmental Science', 4, 4, 2),
(17, 'Bachelor of Laws (LLB)', 5, 8, 4),
(18, 'Bachelor of Business Law', 5, 8, 4),
(19, 'Bachelor of Criminal Justice', 5, 8, 4),
(20, 'Master of Laws (LLM)', 5, 4, 2),
(21, 'Bachelor of Fine Arts in Painting', 6, 8, 4),
(22, 'Bachelor of Fine Arts in Music', 6, 8, 4),
(23, 'Bachelor of Fine Arts in Sculpture', 6, 8, 4),
(24, 'Master of Fine Arts in Visual Arts', 6, 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(150) NOT NULL,
  `dept_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`dept_id`, `dept_name`, `dept_code`) VALUES
(1, 'Faculty of Humanities and Social Sciences', 'HUSS'),
(2, 'Faculty of Education', 'EDU'),
(3, 'Faculty of Management', 'MGT'),
(4, 'Faculty of Science and Technology', 'SCI'),
(5, 'Faculty of Law', 'LAW'),
(6, 'Faculty of Fine Arts', 'FINE');

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_id` int(11) NOT NULL,
  `exam_name` varchar(100) DEFAULT NULL,
  `exam_type` enum('Midterm','PreBoard','Board','Internal') DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam`
--

INSERT INTO `exam` (`exam_id`, `exam_name`, `exam_type`, `exam_date`, `is_active`) VALUES
(1, 'Midterm', 'Midterm', '2026-02-21', 1),
(2, 'Final', 'Board', '2026-02-21', 1);

-- --------------------------------------------------------

--
-- Table structure for table `exam_subject`
--

CREATE TABLE `exam_subject` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `theory_full_marks` int(11) NOT NULL,
  `theory_pass_marks` int(11) NOT NULL,
  `practical_full_marks` int(11) DEFAULT NULL,
  `practical_pass_marks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_subject`
--

INSERT INTO `exam_subject` (`id`, `exam_id`, `subject_id`, `exam_date`, `theory_full_marks`, `theory_pass_marks`, `practical_full_marks`, `practical_pass_marks`) VALUES
(1, 1, 109, '2026-02-24', 60, 24, 40, 18),
(2, 2, 109, '2026-02-21', 60, 24, 40, 18);

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `result_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `theory_marks` decimal(5,2) DEFAULT NULL,
  `practical_marks` decimal(5,2) DEFAULT NULL,
  `total_marks` decimal(5,2) GENERATED ALWAYS AS (coalesce(`theory_marks`,0) + coalesce(`practical_marks`,0)) STORED,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `result`
--

INSERT INTO `result` (`result_id`, `student_id`, `subject_id`, `exam_id`, `theory_marks`, `practical_marks`, `grade`, `remarks`) VALUES
(1, 73, 106, 1, 45.00, 32.00, 'B+', NULL),
(2, 74, 106, 1, 32.00, 32.00, 'B', NULL),
(3, 75, 106, 1, 11.00, 23.00, 'F', NULL),
(4, 76, 106, 1, 55.00, 35.00, 'A+', NULL),
(5, 77, 106, 1, 34.00, 34.00, 'B', NULL),
(6, 73, 107, 1, 34.00, 22.00, 'C+', NULL),
(7, 74, 107, 1, 45.00, 34.00, 'B+', NULL),
(8, 75, 107, 1, 34.00, 33.00, 'B', NULL),
(9, 76, 107, 1, 57.00, 23.00, 'A', NULL),
(10, 77, 107, 1, 45.00, 33.00, 'B+', NULL),
(11, 73, 108, 1, 34.00, 22.00, 'C+', NULL),
(12, 74, 108, 1, 34.00, 22.00, 'C+', NULL),
(13, 75, 108, 1, 43.00, 23.00, 'B', NULL),
(14, 76, 108, 1, 45.00, 34.00, 'B+', NULL),
(15, 77, 108, 1, 54.00, 37.00, 'A+', NULL),
(16, 73, 109, 1, 11.00, 33.00, 'F', NULL),
(17, 74, 109, 1, 34.00, 23.00, 'C+', NULL),
(18, 75, 109, 1, 45.00, 34.00, 'B+', NULL),
(19, 76, 109, 1, 34.00, 23.00, 'C+', NULL),
(20, 77, 109, 1, 45.00, 34.00, 'B+', NULL),
(21, 73, 106, 2, 45.00, 34.00, 'B+', NULL),
(22, 74, 106, 2, 55.00, 34.00, 'A', NULL),
(23, 75, 106, 2, 55.00, 36.00, 'A+', NULL),
(24, 76, 106, 2, 45.00, 23.00, 'B', NULL),
(25, 77, 106, 2, 34.00, 33.00, 'B', NULL),
(26, 73, 107, 2, 45.00, 33.00, 'B+', NULL),
(27, 74, 107, 2, 55.00, 34.00, 'A', NULL),
(28, 75, 107, 2, 45.00, 34.00, 'B+', NULL),
(29, 76, 107, 2, 33.00, 23.00, 'C+', NULL),
(30, 77, 107, 2, 45.00, 34.00, 'B+', NULL),
(31, 73, 108, 2, 45.00, 23.00, 'B', NULL),
(32, 74, 108, 2, 55.00, 23.00, 'B+', NULL),
(33, 75, 108, 2, 54.00, 33.00, 'A', NULL),
(34, 76, 108, 2, 57.00, 23.00, 'A', NULL),
(35, 77, 108, 2, 45.00, 34.00, 'B+', NULL),
(36, 73, 109, 2, 34.00, 23.00, 'C+', NULL),
(37, 74, 109, 2, 45.00, 34.00, 'B+', NULL),
(38, 75, 109, 2, 55.00, 34.00, 'A', NULL),
(39, 76, 109, 2, 34.00, 22.00, 'C+', NULL),
(40, 77, 109, 2, 60.00, 37.00, 'A+', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `roll_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `current_semester` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `roll_number`, `first_name`, `middle_name`, `last_name`, `dob`, `gender`, `phone`, `email`, `address`, `course_id`, `batch_id`, `current_semester`, `section`) VALUES
(1, 'SOC22001', 'Aarav', 'Kumar', 'Sharma', '2004-05-12', 'Male', '9812000001', 'aarav.sharma@student.pmc.edu.np', 'Kathmandu', 1, 1, 6, 'A'),
(2, 'SOC22002', 'Anjali', 'Kumari', 'Singh', '2004-08-23', 'Female', '9812000002', 'anjali.singh@student.pmc.edu.np', 'Lalitpur', 1, 1, 6, 'A'),
(3, 'SOC22003', 'Bibek', NULL, 'Gurung', '2005-02-15', 'Male', '9812000003', 'bibek.gurung@student.pmc.edu.np', 'Pokhara', 1, 2, 4, 'B'),
(4, 'SOC23004', 'Diksha', 'Kumari', 'Thapa', '2005-11-30', 'Female', '9812000004', 'diksha.thapa@student.pmc.edu.np', 'Biratnagar', 1, 2, 4, 'B'),
(5, 'SOC23005', 'Elina', NULL, 'Rai', '2006-03-18', 'Female', '9812000005', 'elina.rai@student.pmc.edu.np', 'Dharan', 1, 3, 2, 'A'),
(6, 'PSY22001', 'Firoj', 'Ali', 'Khan', '2004-07-07', 'Male', '9812000006', 'firoj.khan@student.pmc.edu.np', 'Kathmandu', 2, 1, 6, 'A'),
(7, 'PSY22002', 'Gita', NULL, 'Magar', '2004-09-19', 'Female', '9812000007', 'gita.magar@student.pmc.edu.np', 'Patan', 2, 1, 6, 'A'),
(8, 'PSY23003', 'Hari', 'Bahadur', 'Tamang', '2005-04-25', 'Male', '9812000008', 'hari.tamang@student.pmc.edu.np', 'Bhaktapur', 2, 2, 4, 'B'),
(9, 'PSY23004', 'Isha', NULL, 'Shrestha', '2005-12-02', 'Female', '9812000009', 'isha.shrestha@student.pmc.edu.np', 'Lalitpur', 2, 2, 4, 'B'),
(10, 'PSY24005', 'Jenish', 'Kumar', 'Mahato', '2006-06-14', 'Male', '9812000010', 'jenish.mahato@student.pmc.edu.np', 'Janakpur', 2, 3, 2, 'A'),
(11, 'ENG22001', 'Kabita', NULL, 'Dahal', '2004-02-28', 'Female', '9812000011', 'kabita.dahal@student.pmc.edu.np', 'Kathmandu', 3, 1, 6, 'C'),
(12, 'ENG22002', 'Lokesh', 'Prasad', 'Yadav', '2004-10-10', 'Male', '9812000012', 'lokesh.yadav@student.pmc.edu.np', 'Birgunj', 3, 1, 6, 'C'),
(13, 'ENG23003', 'Muna', NULL, 'Shrestha', '2005-01-05', 'Female', '9812000013', 'muna.shrestha@student.pmc.edu.np', 'Patan', 3, 2, 4, 'A'),
(14, 'ENG23004', 'Nabin', NULL, 'Khadka', '2005-08-17', 'Male', '9812000014', 'nabin.khadka@student.pmc.edu.np', 'Kirtipur', 3, 2, 4, 'A'),
(15, 'ENG24005', 'Ojaswi', 'Kumari', 'Sharma', '2006-09-22', 'Female', '9812000015', 'ojaswi.sharma@student.pmc.edu.np', 'Lalitpur', 3, 3, 2, 'B'),
(16, 'BED22001', 'Prakash', NULL, 'Bista', '2003-11-11', 'Male', '9813000001', 'prakash.bista@student.pmc.edu.np', 'Dhangadhi', 5, 4, 8, 'A'),
(17, 'BED22002', 'Rita', 'Kumari', 'Chaudhary', '2004-03-03', 'Female', '9813000002', 'rita.chaudhary@student.pmc.edu.np', 'Nepalgunj', 5, 1, 6, 'B'),
(18, 'BED23003', 'Sagar', NULL, 'Poudel', '2005-07-19', 'Male', '9813000003', 'sagar.poudel@student.pmc.edu.np', 'Pokhara', 5, 2, 4, 'A'),
(19, 'BED23004', 'Tara', NULL, 'Gurung', '2005-12-25', 'Female', '9813000004', 'tara.gurung@student.pmc.edu.np', 'Butwal', 5, 2, 4, 'A'),
(20, 'BED24005', 'Umesh', 'Prasad', 'Kushwaha', '2006-04-08', 'Male', '9813000005', 'umesh.kushwaha@student.pmc.edu.np', 'Birgunj', 5, 3, 2, 'B'),
(21, 'BEM22001', 'Bandana', NULL, 'Thapa', '2004-01-15', 'Female', '9813000006', 'bandana.thapa@student.pmc.edu.np', 'Kathmandu', 6, 1, 6, 'C'),
(22, 'BEM22002', 'Chitra', 'Bahadur', 'Karki', '2004-06-20', 'Male', '9813000007', 'chitra.karki@student.pmc.edu.np', 'Lalitpur', 6, 1, 6, 'C'),
(23, 'BEM23003', 'Deepa', NULL, 'Sharma', '2005-09-13', 'Female', '9813000008', 'deepa.sharma@student.pmc.edu.np', 'Bhaktapur', 6, 2, 4, 'A'),
(24, 'BEM23004', 'Ekaraj', NULL, 'Tamang', '2005-11-05', 'Male', '9813000009', 'ekaraj.tamang@student.pmc.edu.np', 'Dhulikhel', 6, 2, 4, 'A'),
(25, 'BEM24005', 'Fulmaya', NULL, 'Limbu', '2006-07-30', 'Female', '9813000010', 'fulmaya.limbu@student.pmc.edu.np', 'Ilam', 6, 3, 2, 'B'),
(26, 'BBA22001', 'Ganesh', NULL, 'Adhikari', '2004-02-14', 'Male', '9814000001', 'ganesh.adhikari@student.pmc.edu.np', 'Kathmandu', 9, 1, 6, 'A'),
(27, 'BBA22002', 'Hima', 'Kumari', 'Shrestha', '2004-05-05', 'Female', '9814000002', 'hima.shrestha@student.pmc.edu.np', 'Lalitpur', 9, 1, 6, 'A'),
(28, 'BBA22003', 'Indra', 'Bahadur', 'Khulal', '2004-08-18', 'Male', '9814000003', 'indra.khulal@student.pmc.edu.np', 'Pokhara', 9, 1, 6, 'B'),
(29, 'BBA23004', 'Jamuna', NULL, 'Gurung', '2005-03-22', 'Female', '9814000004', 'jamuna.gurung@student.pmc.edu.np', 'Patan', 9, 2, 4, 'A'),
(30, 'BBA23005', 'Karan', 'Kumar', 'Shah', '2005-10-11', 'Male', '9814000005', 'karan.shah@student.pmc.edu.np', 'Biratnagar', 9, 2, 4, 'A'),
(31, 'BBA24006', 'Laxmi', NULL, 'Neupane', '2006-01-27', 'Female', '9814000006', 'laxmi.neupane@student.pmc.edu.np', 'Hetauda', 9, 3, 2, 'B'),
(32, 'BBA24007', 'Milan', NULL, 'Rai', '2006-06-09', 'Male', '9814000007', 'milan.rai@student.pmc.edu.np', 'Dharan', 9, 3, 2, 'B'),
(33, 'BHM22001', 'Nisha', NULL, 'Maharjan', '2004-04-16', 'Female', '9814000008', 'nisha.maharjan@student.pmc.edu.np', 'Patan', 10, 1, 6, 'C'),
(34, 'BHM22002', 'Om', 'Prakash', 'Yadav', '2004-09-29', 'Male', '9814000009', 'om.yadav@student.pmc.edu.np', 'Birgunj', 10, 1, 6, 'C'),
(35, 'BHM23003', 'Pabitra', NULL, 'Khadka', '2005-12-07', 'Female', '9814000010', 'pabitra.khadka@student.pmc.edu.np', 'Kathmandu', 10, 2, 4, 'A'),
(36, 'BHM23004', 'Rajesh', NULL, 'Tamang', '2005-02-19', 'Male', '9814000011', 'rajesh.tamang@student.pmc.edu.np', 'Bhaktapur', 10, 2, 4, 'A'),
(37, 'BHM24005', 'Sita', 'Devi', 'Sharma', '2006-08-03', 'Female', '9814000012', 'sita.sharma@student.pmc.edu.np', 'Lalitpur', 10, 3, 2, 'B'),
(38, 'BPA22001', 'Tulsi', NULL, 'Poudel', '2004-07-24', 'Male', '9814000013', 'tulsi.poudel@student.pmc.edu.np', 'Pokhara', 11, 1, 6, 'B'),
(39, 'BPA23002', 'Urmila', NULL, 'Karki', '2005-10-01', 'Female', '9814000014', 'urmila.karki@student.pmc.edu.np', 'Dhangadhi', 11, 2, 4, 'A'),
(40, 'BPA23003', 'Bishnu', 'Prasad', 'Bhattarai', '2005-03-14', 'Male', '9814000015', 'bishnu.bhattarai@student.pmc.edu.np', 'Butwal', 11, 2, 4, 'A'),
(41, 'BPA24004', 'Yamuna', NULL, 'Thapa', '2006-11-11', 'Female', '9814000016', 'yamuna.thapa@student.pmc.edu.np', 'Nepalgunj', 11, 3, 2, 'C'),
(42, 'PHY22001', 'Anil', NULL, 'Sharma', '2003-09-08', 'Male', '9815000001', 'anil.sharma@student.pmc.edu.np', 'Kathmandu', 13, 4, 8, 'A'),
(43, 'PHY22002', 'Bimala', NULL, 'Gautam', '2004-01-23', 'Female', '9815000002', 'bimala.gautam@student.pmc.edu.np', 'Lalitpur', 13, 1, 6, 'B'),
(44, 'PHY23003', 'Chandra', 'Kant', 'Jha', '2005-05-17', 'Male', '9815000003', 'chandra.jha@student.pmc.edu.np', 'Janakpur', 13, 2, 4, 'A'),
(45, 'PHY23004', 'Deepa', NULL, 'Bhandari', '2005-08-29', 'Female', '9815000004', 'deepa.bhandari@student.pmc.edu.np', 'Pokhara', 13, 2, 4, 'A'),
(46, 'PHY24005', 'Ekendra', NULL, 'Singh', '2006-12-12', 'Male', '9815000005', 'ekendra.singh@student.pmc.edu.np', 'Biratnagar', 13, 3, 2, 'C'),
(47, 'CHM22001', 'Gita', NULL, 'Rana', '2004-06-06', 'Female', '9815000006', 'gita.rana@student.pmc.edu.np', 'Bhaktapur', 14, 1, 6, 'B'),
(48, 'CHM22002', 'Hari', 'Prasad', 'Shrestha', '2004-11-19', 'Male', '9815000007', 'hari.shrestha@student.pmc.edu.np', 'Patan', 14, 1, 6, 'B'),
(49, 'CHM23003', 'Indira', NULL, 'Khanal', '2005-04-02', 'Female', '9815000008', 'indira.khanal@student.pmc.edu.np', 'Kirtipur', 14, 2, 4, 'A'),
(50, 'CHM23004', 'Jitendra', NULL, 'Mahato', '2005-07-15', 'Male', '9815000009', 'jitendra.mahato@student.pmc.edu.np', 'Birgunj', 14, 2, 4, 'A'),
(51, 'CHM24005', 'Kabita', NULL, 'Limbu', '2006-02-28', 'Female', '9815000010', 'kabita.limbu@student.pmc.edu.np', 'Ilam', 14, 3, 2, 'C'),
(52, 'MAT22001', 'Lokendra', 'Bahadur', 'Shahi', '2004-03-10', 'Male', '9815000011', 'lokendra.shahi@student.pmc.edu.np', 'Nepalgunj', 15, 1, 6, 'C'),
(53, 'MAT22002', 'Manju', NULL, 'Devi', '2004-10-22', 'Female', '9815000012', 'manju.devi@student.pmc.edu.np', 'Lalitpur', 15, 1, 6, 'C'),
(54, 'MAT23003', 'Narayan', NULL, 'Prasad', '2005-01-05', 'Male', '9815000013', 'narayan.prasad@student.pmc.edu.np', 'Kathmandu', 15, 2, 4, 'B'),
(55, 'MAT23004', 'Ojashwi', NULL, 'Shakya', '2005-09-18', 'Female', '9815000014', 'ojashwi.shakya@student.pmc.edu.np', 'Patan', 15, 2, 4, 'B'),
(56, 'MAT24005', 'Pramod', NULL, 'Khadka', '2006-05-31', 'Male', '9815000015', 'pramod.khadka@student.pmc.edu.np', 'Dharan', 15, 3, 2, 'A'),
(57, 'LLB22001', 'Rabin', NULL, 'Sharma', '2002-08-15', 'Male', '9816000001', 'rabin.sharma@student.pmc.edu.np', 'Kathmandu', 17, 4, 8, 'A'),
(58, 'LLB22002', 'Saraswati', NULL, 'Timilsina', '2003-02-20', 'Female', '9816000002', 'saraswati.timilsina@student.pmc.edu.np', 'Lalitpur', 17, 4, 8, 'A'),
(59, 'LLB22003', 'Tilak', 'Bahadur', 'Thapa', '2003-07-11', 'Male', '9816000003', 'tilak.thapa@student.pmc.edu.np', 'Pokhara', 17, 1, 6, 'B'),
(60, 'LLB23004', 'Usha', NULL, 'Kiran', '2004-12-24', 'Female', '9816000004', 'usha.kiran@student.pmc.edu.np', 'Biratnagar', 17, 2, 4, 'C'),
(61, 'LLB23005', 'Bikash', 'Kumar', 'Sah', '2004-04-07', 'Male', '9816000005', 'bikash.sah@student.pmc.edu.np', 'Janakpur', 17, 2, 4, 'C'),
(62, 'LLB24006', 'Yashoda', NULL, 'Kumari', '2005-10-30', 'Female', '9816000006', 'yashoda.kumari@student.pmc.edu.np', 'Nepalgunj', 17, 3, 2, 'A'),
(63, 'CRJ22001', 'Abhishek', NULL, 'Singh', '2003-05-19', 'Male', '9816000007', 'abhishek.singh@student.pmc.edu.np', 'Kathmandu', 19, 1, 6, 'D'),
(64, 'CRJ22002', 'Binita', NULL, 'Shrestha', '2003-11-02', 'Female', '9816000008', 'binita.shrestha@student.pmc.edu.np', 'Lalitpur', 19, 1, 6, 'D'),
(65, 'CRJ23003', 'Chiranjivi', NULL, 'Bhandari', '2004-09-14', 'Male', '9816000009', 'chiranjivi.bhandari@student.pmc.edu.np', 'Bhaktapur', 19, 2, 4, 'B'),
(66, 'CRJ23004', 'Durga', NULL, 'Thapa', '2005-03-27', 'Female', '9816000010', 'durga.thapa@student.pmc.edu.np', 'Patan', 19, 2, 4, 'B'),
(67, 'CRJ24005', 'Eshwar', 'Prasad', 'Gupta', '2006-01-09', 'Male', '9816000011', 'eshwar.gupta@student.pmc.edu.np', 'Birgunj', 19, 3, 2, 'C'),
(68, 'FPA22001', 'Gopal', '', 'Chitrakar', '2003-12-03', 'Male', '9817000001', 'gopal.chitrakar@student.pmc.edu.np', 'Patan', 21, NULL, 1, 'A'),
(69, 'FPA22002', 'Hiranya', '', 'Maharjan', '2004-06-16', 'Female', '9817000002', 'hiranya.maharjan@student.pmc.edu.np', 'Bhaktapur', 21, NULL, 1, 'A'),
(70, 'FPA23003', 'Indra', 'Man', 'Shakya', '2005-02-28', 'Male', '9817000003', 'indra.shakya@student.pmc.edu.np', 'Kirtipur', 21, NULL, 1, 'A'),
(71, 'FPA23004', 'Junu', '', 'Tuladhar', '2005-08-11', 'Female', '9817000004', 'junu.tuladhar@student.pmc.edu.np', 'Patan', 21, NULL, 2, 'B'),
(72, 'FPA24005', 'Kiran', '', 'Shrestha', '2006-04-24', 'Male', '9817000005', 'kiran.shrestha@student.pmc.edu.np', 'Lalitpur', 21, NULL, 2, 'B'),
(73, 'FMU22001', 'Laxman', '', 'Dangol', '2004-01-07', 'Male', '9817000006', 'laxman.dangol@student.pmc.edu.np', 'Kathmandu', 22, NULL, 1, 'A'),
(74, 'FMU22002', 'Maya', '', 'Devkota', '2004-07-20', 'Female', '9817000007', 'maya.devkota@student.pmc.edu.np', 'Lalitpur', 22, NULL, 1, 'A'),
(75, 'FMU23003', 'Nabin', '', 'Kayastha', '2005-10-03', 'Male', '9817000008', 'nabin.kayastha@student.pmc.edu.np', 'Bhaktapur', 22, NULL, 1, 'A'),
(76, 'FMU23004', 'Oshin', '', 'Shrestha', '2005-05-16', 'Female', '9817000009', 'oshin.shrestha@student.pmc.edu.np', 'Patan', 22, NULL, 1, 'A'),
(77, 'FMU24005', 'Prakriti', '', 'Shahi', '2006-09-29', 'Female', '9817000010', 'prakriti.shahi@student.pmc.edu.np', 'Pokhara', 22, NULL, 1, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `subject_type` enum('compulsory','optional','elective') NOT NULL,
  `semester` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`, `subject_type`, `semester`) VALUES
(1, 77, 106, 'compulsory', 1),
(2, 77, 107, 'compulsory', 1),
(3, 77, 108, 'compulsory', 1),
(4, 77, 109, 'optional', 1),
(23, 76, 106, 'compulsory', 1),
(24, 76, 107, 'compulsory', 1),
(25, 76, 108, 'compulsory', 1),
(26, 76, 109, 'optional', 1),
(27, 75, 106, 'compulsory', 1),
(28, 75, 107, 'compulsory', 1),
(29, 75, 108, 'compulsory', 1),
(30, 75, 109, 'optional', 1),
(31, 74, 106, 'compulsory', 1),
(32, 74, 107, 'compulsory', 1),
(33, 74, 108, 'compulsory', 1),
(34, 74, 109, 'optional', 1),
(35, 73, 106, 'compulsory', 1),
(36, 73, 107, 'compulsory', 1),
(37, 73, 108, 'compulsory', 1),
(38, 73, 109, 'optional', 1),
(39, 72, 103, 'compulsory', 2),
(40, 72, 105, 'elective', 2),
(41, 71, 103, 'compulsory', 2),
(42, 71, 105, 'elective', 2),
(43, 70, 101, 'compulsory', 1),
(44, 70, 102, 'compulsory', 1),
(46, 70, 104, 'optional', 1),
(47, 69, 101, 'compulsory', 1),
(48, 69, 102, 'compulsory', 1),
(50, 69, 104, 'optional', 1),
(51, 68, 101, 'compulsory', 1),
(52, 68, 102, 'compulsory', 1),
(54, 68, 104, 'optional', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `semester_number` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `subject_type` enum('Compulsory','Optional','Elective') DEFAULT 'Compulsory'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_name`, `subject_code`, `credit_hours`, `semester_number`, `course_id`, `subject_type`) VALUES
(1, 'Introduction to Sociology', 'SOC101', 3, 1, 1, 'Compulsory'),
(2, 'Sociological Theories', 'SOC102', 3, 2, 1, 'Compulsory'),
(3, 'Research Methods in Sociology', 'SOC201', 4, 3, 1, 'Compulsory'),
(4, 'Social Psychology', 'SOC301', 3, 5, 1, 'Optional'),
(5, 'Urban Sociology', 'SOC401', 3, 7, 1, 'Elective'),
(6, 'General Psychology', 'PSY101', 3, 1, 2, 'Compulsory'),
(7, 'Developmental Psychology', 'PSY102', 3, 2, 2, 'Compulsory'),
(8, 'Abnormal Psychology', 'PSY201', 4, 3, 2, 'Compulsory'),
(9, 'Counseling Psychology', 'PSY301', 3, 5, 2, 'Optional'),
(10, 'Organizational Behavior', 'PSY401', 3, 7, 2, 'Elective'),
(11, 'English Poetry', 'ENG101', 3, 1, 3, 'Compulsory'),
(12, 'English Prose', 'ENG102', 3, 2, 3, 'Compulsory'),
(13, 'Shakespearean Studies', 'ENG201', 4, 3, 3, 'Compulsory'),
(14, 'American Literature', 'ENG301', 3, 5, 3, 'Optional'),
(15, 'Creative Writing', 'ENG401', 3, 7, 3, 'Elective'),
(16, 'Anthropological Theory', 'ANT501', 4, 1, 4, 'Compulsory'),
(17, 'Ethnographic Methods', 'ANT502', 4, 1, 4, 'Compulsory'),
(18, 'Medical Anthropology', 'ANT503', 3, 2, 4, 'Compulsory'),
(19, 'Visual Anthropology', 'ANT504', 3, 2, 4, 'Optional'),
(20, 'Digital Anthropology', 'ANT505', 3, 2, 4, 'Elective'),
(21, 'Foundations of Education', 'EDU101', 3, 1, 5, 'Compulsory'),
(22, 'English Language Teaching', 'EDU102', 3, 1, 5, 'Compulsory'),
(23, 'Curriculum Development', 'EDU201', 4, 3, 5, 'Compulsory'),
(24, 'Educational Psychology', 'EDU301', 3, 5, 5, 'Optional'),
(25, 'Technology in Education', 'EDU401', 3, 7, 5, 'Elective'),
(26, 'Mathematics for Teachers', 'MED101', 3, 1, 6, 'Compulsory'),
(27, 'Geometry and Algebra', 'MED102', 3, 2, 6, 'Compulsory'),
(28, 'Calculus for Educators', 'MED201', 4, 3, 6, 'Compulsory'),
(29, 'Statistics in Education', 'MED301', 3, 5, 6, 'Optional'),
(30, 'Mathematical Modeling', 'MED401', 3, 7, 6, 'Elective'),
(31, 'Health Education Fundamentals', 'HED101', 3, 1, 7, 'Compulsory'),
(32, 'Nutrition and Wellness', 'HED102', 3, 2, 7, 'Compulsory'),
(33, 'Community Health', 'HED201', 4, 3, 7, 'Compulsory'),
(34, 'School Health Programs', 'HED301', 3, 5, 7, 'Optional'),
(35, 'Global Health Issues', 'HED401', 3, 7, 7, 'Elective'),
(36, 'Educational Leadership Theory', 'MED501', 4, 1, 8, 'Compulsory'),
(37, 'School Administration', 'MED502', 4, 1, 8, 'Compulsory'),
(38, 'Educational Policy Analysis', 'MED503', 3, 2, 8, 'Compulsory'),
(39, 'Change Management in Education', 'MED504', 3, 2, 8, 'Optional'),
(40, 'Comparative Education Systems', 'MED505', 3, 2, 8, 'Elective'),
(41, 'Principles of Management', 'BBA101', 3, 1, 9, 'Compulsory'),
(42, 'Financial Accounting', 'BBA102', 3, 2, 9, 'Compulsory'),
(43, 'Marketing Management', 'BBA201', 4, 3, 9, 'Compulsory'),
(44, 'Human Resource Management', 'BBA301', 3, 5, 9, 'Optional'),
(45, 'Digital Marketing', 'BBA401', 3, 7, 9, 'Elective'),
(46, 'Hotel Operations', 'HOT101', 3, 1, 10, 'Compulsory'),
(47, 'Food and Beverage Management', 'HOT102', 3, 2, 10, 'Compulsory'),
(48, 'Front Office Management', 'HOT201', 4, 3, 10, 'Compulsory'),
(49, 'Hospitality Marketing', 'HOT301', 3, 5, 10, 'Optional'),
(50, 'Event Management', 'HOT401', 3, 7, 10, 'Elective'),
(51, 'Public Administration Theory', 'PAD101', 3, 1, 11, 'Compulsory'),
(52, 'Public Policy Analysis', 'PAD102', 3, 2, 11, 'Compulsory'),
(53, 'Government Accounting', 'PAD201', 4, 3, 11, 'Compulsory'),
(54, 'Local Governance', 'PAD301', 3, 5, 11, 'Optional'),
(55, 'E-Governance', 'PAD401', 3, 7, 11, 'Elective'),
(56, 'Strategic Management', 'MBA501', 4, 1, 12, 'Compulsory'),
(57, 'Managerial Economics', 'MBA502', 4, 1, 12, 'Compulsory'),
(58, 'Corporate Finance', 'MBA503', 3, 2, 12, 'Compulsory'),
(59, 'Business Analytics', 'MBA504', 3, 2, 12, 'Optional'),
(60, 'International Business', 'MBA505', 3, 2, 12, 'Elective'),
(61, 'Classical Mechanics', 'PHY101', 3, 1, 13, 'Compulsory'),
(62, 'Electromagnetism', 'PHY102', 3, 2, 13, 'Compulsory'),
(63, 'Quantum Physics', 'PHY201', 4, 3, 13, 'Compulsory'),
(64, 'Astrophysics', 'PHY301', 3, 5, 13, 'Optional'),
(65, 'Nuclear Physics', 'PHY401', 3, 7, 13, 'Elective'),
(66, 'Organic Chemistry', 'CHM101', 3, 1, 14, 'Compulsory'),
(67, 'Inorganic Chemistry', 'CHM102', 3, 2, 14, 'Compulsory'),
(68, 'Physical Chemistry', 'CHM201', 4, 3, 14, 'Compulsory'),
(69, 'Analytical Chemistry', 'CHM301', 3, 5, 14, 'Optional'),
(70, 'Polymer Chemistry', 'CHM401', 3, 7, 14, 'Elective'),
(71, 'Calculus I', 'MAT101', 3, 1, 15, 'Compulsory'),
(72, 'Linear Algebra', 'MAT102', 3, 2, 15, 'Compulsory'),
(73, 'Differential Equations', 'MAT201', 4, 3, 15, 'Compulsory'),
(74, 'Real Analysis', 'MAT301', 3, 5, 15, 'Optional'),
(75, 'Number Theory', 'MAT401', 3, 7, 15, 'Elective'),
(76, 'Environmental Chemistry', 'ENV501', 4, 1, 16, 'Compulsory'),
(77, 'Ecology and Ecosystems', 'ENV502', 4, 1, 16, 'Compulsory'),
(78, 'Climate Change Studies', 'ENV503', 3, 2, 16, 'Compulsory'),
(79, 'Environmental Impact Assessment', 'ENV504', 3, 2, 16, 'Optional'),
(80, 'Renewable Energy Systems', 'ENV505', 3, 2, 16, 'Elective'),
(81, 'Constitutional Law', 'LAW101', 3, 1, 17, 'Compulsory'),
(82, 'Criminal Law', 'LAW102', 3, 2, 17, 'Compulsory'),
(83, 'Contract Law', 'LAW201', 4, 3, 17, 'Compulsory'),
(84, 'Corporate Law', 'LAW301', 3, 5, 17, 'Optional'),
(85, 'Cyber Law', 'LAW401', 3, 7, 17, 'Elective'),
(86, 'Business Law Fundamentals', 'BLA101', 3, 1, 18, 'Compulsory'),
(87, 'Company Law', 'BLA102', 3, 2, 18, 'Compulsory'),
(88, 'Intellectual Property Law', 'BLA201', 4, 3, 18, 'Compulsory'),
(89, 'Banking Law', 'BLA301', 3, 5, 18, 'Optional'),
(90, 'International Trade Law', 'BLA401', 3, 7, 18, 'Elective'),
(91, 'Criminal Justice System', 'CRJ101', 3, 1, 19, 'Compulsory'),
(92, 'Criminology', 'CRJ102', 3, 2, 19, 'Compulsory'),
(93, 'Forensic Science', 'CRJ201', 4, 3, 19, 'Compulsory'),
(94, 'Juvenile Justice', 'CRJ301', 3, 5, 19, 'Optional'),
(95, 'Cyber Crime', 'CRJ401', 3, 7, 19, 'Elective'),
(96, 'Advanced Constitutional Law', 'LLM501', 4, 1, 20, 'Compulsory'),
(97, 'Legal Research Methodology', 'LLM502', 4, 1, 20, 'Compulsory'),
(98, 'Human Rights Law', 'LLM503', 3, 2, 20, 'Compulsory'),
(99, 'Environmental Law', 'LLM504', 3, 2, 20, 'Optional'),
(100, 'International Criminal Law', 'LLM505', 3, 2, 20, 'Elective'),
(101, 'Drawing Fundamentals', 'FPA101', 3, 1, 21, 'Compulsory'),
(102, 'Color Theory', 'FPA102', 3, 1, 21, 'Compulsory'),
(103, 'Oil Painting', 'FPA201', 4, 2, 21, 'Compulsory'),
(104, 'Watercolor Techniques', 'FPA301', 3, 1, 21, 'Optional'),
(105, 'Digital Art', 'FPA401', 3, 2, 21, 'Elective'),
(106, 'Music Theory', 'FMU101', 3, 1, 22, 'Compulsory'),
(107, 'Vocal Training', 'FMU102', 3, 1, 22, 'Compulsory'),
(108, 'Music History', 'FMU201', 4, 1, 22, 'Compulsory'),
(109, 'Instrumental Music', 'FMU301', 3, 1, 22, 'Optional'),
(110, 'Music Composition', 'FMU401', 3, 7, 22, 'Elective'),
(111, 'Sculpture Fundamentals', 'FSC101', 3, 1, 23, 'Compulsory'),
(112, 'Clay Modeling', 'FSC102', 3, 2, 23, 'Compulsory'),
(113, 'Stone Carving', 'FSC201', 4, 3, 23, 'Compulsory'),
(114, 'Metal Sculpture', 'FSC301', 3, 5, 23, 'Optional'),
(115, 'Installation Art', 'FSC401', 3, 7, 23, 'Elective'),
(116, 'Contemporary Art Theory', 'MFA501', 4, 1, 24, 'Compulsory'),
(117, 'Advanced Studio Practice', 'MFA502', 4, 1, 24, 'Compulsory'),
(118, 'Art Criticism', 'MFA503', 3, 2, 24, 'Compulsory'),
(119, 'Curatorial Studies', 'MFA504', 3, 2, 24, 'Optional'),
(120, 'Digital Media Arts', 'MFA505', 3, 2, 24, 'Elective');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `teacher_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`teacher_id`, `username`, `password`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `dept_id`) VALUES
(1, 'prof.sharma', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Rajesh', 'Kumar', 'Sharma', 'rajesh.sharma@pmc.edu.np', '9841000001', 1),
(2, 'prof.adhikari', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Sita', 'Devi', 'Adhikari', 'sita.adhikari@pmc.edu.np', '9841000002', 1),
(3, 'prof.karki', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Prakash', NULL, 'Karki', 'prakash.karki@pmc.edu.np', '9841000003', 1),
(4, 'prof.neupane', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Laxmi', 'Kumari', 'Neupane', 'laxmi.neupane@pmc.edu.np', '9841000004', 1),
(5, 'prof.bhandari', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Hari', 'Prasad', 'Bhandari', 'hari.bhandari@pmc.edu.np', '9841000005', 1),
(6, 'prof.poudel', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Gopal', NULL, 'Poudel', 'gopal.poudel@pmc.edu.np', '9842000001', 2),
(7, 'prof.timalsina', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Radhika', 'Kumari', 'Timalsina', 'radhika.timalsina@pmc.edu.np', '9842000002', 2),
(8, 'prof.dahal', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Madhav', 'Prasad', 'Dahal', 'madhav.dahal@pmc.edu.np', '9842000003', 2),
(9, 'prof.khanal', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Sunita', NULL, 'Khanal', 'sunita.khanal@pmc.edu.np', '9842000004', 2),
(10, 'Bikash', '$2y$10$u99NjTs1qKL1icYQxeSq1.VfX5KR0WUuTUEblzuoq05L3vy3Pkygm', 'Bikash', 'Man', 'Shrestha', 'bikash.shrestha@pmc.edu.np', '9843000001', 6),
(11, 'Anita', '$2y$10$LtR2LOsfrUBf3RabCrqqW.4KXXNxK5NhyAy232YSrYa48rA7KlUTS', 'Anita', '', 'Maharjan', 'anita.maharjan@pmc.edu.np', '9843000002', 6),
(12, 'prof.joshi', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Suresh', 'Kumar', 'Joshi', 'suresh.joshi@pmc.edu.np', '9843000003', 3),
(13, 'prof.bajracharya', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Nisha', 'Kumari', 'Bajracharya', 'nisha.bajracharya@pmc.edu.np', '9843000004', 3),
(14, 'Dipendra', '$2y$10$v4l0M94rkzq0U5tBc3WW0e.GNjgf/6.CRkaENiGhPl3X4b1v5rhJS', 'Dipendra', 'Bahadur', 'Khadka', 'dipendra.khadka@pmc.edu.np', '9843000005', 6),
(15, 'prof.bhattarai', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Krishna', 'Prasad', 'Bhattarai', 'krishna.bhattarai@pmc.edu.np', '9844000001', 4),
(16, 'prof.pokharel', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Rita', NULL, 'Pokharel', 'rita.pokharel@pmc.edu.np', '9844000002', 4),
(17, 'prof.gautam', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Mohan', 'Bahadur', 'Gautam', 'mohan.gautam@pmc.edu.np', '9844000003', 4),
(18, 'prof.kafle', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Sabita', 'Devi', 'Kafle', 'sabita.kafle@pmc.edu.np', '9844000004', 4),
(19, 'prof.aryal', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Tika', 'Ram', 'Aryal', 'tika.aryal@pmc.edu.np', '9844000005', 4),
(20, 'prof.thapa', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Prakash', 'Kumar', 'Thapa', 'prakash.thapa@pmc.edu.np', '9845000001', 5),
(21, 'prof.basnet', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Gita', NULL, 'Basnet', 'gita.basnet@pmc.edu.np', '9845000002', 5),
(22, 'prof.subedi', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Ramesh', 'Prasad', 'Subedi', 'ramesh.subedi@pmc.edu.np', '9845000003', 5),
(23, 'prof.baral', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Sushila', 'Kumari', 'Baral', 'sushila.baral@pmc.edu.np', '9845000004', 5),
(24, 'prof.rana', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Ujjwal', 'Bahadur', 'Rana', 'ujjwal.rana@pmc.edu.np', '9846000001', 6),
(25, 'prof.shakya', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Mina', NULL, 'Shakya', 'mina.shakya@pmc.edu.np', '9846000002', 6),
(26, 'prof.tuladhar', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Rajan', 'Kumar', 'Tuladhar', 'rajan.tuladhar@pmc.edu.np', '9846000003', 6),
(27, 'prof.vajracharya', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Sunita', 'Maya', 'Vajracharya', 'sunita.vajracharya@pmc.edu.np', '9846000004', 6),
(28, 'prof.ghimire', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Yubaraj', NULL, 'Ghimire', 'yubaraj.ghimire@pmc.edu.np', '9847000001', 1),
(29, 'prof.panthi', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Indira', 'Kumari', 'Panthi', 'indira.panthi@pmc.edu.np', '9847000002', 2),
(30, 'Baburam', '$2y$10$/r4JX8YGau6AuAS6zuQBYeVLtuUoWUFlyW3RGBWnHE654ksRBv8Y6', 'Baburam', 'KC', 'Khatri', 'baburam.kc@pmc.edu.np', '9847000003', 6),
(31, 'prof.ojha', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Reeta', NULL, 'Ojha', 'reeta.ojha@pmc.edu.np', '9847000004', 4),
(32, 'prof.chapagain', '$2y$10$g1ndMQqaxqHSfFA/jgf0Y.ACN37HToSvFFZnaCjcVPhi1TYBg2WSq', 'Kamal', 'Prasad', 'Chapagain', 'kamal.chapagain@pmc.edu.np', '9847000005', 5),
(33, 'Anjana', '$2y$10$7OkToHAuTq6pfJsOyBLA3.8IssIqXm8B8ne/moi8wKA1UNQyBouNa', 'Anjana', '', 'Silwal', 'anjana.silwal@pmc.edu.np', '9847000006', 6);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_allocation`
--

CREATE TABLE `teacher_allocation` (
  `allocation_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_allocation`
--

INSERT INTO `teacher_allocation` (`allocation_id`, `teacher_id`, `subject_id`, `batch_id`, `session_id`, `section`) VALUES
(1, 11, 106, NULL, NULL, 'A'),
(2, 33, 107, NULL, NULL, 'A'),
(3, 30, 108, NULL, NULL, 'A'),
(4, 10, 109, NULL, NULL, 'A');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`batch_id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `dept_name` (`dept_name`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_id`);

--
-- Indexes for table `exam_subject`
--
ALTER TABLE `exam_subject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `result`
--
ALTER TABLE `result`
  ADD PRIMARY KEY (`result_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`,`exam_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `roll_number` (`roll_number`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `teacher_allocation`
--
ALTER TABLE `teacher_allocation`
  ADD PRIMARY KEY (`allocation_id`),
  ADD UNIQUE KEY `unique_teacher_subject_section_session` (`teacher_id`,`subject_id`,`section`,`session_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `idx_section` (`section`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_subject`
--
ALTER TABLE `exam_subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `result`
--
ALTER TABLE `result`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `teacher_allocation`
--
ALTER TABLE `teacher_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_subject`
--
ALTER TABLE `exam_subject`
  ADD CONSTRAINT `exam_subject_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `exam_subject_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `result`
--
ALTER TABLE `result`
  ADD CONSTRAINT `result_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `result_ibfk_3` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batch` (`batch_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
