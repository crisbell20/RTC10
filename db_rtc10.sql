-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 02:41 AM
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
-- Database: `db_rtc`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_academic_section`
--

CREATE TABLE `tbl_academic_section` (
  `Section_ID` int(11) NOT NULL,
  `Batch_ID` int(11) NOT NULL,
  `Section_Name` varchar(100) NOT NULL,
  `Capacity` int(11) NOT NULL DEFAULT 40
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_answer`
--

CREATE TABLE `tbl_answer` (
  `Answer_ID` int(11) NOT NULL,
  `Result_ID` int(11) NOT NULL,
  `Session_ID` int(11) NOT NULL,
  `Question_ID` int(11) NOT NULL,
  `Choice_ID` int(11) DEFAULT NULL COMMENT 'NULL for open-ended questions',
  `Answer_Correct` text DEFAULT NULL COMMENT 'Free-text answer for non-choice types',
  `Is_Correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_audit_log`
--

CREATE TABLE `tbl_audit_log` (
  `Log_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Outcome` varchar(100) NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_batch`
--

CREATE TABLE `tbl_batch` (
  `Batch_ID` int(11) NOT NULL,
  `Course_ID` int(11) NOT NULL,
  `Section_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Batch_Name` varchar(150) NOT NULL,
  `Date_Started` date DEFAULT NULL,
  `Date_Ended` date DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Date_Enrolled` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_batch`
-- Links examinees to batches for enrollment
--

CREATE TABLE `tbl_user_batch` (
  `User_Batch_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) NOT NULL,
  `Batch_ID` int(11) NOT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Date_Enrolled` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`User_Batch_ID`),
  UNIQUE KEY `uq_user_batch` (`User_ID`,`Batch_ID`),
  KEY `idx_user_batch_user` (`User_ID`),
  KEY `idx_user_batch_batch` (`Batch_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cheating_incident`
--

CREATE TABLE `tbl_cheating_incident` (
  `Incident_ID` int(11) NOT NULL,
  `Session_ID` int(11) NOT NULL,
  `Reason` text NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Open','Reviewed','Dismissed','Escalated') NOT NULL DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cheating_log`
--

CREATE TABLE `tbl_cheating_log` (
  `Cheating_Log_ID` int(11) NOT NULL,
  `Session_ID` int(11) NOT NULL,
  `Type` varchar(100) NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_choice`
--

CREATE TABLE `tbl_choice` (
  `Choice_ID` int(11) NOT NULL,
  `Question_ID` int(11) NOT NULL,
  `Choice_Text` text NOT NULL,
  `Is_Correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_course`
--

CREATE TABLE `tbl_course` (
  `Course_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL COMMENT 'Creator',
  `Course_Name` varchar(200) NOT NULL,
  `Details` text DEFAULT NULL,
  `Duration` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam`
--

CREATE TABLE `tbl_exam` (
  `Exam_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL COMMENT 'Created by',
  `Subject_ID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `Schedule_Date` datetime DEFAULT NULL,
  `Deadline` datetime DEFAULT NULL COMMENT 'Deadline to start the exam',
  `Duration` int(11) DEFAULT NULL COMMENT 'Total exam duration in minutes',
  `Passing_Score` decimal(5,2) DEFAULT NULL,
  `Status` enum('Draft','Published','Closed') NOT NULL DEFAULT 'Draft',
  `Is_Randomized` tinyint(1) NOT NULL DEFAULT 0,
  `Time_Limit` int(11) DEFAULT NULL COMMENT 'Per-question time limit in seconds',
  `Exam_Code` varchar(12) DEFAULT NULL,
  `Exam_Code_Generated_At` datetime DEFAULT NULL,
  `Exam_Code_Reset_Count` int(11) NOT NULL DEFAULT 0,
  `Is_Archived` tinyint(1) NOT NULL DEFAULT 0,
  `Archived_At` datetime DEFAULT NULL,
  `Allow_Response_Review` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam_code_attempt`
--

CREATE TABLE `tbl_exam_code_attempt` (
  `Attempt_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Exam_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `IP_Address` varchar(45) DEFAULT NULL,
  `Success` tinyint(1) NOT NULL DEFAULT 0,
  `Attempted_At` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Attempt_ID`),
  KEY `idx_attempt_lookup` (`Exam_ID`,`User_ID`,`Attempted_At`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam_batch`
-- Links exams to batches for deployment
--

CREATE TABLE `tbl_exam_batch` (
  `Exam_Batch_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Exam_ID` int(11) NOT NULL,
  `Batch_ID` int(11) NOT NULL,
  `Date_Assigned` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Exam_Batch_ID`),
  UNIQUE KEY `uq_exam_batch` (`Exam_ID`, `Batch_ID`),
  KEY `idx_exam_batch_batch` (`Batch_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam_question`
-- Links exams to questions from the question bank
--

CREATE TABLE `tbl_exam_question` (
  `Exam_Question_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Exam_ID` int(11) NOT NULL,
  `Question_ID` int(11) NOT NULL,
  `Question_Order` int(11) NOT NULL DEFAULT 0,
  `Date_Added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Exam_Question_ID`),
  UNIQUE KEY `uq_exam_question` (`Exam_ID`, `Question_ID`),
  KEY `idx_exam_question_order` (`Exam_ID`, `Question_Order`),
  KEY `fk_eq_question` (`Question_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_exam_session`
--

CREATE TABLE `tbl_exam_session` (
  `Session_ID` int(11) NOT NULL,
  `Exam_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL COMMENT 'Student taking the exam',
  `Time_Started` datetime NOT NULL DEFAULT current_timestamp(),
  `Time_Ended` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notification`
--

CREATE TABLE `tbl_notification` (
  `Notification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Message` text NOT NULL,
  `Type` varchar(80) NOT NULL,
  `Date_Sent` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Sent','Failed') NOT NULL DEFAULT 'Sent',
  `Target_Role` varchar(100) DEFAULT NULL,
  `Is_Read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_permission`
--

CREATE TABLE `tbl_permission` (
  `Permission_ID` int(11) NOT NULL,
  `Permission_Name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_question_bank`
--

CREATE TABLE `tbl_question_bank` (
  `Question_ID` int(11) NOT NULL,
  `Subject_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL COMMENT 'Created by',
  `Question_Text` text NOT NULL,
  `Question_Type` enum('Multiple Choice') NOT NULL DEFAULT 'Multiple Choice',
  `Added_Date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_report`
--

CREATE TABLE `tbl_report` (
  `Report_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Report_Type` varchar(100) NOT NULL,
  `Generated_By` varchar(200) NOT NULL,
  `Generated_On` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_result`
--

CREATE TABLE `tbl_result` (
  `Result_ID` int(11) NOT NULL,
  `Session_ID` int(11) NOT NULL,
  `Score` decimal(7,2) NOT NULL DEFAULT 0.00,
  `Percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `Remarks` varchar(200) DEFAULT NULL,
  `Submission_Date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role`
--

CREATE TABLE `tbl_role` (
  `Role_ID` int(11) NOT NULL,
  `Role_Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role_permission`
--

CREATE TABLE `tbl_role_permission` (
  `Role_ID` int(11) NOT NULL,
  `Permission_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subject`
--

CREATE TABLE `tbl_subject` (
  `Subject_ID` int(11) NOT NULL,
  `Course_ID` int(11) NOT NULL,
  `Subject_Name` varchar(200) NOT NULL,
  `Subject_Code` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `User_ID` int(11) NOT NULL,
  `Role_ID` int(11) NOT NULL,
  `Fullname` varchar(200) NOT NULL,
  `Email` varchar(200) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password_Hash` varchar(255) NOT NULL,
  `Academic_Number` varchar(50) DEFAULT NULL,
  `Must_Change_Password` tinyint(1) NOT NULL DEFAULT 0,
  `Date_Created` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_academic_section`
--
ALTER TABLE `tbl_academic_section`
  ADD PRIMARY KEY (`Section_ID`),
  ADD KEY `idx_section_batch` (`Batch_ID`);

--
-- Indexes for table `tbl_answer`
--
ALTER TABLE `tbl_answer`
  ADD PRIMARY KEY (`Answer_ID`),
  ADD KEY `idx_answer_result` (`Result_ID`),
  ADD KEY `idx_answer_session` (`Session_ID`),
  ADD KEY `idx_answer_question` (`Question_ID`),
  ADD KEY `idx_answer_choice` (`Choice_ID`);

--
-- Indexes for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  ADD PRIMARY KEY (`Log_ID`),
  ADD KEY `idx_audit_user` (`User_ID`);

--
-- Indexes for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  ADD PRIMARY KEY (`Batch_ID`),
  ADD KEY `idx_batch_course` (`Course_ID`),
  ADD KEY `idx_batch_user` (`User_ID`),
  ADD KEY `idx_batch_section` (`Section_ID`);

--
-- Indexes for table `tbl_cheating_incident`
--
ALTER TABLE `tbl_cheating_incident`
  ADD PRIMARY KEY (`Incident_ID`),
  ADD KEY `idx_cinc_session` (`Session_ID`);

--
-- Indexes for table `tbl_cheating_log`
--
ALTER TABLE `tbl_cheating_log`
  ADD PRIMARY KEY (`Cheating_Log_ID`),
  ADD KEY `idx_clog_session` (`Session_ID`);

--
-- Indexes for table `tbl_choice`
--
ALTER TABLE `tbl_choice`
  ADD PRIMARY KEY (`Choice_ID`),
  ADD KEY `idx_choice_question` (`Question_ID`);

--
-- Indexes for table `tbl_course`
--
ALTER TABLE `tbl_course`
  ADD PRIMARY KEY (`Course_ID`),
  ADD KEY `idx_course_user` (`User_ID`);

--
-- Indexes for table `tbl_exam`
--
ALTER TABLE `tbl_exam`
  ADD PRIMARY KEY (`Exam_ID`),
  ADD KEY `idx_exam_user` (`User_ID`),
  ADD KEY `idx_exam_subject` (`Subject_ID`);

--
-- Indexes for table `tbl_exam_session`
--
ALTER TABLE `tbl_exam_session`
  ADD PRIMARY KEY (`Session_ID`),
  ADD KEY `idx_session_exam` (`Exam_ID`),
  ADD KEY `idx_session_user` (`User_ID`);

--
-- Indexes for table `tbl_notification`
--
ALTER TABLE `tbl_notification`
  ADD PRIMARY KEY (`Notification_ID`),
  ADD KEY `idx_notif_user` (`User_ID`);

--
-- Indexes for table `tbl_permission`
--
ALTER TABLE `tbl_permission`
  ADD PRIMARY KEY (`Permission_ID`),
  ADD UNIQUE KEY `uq_permission_name` (`Permission_Name`);

--
-- Indexes for table `tbl_question_bank`
--
ALTER TABLE `tbl_question_bank`
  ADD PRIMARY KEY (`Question_ID`),
  ADD KEY `idx_qbank_subject` (`Subject_ID`),
  ADD KEY `idx_qbank_user` (`User_ID`);

--
-- Indexes for table `tbl_report`
--
ALTER TABLE `tbl_report`
  ADD PRIMARY KEY (`Report_ID`),
  ADD KEY `idx_report_user` (`User_ID`);

--
-- Indexes for table `tbl_result`
--
ALTER TABLE `tbl_result`
  ADD PRIMARY KEY (`Result_ID`),
  ADD UNIQUE KEY `uq_result_session` (`Session_ID`);

--
-- Indexes for table `tbl_role`
--
ALTER TABLE `tbl_role`
  ADD PRIMARY KEY (`Role_ID`),
  ADD UNIQUE KEY `uq_role_name` (`Role_Name`);

--
-- Indexes for table `tbl_role_permission`
--
ALTER TABLE `tbl_role_permission`
  ADD PRIMARY KEY (`Role_ID`,`Permission_ID`),
  ADD KEY `fk_rp_permission` (`Permission_ID`);

--
-- Indexes for table `tbl_subject`
--
ALTER TABLE `tbl_subject`
  ADD PRIMARY KEY (`Subject_ID`),
  ADD UNIQUE KEY `uq_subject_code` (`Subject_Code`),
  ADD KEY `idx_subject_course` (`Course_ID`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `uq_user_email` (`Email`),
  ADD UNIQUE KEY `uq_user_username` (`Username`),
  ADD KEY `fk_user_role` (`Role_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_academic_section`
--
ALTER TABLE `tbl_academic_section`
  MODIFY `Section_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_answer`
--
ALTER TABLE `tbl_answer`
  MODIFY `Answer_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  MODIFY `Log_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  MODIFY `Batch_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cheating_incident`
--
ALTER TABLE `tbl_cheating_incident`
  MODIFY `Incident_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_cheating_log`
--
ALTER TABLE `tbl_cheating_log`
  MODIFY `Cheating_Log_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_choice`
--
ALTER TABLE `tbl_choice`
  MODIFY `Choice_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_course`
--
ALTER TABLE `tbl_course`
  MODIFY `Course_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_exam`
--
ALTER TABLE `tbl_exam`
  MODIFY `Exam_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_exam_session`
--
ALTER TABLE `tbl_exam_session`
  MODIFY `Session_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_notification`
--
ALTER TABLE `tbl_notification`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_permission`
--
ALTER TABLE `tbl_permission`
  MODIFY `Permission_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_question_bank`
--
ALTER TABLE `tbl_question_bank`
  MODIFY `Question_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_report`
--
ALTER TABLE `tbl_report`
  MODIFY `Report_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_result`
--
ALTER TABLE `tbl_result`
  MODIFY `Result_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_role`
--
ALTER TABLE `tbl_role`
  MODIFY `Role_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_subject`
--
ALTER TABLE `tbl_subject`
  MODIFY `Subject_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_user_batch`
--
ALTER TABLE `tbl_user_batch`
  MODIFY `User_Batch_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_academic_section`
--
ALTER TABLE `tbl_academic_section`
  ADD CONSTRAINT `fk_section_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `tbl_batch` (`Batch_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_answer`
--
ALTER TABLE `tbl_answer`
  ADD CONSTRAINT `fk_answer_choice` FOREIGN KEY (`Choice_ID`) REFERENCES `tbl_choice` (`Choice_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`Question_ID`) REFERENCES `tbl_question_bank` (`Question_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_result` FOREIGN KEY (`Result_ID`) REFERENCES `tbl_result` (`Result_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_session` FOREIGN KEY (`Session_ID`) REFERENCES `tbl_exam_session` (`Session_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_batch`
--
ALTER TABLE `tbl_batch`
  ADD CONSTRAINT `fk_batch_course` FOREIGN KEY (`Course_ID`) REFERENCES `tbl_course` (`Course_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_batch_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_batch_section` FOREIGN KEY (`Section_ID`) REFERENCES `tbl_academic_section` (`Section_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tbl_cheating_incident`
--
ALTER TABLE `tbl_cheating_incident`
  ADD CONSTRAINT `fk_cinc_session` FOREIGN KEY (`Session_ID`) REFERENCES `tbl_exam_session` (`Session_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_cheating_log`
--
ALTER TABLE `tbl_cheating_log`
  ADD CONSTRAINT `fk_clog_session` FOREIGN KEY (`Session_ID`) REFERENCES `tbl_exam_session` (`Session_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_choice`
--
ALTER TABLE `tbl_choice`
  ADD CONSTRAINT `fk_choice_question` FOREIGN KEY (`Question_ID`) REFERENCES `tbl_question_bank` (`Question_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_course`
--
ALTER TABLE `tbl_course`
  ADD CONSTRAINT `fk_course_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_exam`
--
ALTER TABLE `tbl_exam`
  ADD CONSTRAINT `fk_exam_subject` FOREIGN KEY (`Subject_ID`) REFERENCES `tbl_subject` (`Subject_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_exam_batch`
--
ALTER TABLE `tbl_exam_batch`
  ADD CONSTRAINT `fk_eb_exam` FOREIGN KEY (`Exam_ID`) REFERENCES `tbl_exam` (`Exam_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eb_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `tbl_batch` (`Batch_ID`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_user_batch`
--
ALTER TABLE `tbl_user_batch`
  ADD CONSTRAINT `fk_ub_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `tbl_batch` (`Batch_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_exam_question`
--
ALTER TABLE `tbl_exam_question`
  ADD CONSTRAINT `fk_eq_exam` FOREIGN KEY (`Exam_ID`) REFERENCES `tbl_exam` (`Exam_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eq_question` FOREIGN KEY (`Question_ID`) REFERENCES `tbl_question_bank` (`Question_ID`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_exam_session`
--
ALTER TABLE `tbl_exam_session`
  ADD CONSTRAINT `fk_session_exam` FOREIGN KEY (`Exam_ID`) REFERENCES `tbl_exam` (`Exam_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_notification`
--
ALTER TABLE `tbl_notification`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_question_bank`
--
ALTER TABLE `tbl_question_bank`
  ADD CONSTRAINT `fk_qbank_subject` FOREIGN KEY (`Subject_ID`) REFERENCES `tbl_subject` (`Subject_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_qbank_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_report`
--
ALTER TABLE `tbl_report`
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_result`
--
ALTER TABLE `tbl_result`
  ADD CONSTRAINT `fk_result_session` FOREIGN KEY (`Session_ID`) REFERENCES `tbl_exam_session` (`Session_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_role_permission`
--
ALTER TABLE `tbl_role_permission`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`Permission_ID`) REFERENCES `tbl_permission` (`Permission_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`Role_ID`) REFERENCES `tbl_role` (`Role_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_subject`
--
ALTER TABLE `tbl_subject`
  ADD CONSTRAINT `fk_subject_course` FOREIGN KEY (`Course_ID`) REFERENCES `tbl_course` (`Course_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`Role_ID`) REFERENCES `tbl_role` (`Role_ID`) ON UPDATE CASCADE;

--
-- Dumping data for table `tbl_role`
--
INSERT INTO `tbl_role` (`Role_ID`, `Role_Name`) VALUES
(1, 'Admin'),
(2, 'CCMD'),
(3, 'Examinee');

--
-- Dumping data for table `tbl_user`
--
INSERT INTO `tbl_user` (`User_ID`, `Role_ID`, `Fullname`, `Email`, `Username`, `Password_Hash`, `Academic_Number`, `Date_Created`, `Status`) VALUES
(1, 1, 'Administrator', 'admin@rtc.com', 'admin', '$2y$10$OVma0X9gI62E8DEj7WsGqezdWhPrEI0K0mPiEUa0Zqv2Uy2Fyw5mS', NULL, NOW(), 'Active'),
(2, 2, 'CCMD Officer', 'ccmd@rtc.com', 'ccmd', '$2y$10$OVma0X9gI62E8DEj7WsGqezdWhPrEI0K0mPiEUa0Zqv2Uy2Fyw5mS', NULL, NOW(), 'Active'),
(3, 3, 'Examinee User', 'examinee@rtc.com', 'examinee', '$2y$10$OVma0X9gI62E8DEj7WsGqezdWhPrEI0K0mPiEUa0Zqv2Uy2Fyw5mS', NULL, NOW(), 'Active');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
