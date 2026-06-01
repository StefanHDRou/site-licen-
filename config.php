<?php
// Pornim sesiunea automat doar dacă nu este deja pornită
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- CONFIGURARE BAZĂ DE DATE ---
// Când vei muta site-ul pe internet, vei schimba DOAR aceste 4 rânduri!
$db_host = '127.0.0.1'; 
$db_name = 'db'; 
$db_user = 'root'; 
$db_pass = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) { 
    die("Eroare critică: Nu s-a putut stabili conexiunea cu baza de date."); 
}

// --- VARIABILE GLOBALE UTILE ---
$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';
?>