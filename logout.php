<?php
// 1. Pornim sesiunea ca să avem acces la ea
require 'config.php';

// 2. Golim toate variabilele de sesiune (array gol)
$_SESSION = array();

// 3. Dacă se folosesc cookies pentru sesiune, îl ștergem și pe cel din browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Distrugem sesiunea efectiv de pe server
session_destroy();

// 5. Redirecționăm utilizatorul către pagina principală sau login
header("Location: home.php");
exit;
?>