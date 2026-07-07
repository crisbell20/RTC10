-- Allow examinees to review question responses after submission (Google Forms style)
ALTER TABLE `tbl_exam`
  ADD COLUMN `Allow_Response_Review` tinyint(1) NOT NULL DEFAULT 0;
