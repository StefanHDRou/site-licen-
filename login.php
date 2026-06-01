<?php
require 'config.php';
// Dacă e deja logat, îl trimitem la checkout
if (isset($_SESSION['user_id'])) {
    header('Location: checkout.php');
    exit;
}

$error = '';
$success = '';

// Verificăm dacă vine din pagina de forgot_password
$showResetForm = (isset($_GET['reset']) && $_GET['reset'] == '1');

// --- LOGICA DE LOGIN / REGISTER / RESETARE PAROLĂ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 1. SETARE PAROLĂ NOUĂ (CU PAROLĂ TEMPORARĂ) ---
    if (isset($_POST['set_new_password'])) {
        $email = $mysqli->real_escape_string($_POST['email']);
        $temp_pass = $_POST['temp_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $mysqli->prepare("SELECT id, nume, parola, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($temp_pass, $row['parola'])) {
                if ($new_pass === $confirm_pass) {
                    if (strlen($new_pass) >= 6) {
                        $newHash = password_hash($new_pass, PASSWORD_DEFAULT);
                        $upd = $mysqli->prepare("UPDATE users SET parola = ? WHERE id = ?");
                        $upd->bind_param("si", $newHash, $row['id']);
                        
                        if ($upd->execute()) {
                            // Îl logăm automat
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['user_name'] = $row['nume'];
                            $_SESSION['role'] = $row['role'];
                            
                            header("Location: checkout.php");
                            exit;
                        }
                    } else {
                        $error = "Noua parolă trebuie să aibă minim 6 caractere.";
                        $showResetForm = true; // Ținem formularul de reset deschis dacă e eroare
                    }
                } else {
                    $error = "Parolele noi nu coincid.";
                    $showResetForm = true;
                }
            } else {
                $error = "Email sau Parolă temporară incorectă.";
                $showResetForm = true;
            }
        } else {
            $error = "Acest email nu există în sistem.";
            $showResetForm = true;
        }
    } 
    
    // --- 2. LOGIN SAU REGISTER NORMAL ---
    elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        $email = $mysqli->real_escape_string($_POST['email']);
        $password = $_POST['password'];

        if ($action === 'login') {
            $result = $mysqli->query("SELECT * FROM users WHERE email='$email'");
            if ($userData = $result->fetch_assoc()) {
                if (password_verify($password, $userData['parola'])) {
                    $_SESSION['user_id'] = $userData['id'];
                    $_SESSION['user_name'] = $userData['nume'];
                    $_SESSION['role'] = $userData['role'];

                    header('Location: checkout.php');
                    exit;
                } else {
                    $error = "Parolă incorectă!";
                }
            } else {
                $error = "Utilizatorul nu există!";
            }
        } elseif ($action === 'register') {
            $nume = $mysqli->real_escape_string($_POST['nume']);
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (nume, email, parola) VALUES ('$nume', '$email', '$hashedPass')";
            if ($mysqli->query($sql)) {
                $_SESSION['user_id'] = $mysqli->insert_id;
                $_SESSION['user_name'] = $nume;
                header('Location: checkout.php');
                exit;
            } else {
                $error = "Eroare la înregistrare (posibil email existent).";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Login / Register - PC Shop</title>
    <style>
        body { background-color: #121212; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-container { background: #1e1e1e; padding: 40px; border-radius: 10px; border: 1px solid #333; width: 350px; position: relative; }
        
        .logo-top { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #fff; text-decoration: none; display: block; }
        .logo-top span { color: #9b59b6; }
        
        h2 { text-align: center; color: #9b59b6; margin-top: 0; }
        p.info-text { color: #aaa; font-size: 13px; text-align: center; margin-bottom: 20px; line-height: 1.4; }
        
        label { font-size: 13px; color: #aaa; margin-left: 2px; }
        input { width: 100%; padding: 10px; margin: 5px 0 15px 0; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 4px; box-sizing: border-box; }
        
        button { width: 100%; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.3s; }
        button:hover { background: #8e44ad; }
        
        .switch-btn { background: none; color: #aaa; margin-top: 15px; font-size: 13px; text-decoration: underline; padding: 0; text-align: center; display: block; margin-left: auto; margin-right: auto; }
        .switch-btn:hover { color: #fff; }
        
        .error { color: #e74c3c; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        .success-msg { color: #27ae60; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 14px; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="home.php" class="logo-top">PC <span>SHOP</span></a>

        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <?php if($showResetForm && !$error) echo "<div class='success-msg'>Parola temporară a fost trimisă! Verifică email-ul.</div>"; ?>

        <div id="loginForm" class="<?php echo $showResetForm ? 'hidden' : ''; ?>">
            <h2>Autentificare</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Parolă" required>
                
                <div style="text-align: right; margin-bottom: 10px; margin-top: -5px;">
                    <a href="forgot_password.php" style="color: #9b59b6; text-decoration: none; font-size: 13px;">Ai uitat parola?</a>
                </div>

                <button type="submit">Intră în cont</button>
            </form>
            
            <button class="switch-btn" onclick="toggleForms(event, 'registerForm')">Nu ai cont? Înregistrează-te</button>
        </div>

        <div id="registerForm" class="hidden">
            <h2>Înregistrare</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <input type="text" name="nume" placeholder="Nume Complet" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Parolă (Minim 6 caractere)" required minlength="6">
                <button type="submit">Creează cont</button>
            </form>
            <button class="switch-btn" onclick="toggleForms(event, 'loginForm')">Ai deja cont? Loghează-te</button>
        </div>

        <div id="tempPasswordForm" class="<?php echo $showResetForm ? '' : 'hidden'; ?>">
            <h2 style="color: #e74c3c;">Setați o parolă nouă</h2>
            <p class="info-text">
                Introdu parola temporară primită pe email pentru a-ți putea seta parola definitivă.
            </p>
            <form method="POST" action="login.php?reset=1">
                <label>Email-ul contului</label>
                <input type="email" name="email" required placeholder="exemplu@email.com">

                <label>Parola Temporară</label>
                <input type="password" name="temp_password" required placeholder="Parola primită pe mail">

                <label>Noua Parolă</label>
                <input type="password" name="new_password" required placeholder="Minim 6 caractere" minlength="6">

                <label>Confirmă Noua Parolă</label>
                <input type="password" name="confirm_password" required placeholder="Repetă noua parolă" minlength="6">

                <button type="submit" name="set_new_password" style="background: #e74c3c;">Salvează și Autentifică-mă</button>
            </form>
            <button class="switch-btn" onclick="toggleForms(event, 'loginForm')">&larr; Înapoi la Autentificare standard</button>
        </div>

    </div>

    <script>
        // Funcție universală pentru a comuta între orice formulare
        function toggleForms(e, targetId) {
            e.preventDefault();
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('tempPasswordForm').classList.add('hidden');
            
            document.getElementById(targetId).classList.remove('hidden');
        }
    </script>
</body>
</html>