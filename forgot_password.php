<?php
require 'config.php';
// INCLUDEM FUNCȚIA DE TRIMITERE EMAIL
require 'send_email.php'; 

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mysqli->real_escape_string($_POST['email']);

    $result = $mysqli->query("SELECT id, nume FROM users WHERE email='$email'");
    
    if ($user = $result->fetch_assoc()) {
        $new_password_raw = substr(bin2hex(random_bytes(10)), 0, 8);
        $new_password_hash = password_hash($new_password_raw, PASSWORD_DEFAULT);
        
        $stmt = $mysqli->prepare("UPDATE users SET parola = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password_hash, $email);
        
        if ($stmt->execute()) {
            // --- TEXTUL EMAILULUI ADAPTAT PENTRU NOUL FLUX ---
            $subiect = "Resetare Parola - PC Shop";
            $continut = "
                <h3>Salut {$user['nume']},</h3>
                <p>Am primit o cerere de resetare a parolei.</p>
                <p>Noua ta parolă temporară este: <b style='font-size:18px; color:#9b59b6;'>$new_password_raw</b></p>
                <p>Folosește această parolă pentru a-ți seta o parolă nouă, definitivă, pe pagina de autentificare.</p>
                <br>
                <small>Echipa PC Shop</small>
            ";

            // Apelăm funcția din send_email.php
            if (trimiteEmail($email, $user['nume'], $subiect, $continut)) {
                // REDIRECT AUTOMAT CĂTRE LOGIN CU FORMULARUL DE RESETARE DESCHIS
                header("Location: login.php?reset=1");
                exit;
            } else {
                $message = "Eroare la trimiterea emailului. Verifică conexiunea la internet.";
                $messageType = "error";
            }
        }
    } else {
        $message = "Acest email nu există în baza noastră de date.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Resetare Parolă - PC Shop</title>
    <style>
        body { background-color: #121212; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-container { background: #1e1e1e; padding: 40px; border-radius: 12px; border: 1px solid #333; width: 350px; text-align: center; }
        h2 { color: #9b59b6; margin-top: 0; }
        p { color: #aaa; font-size: 14px; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.3s; }
        button:hover { background: #8e44ad; }
        .back-link { display: block; margin-top: 15px; color: #aaa; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #fff; }
        
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .error { background: #c0392b; color: white; }
    </style>
</head>
<body>

<div class="reset-container">
    <h2>Ai uitat parola?</h2>
    <p>Introdu adresa de email și îți vom trimite o parolă nouă temporară.</p>

    <?php if($message): ?>
        <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Adresa ta de email" required>
        <button type="submit">Resetează Parola</button>
    </form>

    <a href="login.php" class="back-link">&larr; Înapoi la Autentificare</a>
</div>

</body>
</html>