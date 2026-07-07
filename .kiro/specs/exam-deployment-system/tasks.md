# Implementation Plan: Exam Deployment System

## Overview

This implementation plan breaks down the Exam Deployment System into discrete coding tasks. The system enables administrators and CCMD officers to assign questions from the question bank to exams, deploy exams to batches, and filter examinee dashboards based on batch enrollment. Implementation follows a bottom-up approach: database schema first, then API endpoints, then UI components, and finally integration.

## Tasks

- [x] 1. Create database migration script for new tables
  - Create migration file `scripts/migrations/exam_deployment_system.sql`
  - Add CREATE TABLE statements for tbl_exam_question, tbl_exam_batch, tbl_user_batch
  - Include all foreign key constraints with CASCADE delete
  - Add unique constraints and indexes as specified in design
  - Add IF NOT EXISTS checks for idempotent execution
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10_

- [ ]* 1.1 Write property test for referential integrity cascade
  - **Property 1: Referential Integrity Cascade**
  - **Validates: Requirements 1.2, 1.3, 2.2, 2.3, 2.7, 2.8**

- [ ]* 1.2 Write property test for unique assignment constraints
  - **Property 2: Unique Assignment Constraints**
  - **Validates: Requirements 1.5, 2.5, 2.10**

- [ ] 2. Implement Question Assignment API (api/masterfiles/exam-questions.php)
  - [x] 2.1 Create exam-questions.php file with PDO connection
    - Set up error handling and JSON response structure
    - Implement authentication and role verification (Admin/CCMD only)
    - _Requirements: 4.1_

  - [x] 2.2 Implement POST assign action
    - Accept exam_id and question_ids array from request body
    - Validate exam_id exists, return 404 if not found
    - Delete existing assignments for the exam
    - Insert new assignments with sequential Question_Order (1, 2, 3, ...)
    - Return success response with count
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [ ]* 2.3 Write property test for question assignment idempotence
    - **Property 3: Question Assignment Idempotence**
    - **Validates: Requirements 4.2, 4.3**

  - [ ]* 2.4 Write property test for sequential question ordering
    - **Property 5: Sequential Question Ordering**
    - **Validates: Requirements 3.11, 4.3**

  - [x] 2.5 Implement GET action to retrieve assigned questions
    - Accept exam_id query parameter
    - Join with tbl_question_bank and tbl_subject
    - Include question details and choices
    - Order by Question_Order ascending
    - Return JSON array of questions
    - _Requirements: 4.5, 4.6, 4.7_

  - [ ]* 2.6 Write property test for question retrieval ordering
    - **Property 6: Question Retrieval Ordering**
    - **Validates: Requirements 4.6**

  - [ ]* 2.7 Write property test for API response completeness
    - **Property 7: API Response Completeness**
    - **Validates: Requirements 4.7, 5.3, 6.6, 7.1, 7.2, 8.7**

  - [x] 2.8 Implement POST reorder action
    - Accept exam_id and order array [{question_id, order}]
    - Update Question_Order for each question
    - Return success response
    - _Requirements: 4.8, 4.9_

  - [ ]* 2.9 Write property test for question reordering correctness
    - **Property 8: Question Reordering Correctness**
    - **Validates: Requirements 4.9**

- [ ] 3. Implement Batch Assignment API (api/masterfiles/exam-batches.php)
  - [x] 3.1 Create exam-batches.php file with PDO connection
    - Set up error handling and JSON response structure
    - Implement authentication and role verification (Admin/CCMD only)
    - _Requirements: 6.1_

  - [x] 3.2 Implement POST assign action
    - Accept exam_id and batch_ids array from request body
    - Validate exam_id exists, return 404 if not found
    - Delete existing batch assignments for the exam
    - Insert new batch assignments
    - Return success response with count
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 3.3 Write property test for batch assignment idempotence
    - **Property 4: Batch Assignment Idempotence**
    - **Validates: Requirements 6.2, 6.3**

  - [x] 3.4 Implement GET action to retrieve assigned batches
    - Accept exam_id query parameter
    - Join with tbl_batch, tbl_course, tbl_section
    - Return batch details (Batch_ID, Batch_Name, Course_Name, Section_Name)
    - _Requirements: 6.5, 6.6_

  - [x] 3.5 Implement GET list action to retrieve all batches
    - Query all batches with course and section information
    - Return formatted list for UI selection
    - _Requirements: 6.7, 6.8_

- [ ] 4. Implement User-Batch Management API (api/masterfiles/user-batch.php)
  - [x] 4.1 Create user-batch.php file with PDO connection
    - Set up error handling and JSON response structure
    - Implement authentication and role verification (Admin/CCMD only)
    - _Requirements: 12.1_

  - [x] 4.2 Implement POST assign action
    - Accept user_id and batch_id from request body
    - Verify user has Role = 'Examinee', return 400 if not
    - Insert record in tbl_user_batch with Status = 'Active'
    - Set Date_Enrolled to current timestamp
    - Handle duplicate enrollment gracefully
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [ ]* 4.3 Write property test for role-based batch enrollment
    - **Property 17: Role-Based Batch Enrollment**
    - **Validates: Requirements 11.7, 12.2, 12.3**

  - [ ]* 4.4 Write property test for enrollment record creation
    - **Property 18: Enrollment Record Creation**
    - **Validates: Requirements 12.4**

  - [ ]* 4.5 Write property test for batch enrollment status
    - **Property 15: Batch Enrollment Status**
    - **Validates: Requirements 11.5**

  - [x] 4.6 Implement POST remove action
    - Accept user_id and batch_id from request body
    - Delete record from tbl_user_batch
    - Return success response
    - _Requirements: 12.5_

  - [x] 4.7 Implement GET batches_by_user action
    - Accept user_id query parameter
    - Return all batches the user is enrolled in
    - Include batch details and enrollment status
    - _Requirements: 12.6_

  - [x] 4.8 Implement GET users_by_batch action
    - Accept batch_id query parameter
    - Return all users enrolled in the batch
    - Include user details and enrollment status
    - _Requirements: 12.7_

  - [ ]* 4.9 Write property test for multiple batch enrollment
    - **Property 16: Multiple Batch Enrollment**
    - **Validates: Requirements 11.6**

- [x] 5. Checkpoint - Ensure all API tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Enhance Exams List API (api/masterfiles/exams.php)
  - [x] 6.1 Modify GET list action to include question and batch counts
    - Add LEFT JOIN with tbl_exam_question
    - Add LEFT JOIN with tbl_exam_batch
    - Add COUNT(DISTINCT eq.Question_ID) as Question_Count
    - Add COUNT(DISTINCT eb.Batch_ID) as Batch_Count
    - Add GROUP BY e.Exam_ID
    - Return enhanced exam list with counts
    - _Requirements: 7.1, 7.2_

  - [ ]* 6.2 Write property test for question count display
    - **Property 19: Question Count Display**
    - **Validates: Requirements 7.1, 13.1**

  - [ ]* 6.3 Write property test for batch count display
    - **Property 20: Batch Count Display**
    - **Validates: Requirements 7.2, 14.1**

- [ ] 7. Implement exam publishing validation (api/masterfiles/exams.php)
  - [x] 7.1 Add validation logic to POST update action
    - Check if Status is being changed to 'Published'
    - Query question count from tbl_exam_question
    - If question count is 0, return 400 with error message
    - Query batch count from tbl_exam_batch
    - If batch count is 0, return 400 with error message
    - Allow Draft/Closed status without validation
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

  - [ ]* 7.2 Write property test for publishing validation
    - **Property 23: Publishing Validation**
    - **Validates: Requirements 15.1, 15.2, 15.3, 15.4, 15.5**

  - [ ]* 7.3 Write property test for invalid exam ID error handling
    - **Property 24: Invalid Exam ID Error Handling**
    - **Validates: Requirements 4.4, 6.4**

- [ ] 8. Enhance Examinee Dashboard API (api/examinee/dashboard.php)
  - [x] 8.1 Modify GET available_exams action to filter by batch
    - Add INNER JOIN with tbl_exam_batch on Exam_ID
    - Add INNER JOIN with tbl_user_batch on Batch_ID
    - Filter WHERE e.Status = 'Published'
    - Filter WHERE ub.User_ID = authenticated user
    - Filter WHERE ub.Status = 'Active'
    - Add LEFT JOIN with tbl_exam_session to exclude completed exams
    - Filter WHERE es.Session_ID IS NULL OR es.Time_Ended IS NULL
    - Add GROUP BY e.Exam_ID to handle multiple batch matches
    - Include Question_Count in response
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

  - [ ]* 8.2 Write property test for published exam filtering
    - **Property 11: Published Exam Filtering**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.5**

- [ ] 9. Implement exam start validation (api/examinee/start-exam.php)
  - [x] 9.1 Add validation checks before creating exam session
    - Query question count for the exam
    - If count is 0, return 400 with error message
    - Verify exam is assigned to user's active batch
    - If not assigned, return 403 with error message
    - Verify exam Status = 'Published'
    - If not published, return 400 with error message
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [ ]* 9.2 Write property test for exam start validation
    - **Property 14: Exam Start Validation**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5, 10.6**

- [ ] 10. Implement question randomization logic (api/examinee/start-exam.php)
  - [x] 10.1 Add randomization logic when loading exam questions
    - Check Is_Randomized flag on tbl_exam
    - If Is_Randomized = 1, retrieve questions and shuffle using Session_ID as seed
    - If Is_Randomized = 0, retrieve questions ordered by Question_Order
    - Store question order in session or database for consistency
    - Return questions in the determined order
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ]* 10.2 Write property test for randomization consistency
    - **Property 12: Randomization Consistency**
    - **Validates: Requirements 9.2, 9.4, 9.5**

  - [ ]* 10.3 Write property test for non-randomized order preservation
    - **Property 13: Non-Randomized Order Preservation**
    - **Validates: Requirements 9.3**

- [x] 11. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Create Question Selection Modal UI (html/masterfiles/exams.php)
  - [x] 12.1 Add modal HTML structure
    - Create modal with id="questionSelectionModal"
    - Add subject filter dropdown
    - Add search input field
    - Add scrollable question list container
    - Add selected count display
    - Add Save Selection and Cancel buttons
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 12.2 Implement question loading JavaScript (js/masterfiles/exams.js)
    - Fetch all questions from question bank API
    - Group questions by subject
    - Render questions with checkboxes
    - Display question text, subject, and choices
    - _Requirements: 3.3, 3.6, 3.7_

  - [x] 12.3 Implement subject filter functionality
    - Filter displayed questions when subject dropdown changes
    - Show/hide questions based on selected subject
    - _Requirements: 3.4_

  - [x] 12.4 Implement search functionality
    - Filter questions by question text as user types
    - Update displayed questions in real-time
    - _Requirements: 3.5_

  - [x] 12.5 Implement selection count tracking
    - Update count when checkboxes are toggled
    - Display count in real-time
    - _Requirements: 3.8, 3.9_

  - [ ]* 12.6 Write property test for selection count accuracy
    - **Property 10: Selection Count Accuracy**
    - **Validates: Requirements 3.8, 3.9**

  - [x] 12.7 Implement Save Selection button handler
    - Collect selected question IDs
    - Call POST /api/masterfiles/exam-questions.php?action=assign
    - Update question count display in exam modal
    - Close question selection modal
    - Show success message
    - _Requirements: 3.10, 3.11_

- [ ] 13. Enhance Exam Modal with Batch Assignment UI (html/masterfiles/exams.php)
  - [x] 13.1 Add batch assignment section to exam modal
    - Add "Assign to Batches" label and container
    - Add batch count display
    - _Requirements: 5.1_

  - [x] 13.2 Implement batch loading JavaScript (js/masterfiles/exams.js)
    - Fetch all batches from GET /api/masterfiles/exam-batches.php?action=list
    - Render batch checkboxes with Batch_Name, Course_Name, Section_Name
    - _Requirements: 5.2, 5.3, 5.4_

  - [x] 13.3 Implement batch pre-selection for existing exams
    - When editing an exam, fetch assigned batches
    - Pre-check checkboxes for assigned batches
    - _Requirements: 5.5_

  - [ ]* 13.4 Write property test for batch pre-selection consistency
    - **Property 9: Batch Pre-selection Consistency**
    - **Validates: Requirements 5.5**

  - [x] 13.4 Implement batch count tracking
    - Update count when batch checkboxes are toggled
    - Display count in real-time
    - _Requirements: 5.6_

  - [x] 13.5 Implement exam save with batch assignments
    - Collect selected batch IDs when saving exam
    - Call POST /api/masterfiles/exam-batches.php?action=assign
    - Show success message
    - _Requirements: 5.7_

- [ ] 14. Add Question Assignment section to Exam Modal (html/masterfiles/exams.php)
  - [x] 14.1 Add question management section to exam modal
    - Add "Exam Questions" label
    - Add question count badge
    - Add "Manage Questions" button
    - _Requirements: 3.1, 3.2_

  - [x] 14.2 Implement Manage Questions button handler (js/masterfiles/exams.js)
    - Open question selection modal when clicked
    - Load current exam's assigned questions
    - Pre-select assigned questions in modal
    - _Requirements: 3.2_

  - [x] 14.3 Implement question count display
    - Fetch question count when opening exam modal
    - Display count in badge
    - Show warning if count is 0
    - _Requirements: 13.1, 13.2, 13.3_

  - [x] 14.4 Update question count after assignment
    - Refresh count after saving question selection
    - Update display without closing exam modal
    - _Requirements: 13.4_

  - [ ]* 14.5 Write property test for real-time count updates
    - **Property 22: Real-Time Count Updates**
    - **Validates: Requirements 13.4, 14.4**

- [ ] 15. Enhance Exam List Table (html/masterfiles/exams.php, js/masterfiles/exams.js)
  - [x] 15.1 Add Questions and Batches columns to table HTML
    - Add <th>Questions</th> header
    - Add <th>Batches</th> header
    - _Requirements: 7.1, 7.2_

  - [x] 15.2 Update exam rendering JavaScript
    - Display Question_Count in badge
    - Display Batch_Count in badge
    - Use warning badge (bg-warning) for zero counts
    - Use success badge (bg-success) for questions > 0
    - Use info badge (bg-info) for batches > 0
    - Add tooltip showing batch names on hover
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ]* 15.3 Write property test for zero count warning display
    - **Property 21: Zero Count Warning Display**
    - **Validates: Requirements 7.3, 7.4, 13.3, 14.3**

- [ ] 16. Add Batch Enrollment UI to User Management (html/masterfiles/users.php)
  - [x] 16.1 Add batch enrollment section to user modal
    - Add "Batch Enrollment" section (visible only for Examinees)
    - Add container to display enrolled batches
    - Add "Add to Batch" button
    - Add "Remove from Batch" button for each batch
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [x] 16.2 Implement batch enrollment display (js/masterfiles/users.js)
    - Fetch user's batches from GET /api/masterfiles/user-batch.php?action=batches_by_user
    - Display batch list with Batch_Name, Status, Date_Enrolled
    - Show section only if user Role = 'Examinee'
    - _Requirements: 11.2_

  - [x] 16.3 Implement Add to Batch functionality
    - Show batch selection dropdown when button clicked
    - Call POST /api/masterfiles/user-batch.php?action=assign
    - Refresh batch list after successful enrollment
    - Show success message
    - _Requirements: 11.3, 11.5_

  - [x] 16.4 Implement Remove from Batch functionality
    - Call POST /api/masterfiles/user-batch.php?action=remove
    - Refresh batch list after successful removal
    - Show confirmation dialog before removal
    - _Requirements: 11.4_

- [ ] 17. Update Examinee Dashboard UI (html/examinee/dashboard.php, js/examinee/examinee-dashboard.js)
  - [ ] 17.1 Verify dashboard loads filtered exams
    - Ensure dashboard calls GET /api/examinee/dashboard.php?action=available_exams
    - Display only exams returned by the filtered API
    - Show message if no exams are available
    - _Requirements: 8.1, 8.2, 8.3, 8.6_

  - [ ] 17.2 Display exam details including question count
    - Render exam cards with Title, Description, Schedule_Date, Duration
    - Display Question_Count in exam card
    - Display Subject_Name and Course_Name
    - _Requirements: 8.7_

- [ ] 18. Final checkpoint - Integration testing
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties across all inputs
- The implementation follows a bottom-up approach: database → API → UI → integration
- All API endpoints require authentication and role-based authorization
- Use prepared statements for all database queries to prevent SQL injection
- Escape all user-generated content before rendering to prevent XSS attacks
