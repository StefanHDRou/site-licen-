<?php
header('Content-Type: application/json');

// Citim ce produs a scris adminul în căsuță
$data = json_decode(file_get_contents('php://input'), true);
$produs = isset($data['produs']) ? trim($data['produs']) : '';

if (empty($produs)) {
    echo json_encode(['error' => 'Nu ai scris numele produsului.']);
    exit;
}

// Creăm prompt-ul (Instrucțiunile stricte pentru AI)
$prompt = "Ești un expert în hardware PC. Generează o scurtă prezentare comercială (2-3 propoziții) și lista de specificații tehnice reale pentru produsul: '$produs'. 
Răspunde STRICT cu un obiect JSON valid, exact în acest format, fără niciun alt text pe lângă:
{
    \"descriere\": \"Descrierea atractivă a produsului aici...\",
    \"specificatii\": [
        {\"nume\": \"General\", \"valoare\": \"__SECTIUNE__\"},
        {\"nume\": \"Producător\", \"valoare\": \"Nume producător\"},
        {\"nume\": \"Tip componentă\", \"valoare\": \"Valoare\"},
        {\"nume\": \"Performanță\", \"valoare\": \"__SECTIUNE__\"},
        {\"nume\": \"Frecvență\", \"valoare\": \"Valoare\"}
    ]
}
Asigură-te că folosești valoarea '__SECTIUNE__' pentru a crea titluri logice de categorii (ex: General, Performanță, Conectivitate).";

// Trimitem cererea către Ollama-ul tău local
$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'ministral-3:8b', // Aici specificăm modelul tău
    'prompt' => $prompt,
    'stream' => false,
    'format' => 'json' // FORȚĂM OLLAMA SĂ RETURNEZE JSON CURAT
]));

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode(['error' => 'Eroare conexiune cu AI (Ollama este pornit?). Detalii: ' . curl_error($ch)]);
} else {
    $result = json_decode($response, true);
    // Returnăm exact răspunsul AI-ului (care e deja formatat JSON)
    echo $result['response'];
}

curl_close($ch);
?>