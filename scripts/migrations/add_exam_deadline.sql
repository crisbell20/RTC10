-- Add deadline field to tbl_exam
-- This allows setting a due date for when students must START the exam
-- Similar to Google Classroom assignment deadlines

ALTER TABLE `tbl_exam` 
ADD COLUMN `Deadline` datetime DEFAULT NULL COMMENT 'Deadline to start the exam' AFTER `Schedule_Date`;

-- Update existing exams to have a default deadline (24 hours after schedule date)
UPDATE `tbl_exam` 
SET `Deadline` = DATE_ADD(`Schedule_Date`, INTERVAL 24 HOUR) 
WHERE `Schedule_Date` IS NOT NULL AND `Deadline` IS NULL;
