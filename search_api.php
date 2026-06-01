<?php
require 'config.php';
header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
    exit;
}

// Preluăm ce a scris utilizatorul
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Dacă a scris mai puțin de 2 litere, nu căutăm nimic (să nu stresăm baza de date degeaba)
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Căutăm în produse după nume (folosind LIKE) - returnăm doar primele 5 rezultate
$searchTerm = "%" . $query . "%";
$stmt = $mysqli->prepare("SELECT id, nume, pret, pret_vechi, imagine_url FROM produse WHERE nume LIKE ? LIMIT 5");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$produse = [];
while ($row = $result->fetch_assoc()) {
    // Logica pentru poze
    $imgName = $row['imagine_url'];
    $row['imagine_url'] = (!empty($imgName)) ? ((strpos($imgName, 'images') === 0) ? $imgName : "images/" . $imgName) : "https://placehold.co/100?text=Fara+Poza";
    
    // Formatam prețul
    $row['pret_formatat'] = number_format($row['pret'], 0, ',', '.');
    
    $produse[] = $row;
}

echo json_encode($produse);
?>