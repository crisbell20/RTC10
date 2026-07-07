# Requirements Document

## Introduction

The Exam Deployment System enables administrators and CCMD officers to assign questions from the question bank to specific exams and deploy those exams to designated batches of examinees. This system ensures that examinees only see exams that are published, assigned to their batch(es), and not yet completed by them. The system supports question randomization per examinee when enabled.

## Glossary

- **System**: The Exam Deployment System
- **Admin**: User with administrative privileges (Role = 'Admin')
- **CCMD**: User with CCMD officer privileges (Role = 'CCMD')
- **Examinee**: User taking exams (Role = 'Examinee')
- **Question_Bank**: Repository of all available exam questions (tbl_question_bank)
- **Exam**: Assessment containing assigned questions (tbl_exam)
- **Batch**: Group of examinees enrolled together (tbl_batch)
- **Exam_Question_Link**: Association between an exam and a question (tbl_exam_question)
- **Exam_Batch_Link**: Association between an exam and a batch (tbl_exam_batch)
- **Published_Exam**: Exam with Status = 'Published'
- **Completed_Exam**: Exam with a corresponding tbl_exam_session record where Time_Ended IS NOT NULL for the user
- **Question_Assignment_UI**: Interface for selecting and assigning questions to exams
- **Batch_Assignment_UI**: Interface for assigning exams to batches
- **Dashboard_API**: API endpoint serving available exams to examinees (api/examinee/dashboard.php)

## Requirements

### Requirement 1: Database Schema for Question Assignment

**User Story:** As a system architect, I want database tables to link exams with questions, so that each exam can have a specific set of questions assigned to it.

#### Acceptance Criteria

1. THE System SHALL create a table tbl_exam_question with columns: Exam_Question_ID (primary key, auto-increment), Exam_ID (foreign key to tbl_exam), Question_ID (foreign key to tbl_question_bank), Question_Order (integer), Date_Added (datetime)
2. THE System SHALL enforce a foreign key constraint from tbl_exam_question.Exam_ID to tbl_exam.Exam_ID with CASCADE on delete
3. THE System SHALL enforce a foreign key constraint from tbl_exam_question.Question_ID to tbl_question_bank.Question_ID with CASCADE on delete
4. THE System SHALL create an index on tbl_exam_question (Exam_ID, Question_Order) for efficient question retrieval
5. THE System SHALL prevent duplicate question assignments by creating a unique constraint on (Exam_ID, Question_ID)

### Requirement 2: Database Schema for Batch Assignment

**User Story:** As a system architect, I want database tables to link exams with batches and users with batches, so that exams can be deployed to specific groups of examinees.

#### Acceptance Criteria

1. THE System SHALL create a table tbl_exam_batch with columns: Exam_Batch_ID (primary key, auto-increment), Exam_ID (foreign key to tbl_exam), Batch_ID (foreign key to tbl_batch), Date_Assigned (datetime)
2. THE System SHALL enforce a foreign key constraint from tbl_exam_batch.Exam_ID to tbl_exam.Exam_ID with CASCADE on delete
3. THE System SHALL enforce a foreign key constraint from tbl_exam_batch.Batch_ID to tbl_batch.Batch_ID with CASCADE on delete
4. THE System SHALL create an index on tbl_exam_batch (Batch_ID) for efficient batch filtering
5. THE System SHALL prevent duplicate batch assignments by creating a unique constraint on (Exam_ID, Batch_ID)
6. THE System SHALL create a table tbl_user_batch with columns: User_Batch_ID (primary key, auto-increment), User_ID (foreign key to tbl_user), Batch_ID (foreign key to tbl_batch), Status (enum: 'Active', 'Inactive'), Date_Enrolled (datetime)
7. THE System SHALL enforce a foreign key constraint from tbl_user_batch.User_ID to tbl_user.User_ID with CASCADE on delete
8. THE System SHALL enforce a foreign key constraint from tbl_user_batch.Batch_ID to tbl_batch.Batch_ID with CASCADE on delete
9. THE System SHALL create an index on tbl_user_batch (User_ID) for efficient user batch lookup
10. THE System SHALL prevent duplicate user-batch enrollments by creating a unique constraint on (User_ID, Batch_ID)

### Requirement 3: Question Assignment Interface

**User Story:** As an Admin or CCMD officer, I want to assign questions from the question bank to an exam, so that I can build the exam content.

#### Acceptance Criteria

1. WHEN an Admin or CCMD opens the exam modal, THE Question_Assignment_UI SHALL display a "Manage Questions" section
2. THE Question_Assignment_UI SHALL display a button to open the question selection interface
3. WHEN the question selection interface opens, THE System SHALL load all questions from Question_Bank grouped by subject
4. THE Question_Assignment_UI SHALL provide a subject filter dropdown to filter questions by subject
5. THE Question_Assignment_UI SHALL provide a search input field to search questions by question text
6. THE Question_Assignment_UI SHALL display each question with its question text, subject name, and answer choices
7. THE Question_Assignment_UI SHALL display a checkbox for each question to select/deselect it
8. THE Question_Assignment_UI SHALL display the count of currently selected questions
9. WHEN a user selects or deselects questions, THE System SHALL update the selected question count in real-time
10. THE Question_Assignment_UI SHALL provide a "Save Selection" button to persist the question assignments
11. WHEN the user saves the selection, THE System SHALL store the assignments in Exam_Question_Link with sequential Question_Order values

### Requirement 4: Question Assignment API

**User Story:** As a developer, I want API endpoints to manage question assignments, so that the frontend can save and retrieve exam questions.

#### Acceptance Criteria

1. THE System SHALL provide an API endpoint POST /api/masterfiles/exam-questions.php?action=assign to assign questions to an exam
2. WHEN the assign endpoint receives exam_id and question_ids array, THE System SHALL delete existing assignments for that exam
3. WHEN the assign endpoint receives exam_id and question_ids array, THE System SHALL insert new Exam_Question_Link records with sequential Question_Order
4. IF the exam_id does not exist, THEN THE System SHALL return HTTP 404 with error message "Exam not found"
5. THE System SHALL provide an API endpoint GET /api/masterfiles/exam-questions.php?action=get&exam_id={id} to retrieve assigned questions
6. WHEN the get endpoint receives a valid exam_id, THE System SHALL return all assigned questions ordered by Question_Order
7. THE System SHALL include question details (Question_ID, Question_Text, Subject_Name, Question_Order) in the response
8. THE System SHALL provide an API endpoint POST /api/masterfiles/exam-questions.php?action=reorder to update question order
9. WHEN the reorder endpoint receives exam_id and an array of {question_id, order} pairs, THE System SHALL update the Question_Order for each Exam_Question_Link record

### Requirement 5: Batch Assignment Interface

**User Story:** As an Admin or CCMD officer, I want to assign an exam to specific batches, so that only examinees in those batches can see and take the exam.

#### Acceptance Criteria

1. WHEN an Admin or CCMD opens the exam modal, THE Batch_Assignment_UI SHALL display a "Assign to Batches" section
2. THE Batch_Assignment_UI SHALL load all available batches from tbl_batch
3. THE Batch_Assignment_UI SHALL display each batch with its Batch_Name, Course_Name, and Section_Name
4. THE Batch_Assignment_UI SHALL provide checkboxes to select multiple batches
5. WHEN editing an existing exam, THE Batch_Assignment_UI SHALL pre-select batches that are already assigned
6. THE Batch_Assignment_UI SHALL display the count of selected batches
7. WHEN the user saves the exam, THE System SHALL persist the batch assignments to Exam_Batch_Link

### Requirement 6: Batch Assignment API

**User Story:** As a developer, I want API endpoints to manage batch assignments, so that the frontend can save and retrieve exam-batch associations.

#### Acceptance Criteria

1. THE System SHALL provide an API endpoint POST /api/masterfiles/exam-batches.php?action=assign to assign batches to an exam
2. WHEN the assign endpoint receives exam_id and batch_ids array, THE System SHALL delete existing batch assignments for that exam
3. WHEN the assign endpoint receives exam_id and batch_ids array, THE System SHALL insert new Exam_Batch_Link records
4. IF the exam_id does not exist, THEN THE System SHALL return HTTP 404 with error message "Exam not found"
5. THE System SHALL provide an API endpoint GET /api/masterfiles/exam-batches.php?action=get&exam_id={id} to retrieve assigned batches
6. WHEN the get endpoint receives a valid exam_id, THE System SHALL return all assigned batches with Batch_ID, Batch_Name, Course_Name, Section_Name
7. THE System SHALL provide an API endpoint GET /api/masterfiles/exam-batches.php?action=list to retrieve all batches for selection
8. THE System SHALL return batches grouped by course and section for easier selection

### Requirement 7: Exam List Enhancement

**User Story:** As an Admin or CCMD officer, I want to see the question count and assigned batches for each exam in the exam list, so that I can quickly verify exam configuration.

#### Acceptance Criteria

1. WHEN the exam list loads, THE System SHALL display a "Questions" column showing the count of assigned questions for each exam
2. WHEN the exam list loads, THE System SHALL display a "Batches" column showing the count of assigned batches for each exam
3. IF an exam has zero questions assigned, THEN THE System SHALL display "0" with a warning indicator
4. IF an exam has zero batches assigned, THEN THE System SHALL display "0" with a warning indicator
5. THE System SHALL provide a tooltip on hover showing the list of assigned batch names

### Requirement 8: Examinee Dashboard Filtering

**User Story:** As an Examinee, I want to see only exams that are published and assigned to my batch(es), so that I don't see irrelevant exams.

#### Acceptance Criteria

1. WHEN the Dashboard_API retrieves available exams for an examinee, THE System SHALL filter exams where Status = 'Published'
2. WHEN the Dashboard_API retrieves available exams for an examinee, THE System SHALL filter exams that have at least one Exam_Batch_Link record matching the user's batches in tbl_user_batch
3. WHEN the Dashboard_API retrieves available exams for an examinee, THE System SHALL exclude exams that have a Completed_Exam record for that user
4. THE System SHALL join tbl_exam with tbl_exam_batch and tbl_user_batch on Batch_ID
5. THE System SHALL filter tbl_user_batch records where User_ID matches the authenticated examinee and Status = 'Active'
6. IF an examinee is not enrolled in any batches, THEN THE System SHALL return an empty exam list
7. THE Dashboard_API SHALL return exam details including Title, Description, Schedule_Date, Duration, Passing_Score, Subject_Name, Course_Name

### Requirement 9: Question Randomization

**User Story:** As an Admin or CCMD officer, I want to randomize question order for each examinee when they start an exam, so that examinees cannot easily share answers.

#### Acceptance Criteria

1. WHEN an examinee starts an exam, THE System SHALL check the Is_Randomized flag on tbl_exam
2. IF Is_Randomized = 1, THEN THE System SHALL retrieve assigned questions and shuffle their order using a random seed based on Session_ID
3. IF Is_Randomized = 0, THEN THE System SHALL retrieve assigned questions ordered by Question_Order
4. THE System SHALL store the randomized question order in the exam session for consistency during the exam
5. WHEN an examinee navigates between questions during an exam, THE System SHALL maintain the same randomized order throughout the session

### Requirement 10: Exam Start Validation

**User Story:** As an Examinee, I want the system to validate that I can start an exam, so that I don't encounter errors mid-exam.

#### Acceptance Criteria

1. WHEN an examinee attempts to start an exam, THE System SHALL verify the exam has at least one assigned question
2. IF the exam has zero assigned questions, THEN THE System SHALL return HTTP 400 with error message "This exam has no questions assigned"
3. WHEN an examinee attempts to start an exam, THE System SHALL verify the exam is assigned to at least one of the user's active batches
4. IF the exam is not assigned to any of the user's batches, THEN THE System SHALL return HTTP 403 with error message "You are not authorized to take this exam"
5. WHEN an examinee attempts to start an exam, THE System SHALL verify the exam Status = 'Published'
6. IF the exam Status is not 'Published', THEN THE System SHALL return HTTP 400 with error message "This exam is not available"

### Requirement 11: Batch Management for Users

**User Story:** As an Admin or CCMD officer, I want to assign examinees to batches, so that they can access exams deployed to those batches.

#### Acceptance Criteria

1. THE System SHALL provide a user interface in the user management page to assign examinees to batches
2. WHEN viewing an examinee's details, THE System SHALL display a list of batches the examinee is enrolled in
3. THE System SHALL provide an "Add to Batch" button to assign the examinee to additional batches
4. THE System SHALL provide a "Remove from Batch" button to remove the examinee from a batch
5. WHEN adding an examinee to a batch, THE System SHALL set Status = 'Active' and Date_Enrolled = current timestamp
6. THE System SHALL allow an examinee to be enrolled in multiple batches simultaneously
7. THE System SHALL prevent enrolling users with Role other than 'Examinee' to batches

### Requirement 12: Batch Management API

**User Story:** As a developer, I want API endpoints to manage user-batch assignments, so that the frontend can enroll and remove examinees from batches.

#### Acceptance Criteria

1. THE System SHALL provide an API endpoint POST /api/masterfiles/user-batch.php?action=assign to assign a user to a batch
2. WHEN the assign endpoint receives user_id and batch_id, THE System SHALL verify the user has Role = 'Examinee'
3. IF the user is not an Examinee, THEN THE System SHALL return HTTP 400 with error message "Only examinees can be assigned to batches"
4. WHEN the assign endpoint receives valid user_id and batch_id, THE System SHALL insert a record in tbl_user_batch with Status = 'Active'
5. THE System SHALL provide an API endpoint POST /api/masterfiles/user-batch.php?action=remove to remove a user from a batch
6. THE System SHALL provide an API endpoint GET /api/masterfiles/user-batch.php?action=batches_by_user&user_id={id} to retrieve all batches for a user
7. THE System SHALL provide an API endpoint GET /api/masterfiles/user-batch.php?action=users_by_batch&batch_id={id} to retrieve all users in a batch

### Requirement 13: Question Count Display in Exam Modal

**User Story:** As an Admin or CCMD officer, I want to see the current question count when editing an exam, so that I know if the exam is ready for deployment.

#### Acceptance Criteria

1. WHEN the exam modal opens for editing, THE System SHALL display the count of assigned questions
2. THE System SHALL display the question count in a prominent location near the "Manage Questions" button
3. IF the question count is zero, THEN THE System SHALL display a warning message "No questions assigned"
4. WHEN the user saves new question assignments, THE System SHALL update the displayed question count without closing the modal

### Requirement 14: Batch Count Display in Exam Modal

**User Story:** As an Admin or CCMD officer, I want to see the current batch count when editing an exam, so that I know which groups can access the exam.

#### Acceptance Criteria

1. WHEN the exam modal opens for editing, THE System SHALL display the count of assigned batches
2. THE System SHALL display the batch count in a prominent location near the batch selection checkboxes
3. IF the batch count is zero, THEN THE System SHALL display a warning message "Not assigned to any batches"
4. WHEN the user changes batch selections, THE System SHALL update the displayed batch count in real-time

### Requirement 15: Exam Publishing Validation

**User Story:** As an Admin or CCMD officer, I want the system to validate exam configuration before publishing, so that examinees don't encounter incomplete exams.

#### Acceptance Criteria

1. WHEN an Admin or CCMD attempts to change exam Status to 'Published', THE System SHALL verify the exam has at least one assigned question
2. IF the exam has zero questions, THEN THE System SHALL return HTTP 400 with error message "Cannot publish exam without questions"
3. WHEN an Admin or CCMD attempts to change exam Status to 'Published', THE System SHALL verify the exam is assigned to at least one batch
4. IF the exam has zero batch assignments, THEN THE System SHALL return HTTP 400 with error message "Cannot publish exam without batch assignments"
5. THE System SHALL allow saving exams with Status = 'Draft' or 'Closed' without question or batch validation
