<?php
/**
 * Submit Exam API
 * Ends the exam session and calculates the final score
 */

header('Content-Type: application/json');
session_start();

require_once '../config/connection-pdo.php';

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

// Validate session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Examinee') {
    sendError('Unauthorized', 401);
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$session_id = $input['session_id'] ?? null;

if (!$session_id) {
    sendError('Missing session_id');
}

try {
    $pdo->beginTransaction();
    
    // Verify session
    $stmt = $pdo->prepare('SELECT User_ID, Exam_ID, Time_Ended FROM tbl_exam_session WHERE Session_ID = ?');
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();
    
    if (!$session || $session['User_ID'] != $userId) {
        $pdo->rollBack();
        sendError('Invalid session', 403);
    }
    
    if ($session['Time_Ended']) {
        $pdo->rollBack();
        sendError('Exam already submitted', 400);
    }
    
    // End the session
    $stmt = $pdo->prepare('UPDATE tbl_exam_session SET Time_Ended = NOW() WHERE Session_ID = ?');
    $stmt->execute([$session_id]);
    
    // Calculate score
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total_questions
        FROM tbl_exam_question
        WHERE Exam_ID = ?
    ');
    $stmt->execute([$session['Exam_ID']]);
    $total = $stmt->fetch();
    $total_questions = $total['total_questions'];
    
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as correct_answers
        FROM tbl_answer
        WHERE Session_ID = ? AND Is_Correct = 1
    ');
    $stmt->execute([$session_id]);
    $correct = $stmt->fetch();
    $correct_answers = $correct['correct_answers'];
    
    $score = $correct_answers;
    $percentage = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;
    
    // Update or create result
    $stmt = $pdo->prepare('SELECT Result_ID FROM tbl_result WHERE Session_ID = ?');
    $stmt->execute([$session_id]);
    $result = $stmt->fetch();
    
    // Get passing score
    $stmt = $pdo->prepare('SELECT Passing_Score FROM tbl_exam WHERE Exam_ID = ?');
    $stmt->execute([$session['Exam_ID']]);
    $exam = $stmt->fetch();
    $passing_score = $exam['Passing_Score'] ?? 50;
    
    $remarks = $percentage >= $passing_score ? 'Passed' : 'Failed';
    
    if ($result) {
        $stmt = $pdo->prepare('UPDATE tbl_result SET Score = ?, Percentage = ?, Remarks = ?, Submission_Date = NOW() WHERE Result_ID = ?');
        $stmt->execute([$score, $percentage, $remarks, $result['Result_ID']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tbl_result (Session_ID, Score, Percentage, Remarks, Submission_Date) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$session_id, $score, $percentage, $remarks]);
    }
    
    $pdo->commit();
    
    sendSuccess([
        'score' => $score,
        'total_questions' => $total_questions,
        'percentage' => round($percentage, 2),
        'remarks' => $remarks,
        'passing_score' => $passing_score
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in submit-exam.php: " . $e->getMessage());
    sendError('Failed to submit exam', 500);
}
?>
