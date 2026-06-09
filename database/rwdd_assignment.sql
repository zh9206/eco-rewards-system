-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 07:22 AM
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
-- Database: `rwdd_assignment`
--
CREATE DATABASE IF NOT EXISTS `rwdd_assignment` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `rwdd_assignment`;

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` varchar(11) NOT NULL,
  `Title` varchar(30) NOT NULL,
  `Description` text NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `AdminID` varchar(11) NOT NULL,
  `Status` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`AnnouncementID`, `Title`, `Description`, `StartDate`, `EndDate`, `AdminID`, `Status`) VALUES
('A001', 'Raya Special: Points x2', 'Every Challenge will be rewarded with double points!!', '2026-03-13', '2026-03-24', 'U002', 'Expired'),
('A002', 'Server Preventive Maintenance', 'There will be service disruptions to the following:\r\n1. Rewards Shop\r\n2. Badges', '2026-05-10', '2026-05-11', 'U002', 'Scheduled'),
('A003', 'Repair webpage', 'Fix the problem', '2026-03-22', '2026-03-31', 'U002', 'Active'),
('A005', 'Aprilfool', 'Randomly deduct or add 0-100 points for every users', '2026-03-05', '2026-04-03', 'U002', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `challenge`
--

CREATE TABLE `challenge` (
  `ChallengeID` varchar(11) NOT NULL,
  `Title` varchar(50) NOT NULL,
  `Description` text NOT NULL,
  `Points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenge`
--

INSERT INTO `challenge` (`ChallengeID`, `Title`, `Description`, `Points`) VALUES
('C001', 'Took Public Transport', 'Took public transportation such as LRT, MRT,bus to APU', 10),
('C002', 'Used Reuseable Bag', 'Bring and use reuseable bag to school', 5),
('C003', 'Plant A Tree', 'Planting a tree in your housing area.', 60),
('C004', 'Garbage Collector', 'Collect A bag of garbage.', 5),
('C005', 'Meat-Free Day', 'Eat vegetarian meals for one day', 20),
('C006', 'Attend Cleanup Campaign', 'Join a clean-up event', 120);

-- --------------------------------------------------------

--
-- Table structure for table `challenge_submission`
--

CREATE TABLE `challenge_submission` (
  `SubmissionID` varchar(11) NOT NULL,
  `Submission_Date` datetime NOT NULL,
  `Proof` varchar(255) NOT NULL,
  `ProofHash` varchar(255) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `FlagType` varchar(50) NOT NULL DEFAULT 'normal',
  `StudentID` varchar(11) NOT NULL,
  `ChallengeID` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenge_submission`
--

INSERT INTO `challenge_submission` (`SubmissionID`, `Submission_Date`, `Proof`, `ProofHash`, `Status`, `FlagType`, `StudentID`, `ChallengeID`) VALUES
('S001', '2026-03-28 03:44:36', 'proof/1774640676_9000.jpeg', 'c75914556002744c4a478f8a2532c57211e5c7a814e7ee5eee0ec83723a9346d', 'Rejected', 'duplicate', 'TP001', 'C001'),
('S002', '2026-03-28 03:45:37', 'proof/1774640737_4994.jpeg', 'c75914556002744c4a478f8a2532c57211e5c7a814e7ee5eee0ec83723a9346d', 'Approved', 'normal', 'TP001', 'C001'),
('S003', '2026-03-28 03:50:18', 'proof/1774641018_9961.jpeg', 'cd1677f140d2d8f641e56031bb9f9265b4e476c13d986f953ad6deda3963f73b', 'Approved', 'normal', 'TP002', 'C003'),
('S004', '2026-03-28 03:50:50', 'proof/1774641050_9898.jpeg', '5d07b188d4c39e4b538f9b6ae13c48e7a4201e47b21f27beb2a7c7bc32a4e00b', 'Rejected', 'normal', 'TP002', 'C002'),
('S005', '2026-03-28 03:51:30', 'proof/1774641090_3581.jpeg', 'e96ea88e5264d0cd82ee64e35c5324fb0e875b6e9c67070d7a3e92e036aed9c3', 'Rejected', 'suspicious', 'TP004', 'C003'),
('S006', '2026-03-28 03:51:59', 'proof/1774641119_2230.jpeg', 'e96ea88e5264d0cd82ee64e35c5324fb0e875b6e9c67070d7a3e92e036aed9c3', 'Rejected', 'suspicious', 'TP005', 'C003'),
('S007', '2026-03-28 03:54:43', 'proof/1774641283_4036.jpeg', '4093377af7e3faf51e9b67295d47ffb75800d09fcef63e88ffb3732db5d01ad4', 'Rejected', 'duplicate', 'TP006', 'C002'),
('S008', '2026-03-28 03:59:05', 'proof/1774641545_1052.jpeg', '9b178ee4d3c470900e073c1e7c476339373746f78f2fb116d65a8a8dccba7a28', 'Approved', 'normal', 'TP001', 'C002'),
('S009', '2026-03-28 04:00:18', 'proof/1774641618_8930.jpeg', '0fc1b88e0c871147ee3858b7f6ffef01a9d4926d62fd804f436920fafd390a7c', 'Approved', 'normal', 'TP001', 'C003'),
('S010', '2026-03-28 04:05:46', 'proof/1774641946_2182.jpeg', '6a63bebdfc7016f497e649ed551fa58f38c25916b128c0ebd9ce4a7590bedfaa', 'Approved', 'normal', 'TP002', 'C006'),
('S011', '2026-03-28 16:34:42', 'proof/1774686882_2808.jpg', 'd7df71b8a0a80308a8bcd3e93fe4e922f8ea6bed11a1737f948617297c0c0ddc', 'Approved', 'normal', 'TP001', 'C006'),
('S012', '2026-03-28 16:54:44', 'proof/1774688084_8660.jpeg', '4093377af7e3faf51e9b67295d47ffb75800d09fcef63e88ffb3732db5d01ad4', 'Rejected', 'duplicate', 'TP006', 'C002');

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

CREATE TABLE `reward` (
  `RewardID` varchar(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Validity` int(11) DEFAULT NULL,
  `Points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward`
--

INSERT INTO `reward` (`RewardID`, `Title`, `Type`, `Validity`, `Points`) VALUES
('R001', 'Subway Voucher 10% Offer', 'Voucher', 30, 600),
('R002', 'Badge', 'Merchandise', 0, 1000),
('R003', 'Touch N Go RM1', 'Merchandise', 0, 110);

-- --------------------------------------------------------

--
-- Table structure for table `reward_redemption`
--

CREATE TABLE `reward_redemption` (
  `RedemptionID` varchar(11) NOT NULL,
  `Redeem_Date` date NOT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `Status` varchar(50) NOT NULL,
  `StudentID` varchar(50) NOT NULL,
  `RewardID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward_redemption`
--

INSERT INTO `reward_redemption` (`RedemptionID`, `Redeem_Date`, `Expiry_Date`, `Status`, `StudentID`, `RewardID`) VALUES
('D001', '2026-03-28', NULL, 'Active', 'TP002', 'R003');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `RoleID` varchar(11) NOT NULL,
  `Role_Name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`RoleID`, `Role_Name`) VALUES
('R01', 'student'),
('R02', 'moderator'),
('R03', 'administrator');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` varchar(50) NOT NULL,
  `Username` varchar(30) NOT NULL,
  `Gender` varchar(50) NOT NULL,
  `Passwords` varchar(255) NOT NULL,
  `DOB` date NOT NULL,
  `Contact` varchar(20) NOT NULL,
  `RoleID` varchar(50) NOT NULL,
  `LastActive` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `Gender`, `Passwords`, `DOB`, `Contact`, `RoleID`, `LastActive`) VALUES
('TP001', 'Justin', 'Male', '$2y$10$5qaLcTf9R/KNHrf8pu87Ku8Aq2LQQwaRorsiTlFNyJiFfCnWM7JhS', '2007-03-09', '0102526269', 'R01', '2026-03-29 16:08:58'),
('TP002', 'Sin Yue', 'Female', '$2y$10$zP/h4OmYCFectOtEs4AvbeozysamGvQiEf3umMrJDGB4yRcOH2OqO', '2006-06-06', '0100011012', 'R01', '2026-03-28 16:25:52'),
('TP003', 'Bobby', 'Male', '$2y$10$2HxFFaK333aY5rRa/FW/GOYy3JCFS9RuWviCB7HyVLP8mg4hHrz9a', '2006-09-09', '0133468666', 'R01', '2026-03-27 22:19:51'),
('TP004', 'Eunice', 'Female', '$2y$10$UoQV8uC6ezAOl/vkGlUjous.r5t0xT11HqNFVBxD8toprmPzDosk2', '2006-05-03', '0123456789', 'R01', '2026-03-28 03:51:41'),
('TP005', 'James', 'Male', '$2y$10$7CwHzpq5/D1xmVPXnfo/pum9dRzQSuvsiQ8PIJb24Z/YkJwLuAys2', '2005-07-25', '0102526267', 'R01', '2026-03-28 03:51:49'),
('TP006', 'Lim', 'Male', '$2y$10$wXnlsMLSSGL6j5qFJz64LOq5wIp3FSVIZq0FMfGsh0//6rLlaLL4W', '2007-03-27', '0133468888', 'R01', '2026-03-28 16:54:34'),
('U001', 'Sam', 'Male', '$2y$10$.UYPQoMvX9Y8i1.TziCx.uu5hAKCZXKvQZtoCPu0RAvAC05z1fMQy', '1980-07-07', '0134567890', 'R02', '2026-03-29 15:57:46'),
('U002', 'Amy', 'Female', '$2y$10$D4QeAqGzRByrX4qcrBLunee3mJR3ggUETb7KCxHE0upzL.2jjlA36', '1980-07-03', '0134567890', 'R03', '2026-03-28 15:15:38'),
('U003', 'Gan', 'Male', '$2y$10$mM9KsBvA5ybBTQRChqai6.uPUqqNs8sAjk4YVEQAkbB3Ju1H.hGAK', '1986-06-26', '0133468777', 'R02', '2026-03-27 09:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `verification`
--

CREATE TABLE `verification` (
  `ModeratorID` varchar(11) NOT NULL,
  `SubmissionID` varchar(11) NOT NULL,
  `Review` varchar(112) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification`
--

INSERT INTO `verification` (`ModeratorID`, `SubmissionID`, `Review`) VALUES
('U001', 'S001', 'Duplicate of S002'),
('U001', 'S002', 'Approved. This is the latest valid submission.'),
('U001', 'S003', '-'),
('U001', 'S004', 'The uploaded photo is unclear.'),
('U001', 'S005', 'Duplicate image content detected.'),
('U001', 'S006', 'Duplicate image content detected.'),
('U001', 'S007', 'Rejected: Duplicate content. Same as S012.'),
('U001', 'S008', '-'),
('U001', 'S009', '-'),
('U001', 'S010', '-'),
('U001', 'S011', '-'),
('U001', 'S012', 'Rejected: Duplicate content. Same as S007.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`AnnouncementID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `challenge`
--
ALTER TABLE `challenge`
  ADD PRIMARY KEY (`ChallengeID`);

--
-- Indexes for table `challenge_submission`
--
ALTER TABLE `challenge_submission`
  ADD PRIMARY KEY (`SubmissionID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `ChallengeID` (`ChallengeID`);

--
-- Indexes for table `reward`
--
ALTER TABLE `reward`
  ADD PRIMARY KEY (`RewardID`);

--
-- Indexes for table `reward_redemption`
--
ALTER TABLE `reward_redemption`
  ADD PRIMARY KEY (`RedemptionID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `RewardID` (`RewardID`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`RoleID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD KEY `RoleID` (`RoleID`);

--
-- Indexes for table `verification`
--
ALTER TABLE `verification`
  ADD PRIMARY KEY (`ModeratorID`,`SubmissionID`),
  ADD KEY `SubmissionID` (`SubmissionID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `challenge_submission`
--
ALTER TABLE `challenge_submission`
  ADD CONSTRAINT `challenge_submission_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `challenge_submission_ibfk_2` FOREIGN KEY (`ChallengeID`) REFERENCES `challenge` (`ChallengeID`);

--
-- Constraints for table `reward_redemption`
--
ALTER TABLE `reward_redemption`
  ADD CONSTRAINT `reward_redemption_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `reward_redemption_ibfk_2` FOREIGN KEY (`RewardID`) REFERENCES `reward` (`RewardID`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `role` (`RoleID`);

--
-- Constraints for table `verification`
--
ALTER TABLE `verification`
  ADD CONSTRAINT `verification_ibfk_1` FOREIGN KEY (`ModeratorID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `verification_ibfk_2` FOREIGN KEY (`SubmissionID`) REFERENCES `challenge_submission` (`SubmissionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
