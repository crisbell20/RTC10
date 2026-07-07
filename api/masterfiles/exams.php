<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin', 'CCMD'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/connection-pdo.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? '');

try {
    // List exams, optional filters: course_id, subject_id
    if ($request_method === 'GET' && $action === 'list') {
        $course_id = $_GET['course_id'] ?? null;
        $subject_id = $_GET['subject_id'] ?? null;

        $sql = "
            SELECT e.Exam_ID, e.User_ID, e.Subject_ID, e.Title, e.Description,
                   e.Schedule_Date, e.Deadline, e.Duration, e.Passing_Score, e.Status,
                   e.Is_Randomized, e.Time_Limit,
                   s.Subject_Name, s.Subject_Code, s.Course_ID,
                   c.Course_Name,
                   COUNT(DISTINCT eq.Question_ID) as Question_Count,
                   COUNT(DISTINCT eb.Batch_ID) as Batch_Count
            FROM tbl_exam e
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            LEFT JOIN tbl_exam_question eq ON e.Exam_ID = eq.Exam_ID
            LEFT JOIN tbl_exam_batch eb ON e.Exam_ID = eb.Exam_ID
            WHERE 1=1
        ";
        $params = [];
        if ($course_id) {
            $sql .= " AND s.Course_ID = ?";
            $params[] = $course_id;
        }
        if ($subject_id) {
            $sql .= " AND e.Subject_ID = ?";
            $params[] = $subject_id;
        }
        $sql .= " GROUP BY e.Exam_ID";
        $sql .= " ORDER BY c.Course_Name, s.Subject_Name, e.Title";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $exams]);
        exit;
    }

    // Add exam
    if ($request_method === 'POST' && $action === 'add') {
        $subject_id = $input['subject_id'] ?? $_POST['subject_id'] ?? '';
        $title = $input['title'] ?? $_POST['title'] ?? '';
        $description = $input['description'] ?? $_POST['description'] ?? null;
        $schedule_date = $input['schedule_date'] ?? $_POST['schedule_date'] ?? null;
        $deadline = $input['deadline'] ?? $_POST['deadline'] ?? null;
        $duration = $input['duration'] ?? $_POST['duration'] ?? null;
        $passing_score = $input['passing_score'] ?? $_POST['passing_score'] ?? null;
        $status = $input['status'] ?? $_POST['status'] ?? 'Draft';
        $is_randomized = (int)($input['is_randomized'] ?? $_POST['is_randomized'] ?? 0);
        $time_limit = $input['time_limit'] ?? $_POST['time_limit'] ?? null;
        $user_id = $_SESSION['user_id'];

        if (empty($subject_id) || empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Subject and title are required']);
            exit;
        }

        $stmt = $pdo->prepare('
            INSERT INTO tbl_exam
            (User_ID, Subject_ID, Title, Description, Schedule_Date, Deadline, Duration,
             Passing_Score, Status, Is_Randomized, Time_Limit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user_id,
            $subject_id,
            $title,
            $description,
            $schedule_date ?: null,
            $deadline ?: null,
            $duration ?: null,
            $passing_score ?: null,
            $status,
            $is_randomized,
            $time_limit ?: null
        ]);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Exam added', 'exam_id' => $pdo->lastInsertId()]);
        exit;
    }

    // Update exam
    if ($request_method === 'POST' && $action === 'update') {
        $exam_id = $input['exam_id'] ?? $_POST['exam_id'] ?? '';
        $subject_id = $input['subject_id'] ?? $_POST['subject_id'] ?? '';
        $title = $input['title'] ?? $_POST['title'] ?? '';
        $description = $input['description'] ?? $_POST['description'] ?? null;
        $schedule_date = $input['schedule_date'] ?? $_POST['schedule_date'] ?? null;
        $deadline = $input['deadline'] ?? $_POST['deadline'] ?? null;
        $duration = $input['duration'] ?? $_POST['duration'] ?? null;
        $passing_score = $input['passing_score'] ?? $_POST['passing_score'] ?? null;
        $status = $input['status'] ?? $_POST['status'] ?? 'Draft';
        $is_randomized = (int)($input['is_randomized'] ?? $_POST['is_randomized'] ?? 0);
        $time_limit = $input['time_limit'] ?? $_POST['time_limit'] ?? null;

        if (empty($exam_id) || empty($subject_id) || empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exam ID, subject and title are required']);
            exit;
        }

        // Validation for publishing exams (Requirements 15.1, 15.2, 15.3, 15.4, 15.5)
        if ($status === 'Published') {
            // Check question count
            $stmt = $pdo->prepare('SELECT COUNT(*) as question_count FROM tbl_exam_question WHERE Exam_ID = ?');
            $stmt->execute([$exam_id]);
            $question_count = $stmt->fetch(PDO::FETCH_ASSOC)['question_count'];

            if ($question_count == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot publish exam without questions']);
                exit;
            }

            // Check batch count
            $stmt = $pdo->prepare('SELECT COUNT(*) as batch_count FROM tbl_exam_batch WHERE Exam_ID = ?');
            $stmt->execute([$exam_id]);
            $batch_count = $stmt->fetch(PDO::FETCH_ASSOC)['batch_count'];

            if ($batch_count == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot publish exam without batch assignments']);
                exit;
            }
        }

        $stmt = $pdo->prepare('
            UPDATE tbl_exam
               SET Subject_ID = ?,
                   Title = ?,
                   Description = ?,
                   Schedule_Date = ?,
                   Deadline = ?,
                   Duration = ?,
                   Passing_Score = ?,
                   Status = ?,
                   Is_Randomized = ?,
                   Time_Limit = ?
             WHERE Exam_ID = ?
        ');
        $stmt->execute([
            $subject_id,
            $title,
            $description,
            $schedule_date ?: null,
            $deadline ?: null,
            $duration ?: null,
            $passing_score ?: null,
            $status,
            $is_randomized,
            $time_limit ?: null,
            $exam_id
        ]);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Exam updated']);
        exit;
    }

    // Delete exam
    if ($request_method === 'POST' && $action === 'delete') {
        $exam_id = $input['exam_id'] ?? $_POST['exam_id'] ?? '';

        if (empty($exam_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exam ID is required']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM tbl_exam WHERE Exam_ID = ?');
        $stmt->execute([$exam_id]);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Exam deleted']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

