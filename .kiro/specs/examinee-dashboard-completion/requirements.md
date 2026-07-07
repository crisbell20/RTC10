# Requirements Document

## Introduction

This document specifies the requirements for completing the Examinee Dashboard functionality in the PNP RTC X examination system. The current implementation has a complete UI structure but lacks backend APIs, frontend logic, and data processing capabilities. This feature completion will transform the static dashboard into a fully functional system that fetches real data, enforces business rules, and provides complete CRUD operations for exam management from the examinee perspective.

The implementation will address rubric deficiencies in the Core Functional Module (0/20 points), complete Database Implementation validation (2 missing points), and enhance System Architecture (2 missing points) to achieve a full 40/40 project score.

## Glossary

- **Examinee**: A user with Role_ID = 3 who takes exams in the system
- **Dashboard_API**: The backend service that provides dashboard statistics and data
- **Exam_System**: The complete examination management system
- **Session_Manager**: Component that validates user authentication and permissions
- **Available_Exam**: An exam with Status = 'Published' that the examinee has not yet taken
- **Completed_Exam**: An exam that has an associated exam session with Time_Ended not NULL
- **Exam_Session**: A record in tbl_exam_session tracking an examinee's attempt at an exam
- **Result_Record**: A record in tbl_result containing score and performance data
- **Learning_Points**: Calculated metric based on exam performance (sum of percentages)
- **Passing_Score**: The minimum percentage required to pass an exam (from tbl_exam.Passing_Score)
- **Business_Rules**: Constraints on exam availability, deadlines, and access permissions
- **Round_Trip_Property**: A property where parsing then printing then parsing produces equivalent output

## Requirements

### Requirement 1: Dashboard Statistics API

**User Story:** As an examinee, I want to see my exam statistics on the dashboard, so that I can track my progress and performance at a glance.

#### Acceptance Criteria

1. WHEN the Dashboard_API receives a request from an authenticated examinee, THE Dashboard_API SHALL return the count of Available_Exams for that examinee
2. WHEN the Dashboard_API receives a request from an authenticated examinee, THE Dashboard_API SHALL return the count of Completed_Exams for that examinee
3. WHEN the Dashboard_API receives a request from an authenticated examinee, THE Dashboard_API SHALL calculate and return the average score across all Completed_Exams
4. WHEN the Dashboard_API receives a request from an authenticated examinee, THE Dashboard_API SHALL calculate and return Learning_Points as the sum of all exam percentages
5. WHEN an examinee has no Completed_Exams, THE Dashboard_API SHALL return 0 for average score and Learning_Points
6. WHEN the Dashboard_API receives a request from a non-examinee user, THE Dashboard_API SHALL return HTTP 403 Forbidden
7. WHEN the Dashboard_API receives a request without valid session authentication, THE Dashboard_API SHALL return HTTP 401 Unauthorized

### Requirement 2: Available Exams Data Retrieval

**User Story:** As an examinee, I want to see all exams I can take, so that I can choose which exam to attempt.

#### Acceptance Criteria

1. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL return only exams with Status = 'Published'
2. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL exclude exams the examinee has already completed
3. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL include exam title, course name, subject name, schedule date, and duration
4. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL join tbl_exam with tbl_subject and tbl_course to provide complete information
5. WHEN no Available_Exams exist for an examinee, THE Dashboard_API SHALL return an empty array
6. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL order results by Schedule_Date ascending

### Requirement 3: Recent Results Data Retrieval

**User Story:** As an examinee, I want to see my recent exam results, so that I can review my performance and identify areas for improvement.

#### Acceptance Criteria

1. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL return only results for the authenticated examinee
2. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL join tbl_result with tbl_exam_session and tbl_exam to provide complete information
3. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL include exam title, submission date, score, percentage, and remarks
4. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL calculate pass/fail status by comparing percentage to Passing_Score
5. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL order results by Submission_Date descending
6. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL limit results to the 10 most recent entries
7. WHEN an examinee has no completed exams, THE Dashboard_API SHALL return an empty array

### Requirement 4: Frontend Dashboard Statistics Display

**User Story:** As an examinee, I want the dashboard to automatically load my statistics when I log in, so that I can immediately see my current status.

#### Acceptance Criteria

1. WHEN the examinee dashboard page loads, THE Dashboard_Frontend SHALL fetch statistics from the Dashboard_API
2. WHEN statistics are successfully retrieved, THE Dashboard_Frontend SHALL update the "Exams Available" card with the count
3. WHEN statistics are successfully retrieved, THE Dashboard_Frontend SHALL update the "Exams Completed" card with the count
4. WHEN statistics are successfully retrieved, THE Dashboard_Frontend SHALL update the "Average Score" card with the percentage formatted to 1 decimal place
5. WHEN statistics are successfully retrieved, THE Dashboard_Frontend SHALL update the "Learning Points" card with the total points
6. WHEN the API request fails, THE Dashboard_Frontend SHALL display an error message using SweetAlert2
7. WHEN statistics are loading, THE Dashboard_Frontend SHALL display "--" as placeholder values

### Requirement 5: Frontend Available Exams Table Population

**User Story:** As an examinee, I want to see a table of available exams with action buttons, so that I can easily start taking an exam.

#### Acceptance Criteria

1. WHEN the examinee dashboard page loads, THE Dashboard_Frontend SHALL fetch available exams from the Dashboard_API
2. WHEN available exams are successfully retrieved, THE Dashboard_Frontend SHALL populate the table with exam title, course, type, status, and deadline
3. WHEN available exams are successfully retrieved, THE Dashboard_Frontend SHALL display a "Take Exam" button for each exam
4. WHEN no available exams exist, THE Dashboard_Frontend SHALL display a message "No exams available at this time"
5. WHEN the Schedule_Date is in the past, THE Dashboard_Frontend SHALL display "Overdue" status in red
6. WHEN the Schedule_Date is within 24 hours, THE Dashboard_Frontend SHALL display "Due Soon" status in orange
7. WHEN the Schedule_Date is more than 24 hours away, THE Dashboard_Frontend SHALL display "Available" status in green

### Requirement 6: Frontend Recent Results Table Population

**User Story:** As an examinee, I want to see a table of my recent exam results, so that I can track my performance over time.

#### Acceptance Criteria

1. WHEN the examinee dashboard page loads, THE Dashboard_Frontend SHALL fetch recent results from the Dashboard_API
2. WHEN recent results are successfully retrieved, THE Dashboard_Frontend SHALL populate the table with exam title, date taken, score, percentage, and status
3. WHEN the percentage is greater than or equal to Passing_Score, THE Dashboard_Frontend SHALL display "Passed" status with green badge
4. WHEN the percentage is less than Passing_Score, THE Dashboard_Frontend SHALL display "Failed" status with red badge
5. WHEN recent results are successfully retrieved, THE Dashboard_Frontend SHALL display a "View Details" button for each result
6. WHEN no recent results exist, THE Dashboard_Frontend SHALL display a message "No exam results yet"
7. WHEN recent results are successfully retrieved, THE Dashboard_Frontend SHALL format the date as "MMM DD, YYYY HH:MM AM/PM"

### Requirement 7: Input Validation and Sanitization

**User Story:** As a system administrator, I want all user inputs to be validated and sanitized, so that the system is protected from SQL injection and XSS attacks.

#### Acceptance Criteria

1. WHEN the Dashboard_API receives any request, THE Session_Manager SHALL validate that a session exists
2. WHEN the Dashboard_API receives any request, THE Session_Manager SHALL validate that the user_id in session matches a valid user in tbl_user
3. WHEN the Dashboard_API receives any request, THE Session_Manager SHALL validate that the user's Role_ID equals 3 (Examinee)
4. WHEN the Dashboard_API executes database queries, THE Dashboard_API SHALL use prepared statements with parameter binding
5. WHEN the Dashboard_API returns data, THE Dashboard_API SHALL encode special characters to prevent XSS
6. IF the session is invalid or expired, THEN THE Dashboard_API SHALL return HTTP 401 Unauthorized with error message
7. IF the user is not an examinee, THEN THE Dashboard_API SHALL return HTTP 403 Forbidden with error message

### Requirement 8: Error Handling and User Feedback

**User Story:** As an examinee, I want to see clear error messages when something goes wrong, so that I understand what happened and what to do next.

#### Acceptance Criteria

1. WHEN a database connection fails, THE Dashboard_API SHALL return HTTP 500 with message "Database connection failed"
2. WHEN a database query fails, THE Dashboard_API SHALL log the error and return HTTP 500 with message "Failed to retrieve data"
3. WHEN the Dashboard_Frontend receives an error response, THE Dashboard_Frontend SHALL display the error message using SweetAlert2
4. WHEN the Dashboard_Frontend receives a network error, THE Dashboard_Frontend SHALL display message "Network error. Please check your connection"
5. WHEN the Dashboard_Frontend receives HTTP 401, THE Dashboard_Frontend SHALL redirect to login page
6. WHEN the Dashboard_Frontend receives HTTP 403, THE Dashboard_Frontend SHALL display message "Access denied. You do not have permission"
7. WHEN any error occurs, THE Exam_System SHALL log the error with timestamp, user_id, and error details for debugging

### Requirement 9: Take Exam Functionality

**User Story:** As an examinee, I want to click "Take Exam" and start the exam, so that I can complete my assessment.

#### Acceptance Criteria

1. WHEN an examinee clicks "Take Exam" button, THE Dashboard_Frontend SHALL validate that the exam is still available
2. WHEN an examinee clicks "Take Exam" button, THE Dashboard_Frontend SHALL check if the Schedule_Date has passed
3. IF the exam deadline has passed, THEN THE Dashboard_Frontend SHALL display message "This exam is no longer available"
4. WHEN validation passes, THE Dashboard_Frontend SHALL create a new Exam_Session record with Time_Started = current timestamp
5. WHEN the Exam_Session is created, THE Dashboard_Frontend SHALL redirect to the exam taking page with Session_ID parameter
6. IF the Exam_Session creation fails, THEN THE Dashboard_Frontend SHALL display error message "Failed to start exam. Please try again"
7. WHEN an examinee has already started an exam, THE Dashboard_Frontend SHALL resume the existing session instead of creating a new one

### Requirement 10: View Result Details Functionality

**User Story:** As an examinee, I want to click "View Details" on a result and see my detailed exam performance, so that I can understand which questions I got right or wrong.

#### Acceptance Criteria

1. WHEN an examinee clicks "View Details" button, THE Dashboard_Frontend SHALL navigate to the result details page with Result_ID parameter
2. WHEN the result details page loads, THE Result_Details_API SHALL fetch the Result_Record and associated Answer records
3. WHEN the result details page loads, THE Result_Details_Frontend SHALL display exam title, score, percentage, and pass/fail status
4. WHEN the result details page loads, THE Result_Details_Frontend SHALL display a list of all questions with the examinee's answers
5. WHEN the result details page loads, THE Result_Details_Frontend SHALL highlight correct answers in green and incorrect answers in red
6. WHEN the result details page loads, THE Result_Details_Frontend SHALL show the correct answer for questions answered incorrectly
7. IF the Result_ID does not belong to the authenticated examinee, THEN THE Result_Details_API SHALL return HTTP 403 Forbidden

### Requirement 11: API Response Format Standardization

**User Story:** As a frontend developer, I want all API responses to follow a consistent format, so that I can handle responses predictably.

#### Acceptance Criteria

1. WHEN any API endpoint returns a successful response, THE API SHALL include a "success" boolean field set to true
2. WHEN any API endpoint returns a successful response, THE API SHALL include a "data" object containing the response payload
3. WHEN any API endpoint returns an error response, THE API SHALL include a "success" boolean field set to false
4. WHEN any API endpoint returns an error response, THE API SHALL include a "message" string field describing the error
5. WHEN any API endpoint returns data, THE API SHALL set Content-Type header to "application/json"
6. WHEN any API endpoint returns an error, THE API SHALL set appropriate HTTP status code (400, 401, 403, 404, 500)
7. WHEN any API endpoint completes, THE API SHALL return valid JSON that can be parsed without errors

### Requirement 12: Database Transaction Integrity

**User Story:** As a system administrator, I want database operations to maintain data integrity, so that the system remains consistent even during failures.

#### Acceptance Criteria

1. WHEN creating an Exam_Session, THE Dashboard_API SHALL use a database transaction
2. WHEN creating an Exam_Session, THE Dashboard_API SHALL verify the exam exists and is published before inserting
3. WHEN creating an Exam_Session, THE Dashboard_API SHALL verify the examinee has not already completed the exam
4. IF any validation fails during Exam_Session creation, THEN THE Dashboard_API SHALL rollback the transaction
5. WHEN the transaction completes successfully, THE Dashboard_API SHALL commit the changes
6. WHEN a database error occurs during transaction, THE Dashboard_API SHALL rollback and return error response
7. WHEN creating an Exam_Session, THE Dashboard_API SHALL ensure User_ID and Exam_ID foreign key constraints are satisfied

### Requirement 13: Performance Optimization

**User Story:** As an examinee, I want the dashboard to load quickly, so that I can access my exams without delay.

#### Acceptance Criteria

1. WHEN the Dashboard_API fetches statistics, THE Dashboard_API SHALL execute all queries in a single database connection
2. WHEN the Dashboard_API fetches available exams, THE Dashboard_API SHALL use JOIN operations instead of multiple queries
3. WHEN the Dashboard_API fetches recent results, THE Dashboard_API SHALL limit results to 10 records to reduce payload size
4. WHEN the Dashboard_Frontend loads, THE Dashboard_Frontend SHALL make API requests in parallel using Promise.all
5. WHEN the Dashboard_Frontend receives data, THE Dashboard_Frontend SHALL update the DOM efficiently without full page reloads
6. WHEN the Dashboard_API returns data, THE Dashboard_API SHALL include only necessary fields to minimize response size
7. WHEN database queries execute, THE Exam_System SHALL use indexed columns (User_ID, Exam_ID, Status) for optimal performance

### Requirement 14: JSON Data Format Validation

**User Story:** As a system integrator, I want API responses to be valid JSON, so that they can be reliably parsed by clients.

#### Acceptance Criteria

1. WHEN the Dashboard_API returns statistics, THE Dashboard_API SHALL format the response as valid JSON
2. WHEN the Dashboard_API returns available exams, THE Dashboard_API SHALL format each exam as a JSON object with consistent field names
3. WHEN the Dashboard_API returns recent results, THE Dashboard_API SHALL format each result as a JSON object with consistent field names
4. WHEN the Dashboard_Frontend parses API responses, THE Dashboard_Frontend SHALL handle JSON parse errors gracefully
5. FOR ALL API responses, parsing the JSON then encoding then parsing SHALL produce an equivalent data structure (round-trip property)
6. WHEN the Dashboard_API encounters non-UTF8 data, THE Dashboard_API SHALL encode it properly to maintain JSON validity
7. WHEN the Dashboard_API returns dates, THE Dashboard_API SHALL format them as ISO 8601 strings (YYYY-MM-DD HH:MM:SS)

### Requirement 15: Sidebar Navigation Functionality

**User Story:** As an examinee, I want the sidebar links to navigate to different sections, so that I can access all features of the system.

#### Acceptance Criteria

1. WHEN an examinee clicks "Dashboard" in the sidebar, THE Dashboard_Frontend SHALL navigate to dashboard.php
2. WHEN an examinee clicks "Available Exams" in the sidebar, THE Dashboard_Frontend SHALL navigate to available-exams.php
3. WHEN an examinee clicks "My Results" in the sidebar, THE Dashboard_Frontend SHALL navigate to results.php
4. WHEN an examinee clicks "My Profile" in the sidebar, THE Dashboard_Frontend SHALL navigate to profile.php
5. WHEN a sidebar link is clicked, THE Dashboard_Frontend SHALL add "active" class to the current link
6. WHEN a sidebar link is clicked, THE Dashboard_Frontend SHALL remove "active" class from other links
7. WHERE the target page does not exist, THE Dashboard_Frontend SHALL display the dashboard page with a "Coming Soon" message

### Requirement 16: Session Timeout Handling

**User Story:** As an examinee, I want to be notified when my session expires, so that I can log in again without losing context.

#### Acceptance Criteria

1. WHEN the Dashboard_Frontend receives HTTP 401 from any API, THE Dashboard_Frontend SHALL display message "Your session has expired. Please log in again"
2. WHEN the session expiration message is displayed, THE Dashboard_Frontend SHALL redirect to login page after 3 seconds
3. WHEN an examinee is inactive for 30 minutes, THE Session_Manager SHALL invalidate the session
4. WHEN the Dashboard_API detects an invalid session, THE Dashboard_API SHALL return HTTP 401 Unauthorized
5. WHEN an examinee logs in again after session expiration, THE Exam_System SHALL restore them to the dashboard
6. WHEN an examinee has an active Exam_Session, THE Session_Manager SHALL extend the session timeout to prevent interruption
7. WHEN the Dashboard_Frontend detects session expiration during exam, THE Dashboard_Frontend SHALL save progress before redirecting

### Requirement 17: Data Consistency Validation

**User Story:** As a system administrator, I want the system to validate data consistency, so that reports and statistics are accurate.

#### Acceptance Criteria

1. WHEN calculating average score, THE Dashboard_API SHALL exclude exams with NULL or 0 percentage
2. WHEN calculating Learning_Points, THE Dashboard_API SHALL sum only valid percentage values from tbl_result
3. WHEN counting Available_Exams, THE Dashboard_API SHALL verify each exam has a valid Subject_ID and Course_ID
4. WHEN counting Completed_Exams, THE Dashboard_API SHALL verify each session has a corresponding Result_Record
5. WHEN fetching exam data, THE Dashboard_API SHALL verify foreign key relationships are intact
6. IF data inconsistency is detected, THEN THE Dashboard_API SHALL log the issue and exclude the inconsistent record
7. WHEN the Dashboard_API detects missing required fields, THE Dashboard_API SHALL return HTTP 500 with descriptive error message

### Requirement 18: Accessibility and Usability

**User Story:** As an examinee with accessibility needs, I want the dashboard to be usable with keyboard navigation and screen readers, so that I can access all features.

#### Acceptance Criteria

1. WHEN an examinee uses Tab key, THE Dashboard_Frontend SHALL navigate through interactive elements in logical order
2. WHEN an examinee presses Enter on a focused button, THE Dashboard_Frontend SHALL trigger the button action
3. WHEN the Dashboard_Frontend displays status badges, THE Dashboard_Frontend SHALL include aria-label attributes for screen readers
4. WHEN the Dashboard_Frontend displays tables, THE Dashboard_Frontend SHALL include proper table headers with scope attributes
5. WHEN the Dashboard_Frontend displays error messages, THE Dashboard_Frontend SHALL announce them to screen readers using aria-live
6. WHEN the Dashboard_Frontend displays loading states, THE Dashboard_Frontend SHALL show visual indicators and aria-busy attributes
7. WHEN the Dashboard_Frontend uses color to convey information, THE Dashboard_Frontend SHALL also use text or icons for color-blind users

