<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin', 'CCMD'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/connection-pdo.php';

$action = $_GET['action'] ?? '';

try {
    // Get key metrics
    if ($action === 'metrics') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        // Build date filter
        $dateFilter = '';
        if ($dateRange !== 'all') {
            $dateFilter = "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)";
        }

        // Build course/subject filters
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';
        $subjectFilter = $subjectId ? "AND s.Subject_ID = $subjectId" : '';

        // Total examinees who took exams
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT es.User_ID) as total
            FROM tbl_exam_session es
            INNER JOIN tbl_result r ON es.Session_ID = r.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
        ");
        $totalExaminees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total exams created
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT e.Exam_ID) AS total
    FROM tbl_exam e
    INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
    INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
    WHERE 1=1 $courseFilter $subjectFilter
");
$totalExams = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Completion rate (sessions with results / total sessions)
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT es.Session_ID) as total_sessions,
                COUNT(DISTINCT r.Session_ID) as completed_sessions
            FROM tbl_exam_session es
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            LEFT JOIN tbl_result r ON es.Session_ID = r.Session_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
        ");
        $completion = $stmt->fetch(PDO::FETCH_ASSOC);
        $completionRate = $completion['total_sessions'] > 0 
            ? round(($completion['completed_sessions'] / $completion['total_sessions']) * 100, 1)
            : 0;

        // Average score
        $stmt = $pdo->query("
            SELECT AVG(r.Percentage) as avg_score
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
        ");
        $avgScore = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'] ?? 0, 1);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_examinees' => $totalExaminees,
                'total_exams' => $totalExams,
                'completion_rate' => $completionRate,
                'avg_score' => $avgScore
            ]
        ]);
        exit;
    }

    // Get performance trend data
    if ($action === 'performance_trend') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        $dateFilter = $dateRange !== 'all' 
            ? "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)" 
            : '';
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';
        $subjectFilter = $subjectId ? "AND s.Subject_ID = $subjectId" : '';

        $stmt = $pdo->query("
            SELECT 
                DATE(r.Submission_Date) as date,
                AVG(r.Percentage) as avg_score,
                COUNT(*) as exam_count
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
            GROUP BY DATE(r.Submission_Date)
            ORDER BY date ASC
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Get pass/fail distribution
    if ($action === 'pass_fail') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        $dateFilter = $dateRange !== 'all' 
            ? "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)" 
            : '';
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';
        $subjectFilter = $subjectId ? "AND s.Subject_ID = $subjectId" : '';

        $stmt = $pdo->query("
            SELECT 
                SUM(CASE WHEN r.Percentage >= e.Passing_Score THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN r.Percentage < e.Passing_Score THEN 1 ELSE 0 END) as failed
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
        ");

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Get top performing subjects
    if ($action === 'top_subjects') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;

        $dateFilter = $dateRange !== 'all' 
            ? "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)" 
            : '';
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';

        $stmt = $pdo->query("
            SELECT 
                s.Subject_Name,
                AVG(r.Percentage) as avg_score,
                COUNT(*) as exam_count
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter
            GROUP BY s.Subject_ID, s.Subject_Name
            ORDER BY avg_score DESC
            LIMIT 10
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Get exam participation
    if ($action === 'participation') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        $dateFilter = $dateRange !== 'all' 
            ? "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)" 
            : '';
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';
        $subjectFilter = $subjectId ? "AND s.Subject_ID = $subjectId" : '';

        $stmt = $pdo->query("
            SELECT 
                e.Title as exam_title,
                COUNT(DISTINCT es.User_ID) as participant_count
            FROM tbl_exam_session es
            INNER JOIN tbl_result r ON es.Session_ID = r.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
            GROUP BY e.Exam_ID, e.Title
            ORDER BY participant_count DESC
            LIMIT 10
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Get detailed results
    if ($action === 'detailed_results') {
        $dateRange = $_GET['date_range'] ?? '30';
        $courseId = $_GET['course_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;

        $dateFilter = $dateRange !== 'all' 
            ? "AND r.Submission_Date >= DATE_SUB(NOW(), INTERVAL $dateRange DAY)" 
            : '';
        $courseFilter = $courseId ? "AND c.Course_ID = $courseId" : '';
        $subjectFilter = $subjectId ? "AND s.Subject_ID = $subjectId" : '';

        $stmt = $pdo->query("
            SELECT 
                u.Fullname as examinee_name,
                e.Title as exam_title,
                s.Subject_Name,
                r.Submission_Date,
                r.Score,
                r.Percentage,
                CASE 
                    WHEN r.Percentage >= e.Passing_Score THEN 'Passed'
                    ELSE 'Failed'
                END as status,
                e.Passing_Score
            FROM tbl_result r
            INNER JOIN tbl_exam_session es ON r.Session_ID = es.Session_ID
            INNER JOIN tbl_exam e ON es.Exam_ID = e.Exam_ID
            INNER JOIN tbl_subject s ON e.Subject_ID = s.Subject_ID
            INNER JOIN tbl_course c ON s.Course_ID = c.Course_ID
            INNER JOIN tbl_user u ON es.User_ID = u.User_ID
            WHERE 1=1 $dateFilter $courseFilter $subjectFilter
            ORDER BY r.Submission_Date DESC
            LIMIT 100
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
