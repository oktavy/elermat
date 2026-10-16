<?php
/**
 * API Evaluasi Essay dengan AI Google Gemini
 * File: api/ai_grade.php
 * FITUR F: Koreksi Fleksibel (AI + Guru)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/gemini.php';

if (!is_logged_in() || !has_role(['guru', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Hanya Guru dan Admin yang dapat mengakses modul penilaian.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$answerId = $input['answer_id'] ?? null;
$prompt = $input['prompt'] ?? '';
$answer = $input['answer'] ?? '';

if (!$answerId) {
    echo json_encode(['success' => false, 'message' => 'ID Jawaban tidak valid.']);
    exit;
}

$db = getDB();

// Ambil data jawaban dan kemungkinan foto jawaban
$stmt = $db->prepare("SELECT * FROM submission_answers WHERE id = ?");
$stmt->execute([$answerId]);
$row = $stmt->fetch();

$photoData = $row['foto_jawaban'] ?? null;

// Jalankan evaluasi AI Gemini
$evalResult = evaluate_essay_ai($prompt, $answer, $photoData);

if ($evalResult['success']) {
    $score = $evalResult['score'];
    $feedback = $evalResult['feedback'];

    // Simpan hasil AI ke database
    $upd = $db->prepare("
        UPDATE submission_answers 
        SET ai_score = ?, ai_feedback = ?
        WHERE id = ?
    ");
    $upd->execute([$score, $feedback, $answerId]);

    echo json_encode([
        'success' => true,
        'score' => $score,
        'feedback' => $feedback,
        'is_mock' => $evalResult['is_mock'] ?? false
    ]);
} else {
    echo json_encode([
        'success' => false,
        'score' => 75,
        'feedback' => 'Evaluasi AI gagal. Silakan tentukan nilai manual.',
        'message' => $evalResult['error'] ?? 'Terjadi kesalahan sistem AI'
    ]);
}
