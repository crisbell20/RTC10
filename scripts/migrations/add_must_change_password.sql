-- Add Must_Change_Password column to tbl_user table
-- This column is used to force users to change their password on first login

ALTER TABLE `tbl_user` 
ADD COLUMN `Must_Change_Password` tinyint(1) NOT NULL DEFAULT 0 
AFTER `Academic_Number`;

-- Set Must_Change_Password = 1 for all Examinee accounts (they should change password on first login)
UPDATE `tbl_user` u
INNER JOIN `tbl_role` r ON u.Role_ID = r.Role_ID
SET u.Must_Change_Password = 1
WHERE r.Role_Name = 'Examinee';
