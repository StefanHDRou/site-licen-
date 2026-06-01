<?php
require 'config.php';
set_time_limit(300); 
header('Content-Type: application/json');

// 1. Primim ISTORICUL, nu doar ultimul mesaj
$input = json_decode(file_get_contents('php://input'), true);
$history = $input['history'] ?? [];

// Validare simplă
if (empty($history)) {
    echo json_encode(['reply' => 'Nu am primit date.']);
    exit;
}


// 3. EXTRAGEREA DATELOR (RAG)
$sql = "
    SELECT 
        p.nume AS produs,
        p.pret,
        p.stoc,
        c.nume AS categorie,
        GROUP_CONCAT(CONCAT(s.nume_specificatie, ': ', s.valoare_specificatie) SEPARATOR ', ') as specificatii_tehnice
    FROM produse p
    LEFT JOIN categorii c ON p.categorie_id = c.id
    LEFT JOIN specificatii_produse s ON p.id = s.produs_id
    GROUP BY p.id
";

$result = $mysqli->query($sql);

$contextData = "";
while ($row = $result->fetch_assoc()) {
    $stareStoc = ($row['stoc'] > 0) ? "În stoc ({$row['stoc']} buc)" : "Indisponibil";
    $contextData .= "PRODUS: " . $row['produs'] . "\n";
    $contextData .= " - Categorie: " . $row['categorie'] . "\n";
    $contextData .= " - Preț: " . $row['pret'] . " RON\n";
    $contextData .= " - Specificații: " . ($row['specificatii_tehnice'] ?? 'Standard') . "\n";
    $contextData .= "-----------------------------------\n";
}

// 4. CONSTRUIREA PROMPTULUI DE SISTEM
// Acesta va fi mereu PRIMUL mesaj pe care îl "aude" AI-ul, ca să știe cine e.
$systemMessage = [
    "role" => "system",
    "content" => "Ești un asistent de vânzări PC Shop. Răspunde în română.
Folosește DOAR datele de mai jos. Memorează contextul discuției anterioare.
INVENTAR:\n" . $contextData
];

// 5. COMBINAREA MESAJELOR
// Structura finală va fi: [SYSTEM_PROMPT, USER_MSG_1, AI_MSG_1, USER_MSG_2 ...]
$finalMessages = array_merge([$systemMessage], $history);

// 6. TRIMITEREA CĂTRE OLLAMA
$modelName = 'ministral-3:8b'; // Verifică numele!
$apiUrl = "http://127.0.0.1:11434/api/chat";

$payload = [
    "model" => $modelName,
    "messages" => $finalMessages, // Trimitem tot pachetul
    "stream" => false,
    "options" => ["temperature" => 0.7]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['reply' => 'Eroare Ollama: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['message']['content'])) {
    echo json_encode(['reply' => $data['message']['content']]);
} else {
    echo json_encode(['reply' => 'Eroare generare răspuns.']);
}
?>