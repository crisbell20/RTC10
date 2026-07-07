-- ============================================================================
-- Merge User-Batch Tables Migration
-- ============================================================================
-- This migration simplifies the batch structure by:
-- 1. Adding User_ID directly to tbl_batch (one user per batch)
-- 2. Migrating existing tbl_user_batch data to tbl_batch
-- 3. Dropping the tbl_user_batch table
-- 
-- WARNING: This changes the relationship from many-to-many to one-to-many
-- If a batch has multiple users, only the first user will be kept
-- ============================================================================

-- Step 1: Add User_ID column to tbl_batch if it doesn't exist
ALTER TABLE `tbl_batch` 
ADD COLUMN `User_ID` int(11) DEFAULT NULL AFTER `Section_ID`,
ADD COLUMN `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' AFTER `Date_Ended`,
ADD COLUMN `Date_Enrolled` datetime DEFAULT CURRENT_TIMESTAMP AFTER `Status`;

-- Step 2: Add foreign key constraint
ALTER TABLE `tbl_batch`
ADD CONSTRAINT `fk_batch_user` FOREIGN KEY (`User_ID`) REFERENCES `tbl_user`(`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 3: Migrate data from tbl_user_batch to tbl_batch
-- For each batch, take the first enrolled user as the batch owner
UPDATE `tbl_batch` b
INNER JOIN (
    SELECT Batch_ID, MIN(User_ID) as User_ID, MIN(Date_Enrolled) as Date_Enrolled, Status
    FROM `tbl_user_batch`
    GROUP BY Batch_ID
) ub ON b.Batch_ID = ub.Batch_ID
SET b.User_ID = ub.User_ID,
    b.Date_Enrolled = ub.Date_Enrolled,
    b.Status = ub.Status;

-- Step 4: Drop the tbl_user_batch table
DROP TABLE IF EXISTS `tbl_user_batch`;

-- Step 5: Add index for better query performance
ALTER TABLE `tbl_batch`
ADD INDEX `idx_batch_user` (`User_ID`);

-- ============================================================================
-- Migration Complete
-- ============================================================================
