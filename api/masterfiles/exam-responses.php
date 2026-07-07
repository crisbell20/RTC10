<?php
/**
 * Admin exam responses API — rankings and per-student review for a single exam.
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin', 'CCMD'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/connection-pdo.php';
require_once __DIR__ . '/../includes/exam-code-utils.php';

function sendSuccess($data = []) {
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function fetchExamOrFail(PDO $pdo, int $examId): array {
    $stmt = $pdo->prepare('
        SELECT
            e.Exam_ID,
            e.Title,
            e.Description,
            e.Passing_Score,
            e.Duration,
            e.Status,
            e.Is_Archived,
            s.Subject_Name,
            s.Subject_Code,
            c.Course_ID,
            c.Course_Name,
            (SELECT COUNT(*) FROM tbl_exam_question eq WHERE eq.Exam_ID = e.Exam_ID) AS question_count
        FROM tbl_exam e
        INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
        INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
        WHERE e.Exam_ID = ?
    ');
    $stmt->execute([$examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        sendError('Exam not found', 404);
    }

    return $exam;
}

function countAssignedExaminees(PDO $pdo, int $examId): int {
    $stmt = $pdo->prepare('
        SELECT COUNT(DISTINCT ub.User_ID) AS total
        FROM tbl_exam_batch eb
        INNER JOIN tbl_user_batch ub ON eb.Batch_ID = ub.Batch_ID
        WHERE eb.Exam_ID = ?
          AND ub.Status = \'Active\'
    ');
    $stmt->execute([$examId]);
    return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

function applyCompetitionRank(array &$rows): void {
    $rank = 0;
    $position = 0;
    $prevKey = null;

    foreach ($rows as &$row) {
        $position++;
        $key = ($row['Percentage'] ?? 0) . '|' . ($row['Score'] ?? 0);
        if ($key !== $prevKey) {
            $rank = $position;
            $prevKey = $key;
        }
        $row['rank'] = $rank;
    }
    unset($row);
}

function formatTimeTaken(?string $timeStarted, ?string $timeEnded): string {
    if (!$timeStarted || !$timeEnded) {
        return 'N/A';
    }

    $diff = strtotime($timeEnded) - strtotime($timeStarted);
    if ($diff < 0) {
        return 'N/A';
    }

    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

function buildResultDetail(PDO $pdo, int $resultId, int $examId): array {
    $stmt = $pdo->prepare('
        SELECT
            r.Result_ID,
            r.Score,
            r.Percentage,
            r.Remarks,
            r.Submission_Date,
            e.Exam_ID,
            e.Title AS exam_title,
            e.Passing_Score,
            e.Duration,
            es.Session_ID,
            es.Time_Started,
            es.Time_Ended,
            u.User_ID,
            u.Fullname,
            u.Academic_Number,
            s.Subject_Name,
            c.Course_Name
        FROM tbl_result r
        INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
        INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
        INNER JOIN tbl_user u ON es.User_ID = u.User_ID
        LEFT JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
        LEFT JOIN tbl_course c ON s.Course_ID = c.Course_ID
        WHERE r.Result_ID = ?
          AND es.Exam_ID = ?
          AND es.Time_Ended IS NOT NULL
    ');
    $stmt->execute([$resultId, $examId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        sendError('Result not found for this exam', 404);
    }

    $result['time_taken'] = formatTimeTaken($result['Time_Started'], $result['Time_Ended']);
    $result['Duration_Minutes'] = $result['Duration'] ?? 0;

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tbl_exam_question WHERE Exam_ID = ?');
    $stmt->execute([$examId]);
    $result['total_questions'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare('
        SELECT
            eq.Question_ID,
            qb.Question_Text,
            qb.Question_Type,
            a.Answer_ID,
            a.Choice_ID AS user_answer_id,
            a.Is_Correct AS user_is_correct,
            c_user.Choice_Text AS user_answer_text
        FROM tbl_exam_question eq
        INNER JOIN tbl_question_bank qb ON eq.Question_ID = qb.Question_ID
        LEFT JOIN tbl_answer a ON eq.Question_ID = a.Question_ID AND a.Result_ID = ?
        LEFT JOIN tbl_choice c_user ON a.Choice_ID = c_user.Choice_ID
        WHERE eq.Exam_ID = ?
        ORDER BY eq.Question_ID
    ');
    $stmt->execute([$resultId, $examId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$question) {
        $stmt = $pdo->prepare('
            SELECT Choice_ID, Choice_Text, Is_Correct
            FROM tbl_choice
            WHERE Question_ID = ?
            ORDER BY Choice_ID
        ');
        $stmt->execute([$question['Question_ID']]);
        $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($question['choices'] as $choice) {
            if ($choice['Is_Correct']) {
                $question['correct_answer_id'] = $choice['Choice_ID'];
                $question['correct_answer_text'] = $choice['Choice_Text'];
                break;
            }
        }
    }
    unset($question);

    $result['questions'] = $questions;

    return $result;
}

$action = $_GET['action'] ?? '';
$examId = (int)($_GET['exam_id'] ?? 0);

try {
    if ($action === 'summary') {
        if (!$examId) {
            sendError('exam_id is required');
        }

        $exam = fetchExamOrFail($pdo, $examId);
        $assignedCount = countAssignedExaminees($pdo, $examId);

        $stmt = $pdo->prepare('
            SELECT
                COUNT(DISTINCT es.Session_ID) AS finished_count,
                AVG(r.Percentage) AS avg_percentage,
                MAX(r.Percentage) AS highest_percentage,
                MIN(r.Percentage) AS lowest_percentage,
                SUM(CASE WHEN r.Percentage >= e.Passing_Score THEN 1 ELSE 0 END) AS passed_count,
                SUM(CASE WHEN r.Percentage < e.Passing_Score THEN 1 ELSE 0 END) AS failed_count
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            WHERE es.Exam_ID = ?
              AND es.Time_Ended IS NOT NULL
        ');
        $stmt->execute([$examId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $finishedCount = (int)($stats['finished_count'] ?? 0);
        $passedCount = (int)($stats['passed_count'] ?? 0);

        sendSuccess([
            'exam' => $exam,
            'assigned_count' => $assignedCount,
            'finished_count' => $finishedCount,
            'not_submitted_count' => max(0, $assignedCount - $finishedCount),
            'passed_count' => $passedCount,
            'failed_count' => (int)($stats['failed_count'] ?? 0),
            'avg_percentage' => round((float)($stats['avg_percentage'] ?? 0), 1),
            'highest_percentage' => round((float)($stats['highest_percentage'] ?? 0), 1),
            'lowest_percentage' => $finishedCount > 0 ? round((float)($stats['lowest_percentage'] ?? 0), 1) : null,
            'pass_rate' => $finishedCount > 0 ? round(($passedCount / $finishedCount) * 100, 1) : 0
        ]);
    }

    if ($action === 'rankings') {
        if (!$examId) {
            sendError('exam_id is required');
        }

        fetchExamOrFail($pdo, $examId);

        $statusFilter = $_GET['status'] ?? 'all';
        $search = trim($_GET['search'] ?? '');

        $sql = '
            SELECT
                r.Result_ID,
                es.Session_ID,
                u.User_ID,
                u.Fullname,
                u.Academic_Number,
                r.Score,
                r.Percentage,
                r.Remarks,
                r.Submission_Date,
                es.Time_Started,
                es.Time_Ended,
                e.Passing_Score,
                (
                    SELECT GROUP_CONCAT(DISTINCT b.Batch_Name ORDER BY b.Batch_Name SEPARATOR \', \')
                    FROM tbl_exam_batch eb
                    INNER JOIN tbl_user_batch ub ON eb.Batch_ID = ub.Batch_ID AND ub.User_ID = u.User_ID AND ub.Status = \'Active\'
                    INNER JOIN tbl_batch b ON eb.Batch_ID = b.Batch_ID
                    WHERE eb.Exam_ID = es.Exam_ID
                ) AS batch_names
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_user u ON es.User_ID = u.User_ID
            WHERE es.Exam_ID = ?
              AND es.Time_Ended IS NOT NULL
        ';
        $params = [$examId];

        if ($statusFilter === 'passed') {
            $sql .= ' AND r.Percentage >= e.Passing_Score';
        } elseif ($statusFilter === 'failed') {
            $sql .= ' AND r.Percentage < e.Passing_Score';
        }

        if ($search !== '') {
            $sql .= ' AND (u.Fullname LIKE ? OR u.Academic_Number LIKE ? OR u.Username LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY r.Percentage DESC, r.Score DESC, r.Submission_Date ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        applyCompetitionRank($rows);

        foreach ($rows as &$row) {
            $row['status'] = ((float)$row['Percentage'] >= (float)$row['Passing_Score']) ? 'Passed' : 'Failed';
            $row['time_taken'] = formatTimeTaken($row['Time_Started'], $row['Time_Ended']);
            $row['Score'] = (float)$row['Score'];
            $row['Percentage'] = round((float)$row['Percentage'], 2);
        }
        unset($row);

        sendSuccess($rows);
    }

    if ($action === 'not_submitted') {
        if (!$examId) {
            sendError('exam_id is required');
        }

        fetchExamOrFail($pdo, $examId);

        $stmt = $pdo->prepare('
            SELECT DISTINCT
                u.User_ID,
                u.Fullname,
                u.Academic_Number,
                u.Username,
                (
                    SELECT GROUP_CONCAT(DISTINCT b.Batch_Name ORDER BY b.Batch_Name SEPARATOR \', \')
                    FROM tbl_exam_batch eb
                    INNER JOIN tbl_user_batch ub2 ON eb.Batch_ID = ub2.Batch_ID AND ub2.User_ID = u.User_ID AND ub2.Status = \'Active\'
                    INNER JOIN tbl_batch b ON eb.Batch_ID = b.Batch_ID
                    WHERE eb.Exam_ID = ?
                ) AS batch_names
            FROM tbl_exam_batch eb
            INNER JOIN tbl_user_batch ub ON eb.Batch_ID = ub.Batch_ID
            INNER JOIN tbl_user u ON ub.User_ID = u.User_ID
            WHERE eb.Exam_ID = ?
              AND ub.Status = \'Active\'
              AND u.User_ID NOT IN (
                  SELECT es.User_ID
                  FROM tbl_exam_session es
                  INNER JOIN tbl_result r ON r.Session_ID = es.Session_ID
                  WHERE es.Exam_ID = ?
                    AND es.Time_Ended IS NOT NULL
              )
            ORDER BY u.Fullname ASC
        ');
        $stmt->execute([$examId, $examId, $examId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccess($rows);
    }

    if ($action === 'detail') {
        $resultId = (int)($_GET['result_id'] ?? 0);
        if (!$examId || !$resultId) {
            sendError('exam_id and result_id are required');
        }

        fetchExamOrFail($pdo, $examId);
        $detail = buildResultDetail($pdo, $resultId, $examId);

        logExamAuditAction(
            $pdo,
            (int)$_SESSION['user_id'],
            'VIEW_EXAM_RESPONSE',
            "Exam {$examId} result {$resultId} viewed by admin"
        );

        sendSuccess($detail);
    }

    sendError('Invalid action');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
