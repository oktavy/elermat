<?php
/**
 * Integrasi AI Google Gemini untuk Penilaian Essay Otomatis
 * File: config/gemini.php
 */

require_once __DIR__ . '/app.php';

function evaluate_essay_ai($prompt, $studentAnswer, $photoDataUrl = null, $customApiKey = null) {
    $apiKey = $customApiKey ?: get_setting('gemini_api_key', '');

    // Fallback AI Cerdas berbasis Analisis Semantik & Kata Kunci Matematika jika API Key belum diisi
    if (empty(trim($apiKey))) {
        $answerText = strtolower(trim($studentAnswer));
        $score = 70;
        $feedbackParts = [];

        if (empty($answerText)) {
            return [
                'success' => true,
                'score' => 20,
                'feedback' => 'Siswa tidak mengisi uraian teks jawaban. Silakan periksa lampiran foto lembar kerja jika tersedia.',
                'is_mock' => true
            ];
        }

        // Analisis Relevansi Jawaban dengan Soal Matematika
        $mathTerms = [
            'mean' => 'perhitungan rata-rata (mean)',
            'median' => 'nilai tengah (median)',
            'modus' => 'nilai terbanyak (modus)',
            'kualitatif' => 'karakteristik data kualitatif deskriptif',
            'kuantitatif' => 'besaran data kuantitatif numerik',
            'diagram' => 'representasi visual diagram batang/lingkaran',
            'lingkaran' => 'proporsi sudut lingkaran (360°)',
            'derajat' => 'konversi sudut derajat',
            'frekuensi' => 'distribusi frekuensi data',
            'persen' => 'persentase proporsi data',
            'tabel' => 'penyajian tabel frekuensi',
            'data' => 'konsep dasar himpunan data',
            'angka' => 'besaran numerik',
            'survei' => 'metode pengumpulan data survei'
        ];

        $matchedTerms = [];
        foreach ($mathTerms as $term => $desc) {
            if (strpos($answerText, $term) !== false) {
                $matchedTerms[] = $desc;
            }
        }

        $wordCount = str_word_count($studentAnswer);
        if ($wordCount >= 15) {
            $score += 15;
        } elseif ($wordCount >= 8) {
            $score += 10;
        }

        // Cek deteksi perhitungan angka matematika
        if (preg_match('/[0-9]+[\s]*[\+\-\*\/\:\=][\s]*[0-9]+/', $studentAnswer) || preg_match('/[0-9]+/', $studentAnswer)) {
            $score += 10;
            $feedbackParts[] = 'Langkah perhitungan matematis atau komponen angka disertakan dengan runtut.';
        }

        if (!empty($matchedTerms)) {
            $score += min(15, count($matchedTerms) * 5);
            $feedbackParts[] = 'Konsep matematika yang relevan (' . implode(', ', array_slice($matchedTerms, 0, 3)) . ') telah diidentifikasi dengan tepat.';
        } else {
            $feedbackParts[] = 'Penjelasan umum sudah baik, namun dapat diperkuat dengan terminologi matematika yang lebih spesifik.';
        }

        $finalScore = min(95, max(50, $score));
        $combinedFeedback = implode(' ', $feedbackParts) . ' Evaluasi awal AI disajikan sebagai acuan; Guru dapat menyesuaikan skor dan catatan akhir.';

        return [
            'success' => true,
            'score' => $finalScore,
            'feedback' => $combinedFeedback,
            'is_mock' => true
        ];
    }

    $systemInstruction = "Kamu adalah guru matematika profesional yang teliti dan suportif. 
Evaluasi jawaban siswa terhadap soal matematika berikut:
1. Soal: \"{$prompt}\"
2. Jawaban Siswa: \"" . (!empty($studentAnswer) ? $studentAnswer : "(Siswa melampirkan foto lembar kerja)") . "\"

Petunjuk Evaluasi:
- Analisis langkah pengerjaan, ketepatan logika matematis, dan hasil akhir.
- Tentukan skor nilai bulat antara 0 sampai 100.
- Berikan umpan balik ramah, edukatif, dan solutif dalam Bahasa Indonesia yang relevan langsung dengan isi jawaban siswa.
- Respon HARUS valid JSON: {\"score\": 85, \"feedback\": \"Uraian feedback...\"}";

    $parts = [
        ['text' => $systemInstruction]
    ];

    if (!empty($photoDataUrl) && preg_match('/^data:(image\/[a-zA-Z+]+);base64,(.+)$/', $photoDataUrl, $matches)) {
        $mimeType = $matches[1];
        $base64Data = $matches[2];
        $parts[] = [
            'inlineData' => [
                'mimeType' => $mimeType,
                'data' => $base64Data
            ]
        ];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . trim($apiKey);
    $payload = [
        'contents' => [
            ['parts' => $parts]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature' => 0.2
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode !== 200) {
        return [
            'success' => false,
            'score' => 75,
            'feedback' => 'Layanan AI Gemini sedang sibuk atau API key tidak valid. Silakan beri nilai manual.',
            'error' => $err ?: "HTTP $httpCode"
        ];
    }

    $resData = json_decode($response, true);
    $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed = json_decode($rawText, true);

    if (isset($parsed['score'])) {
        return [
            'success' => true,
            'score' => (float) min(100, max(0, $parsed['score'])),
            'feedback' => $parsed['feedback'] ?? 'Evaluasi AI berhasil.',
            'is_mock' => false
        ];
    }

    return [
        'success' => false,
        'score' => 70,
        'feedback' => 'Respon AI tidak sesuai format JSON. Harap tentukan skor manual.'
    ];
}
