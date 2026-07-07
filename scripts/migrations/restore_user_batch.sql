-- ============================================================================
-- Restore tbl_user_batch Migration
-- ============================================================================
-- Restores many-to-many user-batch enrollment via junction table.
-- Migrates existing enrollments from tbl_batch.User_ID into tbl_user_batch.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tbl_user_batch` (
  `User_Batch_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) NOT NULL,
  `Batch_ID` int(11) NOT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `Date_Enrolled` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`User_Batch_ID`),
  UNIQUE KEY `uq_user_batch` (`User_ID`,`Batch_ID`),
  KEY `idx_user_batch_user` (`User_ID`),
  KEY `idx_user_batch_batch` (`Batch_ID`),
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ub_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `tbl_batch` (`Batch_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing enrollments from tbl_batch.User_ID
INSERT INTO `tbl_user_batch` (`User_ID`, `Batch_ID`, `Status`, `Date_Enrolled`)
SELECT `User_ID`, `Batch_ID`, `Status`, `Date_Enrolled`
FROM `tbl_batch`
WHERE `User_ID` IS NOT NULL
ON DUPLICATE KEY UPDATE
  `Status` = VALUES(`Status`),
  `Date_Enrolled` = VALUES(`Date_Enrolled`);

-- Clear enrollment data from tbl_batch (column retained for compatibility)
UPDATE `tbl_batch` SET `User_ID` = NULL WHERE `User_ID` IS NOT NULL;
