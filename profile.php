<?php
require 'config.php';

// 1. Verificare Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = ""; 

// 3. LOGICĂ DE ACTUALIZARE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Actualizare Date Personale
    if (isset($_POST['update_info'])) {
        $telefon = $_POST['telefon'];
        $adresa = $_POST['adresa'];
        
        $stmt = $mysqli->prepare("UPDATE users SET telefon = ?, adresa = ? WHERE id = ?");
        $stmt->bind_param("ssi", $telefon, $adresa, $user_id);
        if ($stmt->execute()) {
            $message = "Datele personale au fost actualizate!";
            $messageType = "success";
        }
    }

    // B. Actualizare Card Bancar
    if (isset($_POST['update_card'])) {
        $card_number = $_POST['card_number'];
        $card_holder = $_POST['card_holder'];
        $card_expiry = $_POST['card_expiry'];
        
        $stmt = $mysqli->prepare("UPDATE users SET card_number = ?, card_holder = ?, card_expiry = ? WHERE id = ?");
        $stmt->bind_param("sssi", $card_number, $card_holder, $card_expiry, $user_id);
        if ($stmt->execute()) {
            $message = "Cardul a fost salvat!";
            $messageType = "success";
        }
    }

    // C. SCHIMBARE PAROLĂ
    if (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $mysqli->prepare("SELECT parola FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $currentHash = $res['parola'];

        if (password_verify($old_pass, $currentHash)) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $newHash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare("UPDATE users SET parola = ? WHERE id = ?");
                    $stmt->bind_param("si", $newHash, $user_id);
                    if ($stmt->execute()) {
                        $message = "Parola a fost schimbată cu succes!";
                        $messageType = "success";
                    }
                } else {
                    $message = "Noua parolă trebuie să aibă minim 6 caractere.";
                    $messageType = "error";
                }
            } else {
                $message = "Cele două parole noi nu coincid.";
                $messageType = "error";
            }
        } else {
            $message = "Parola actuală este incorectă.";
            $messageType = "error";
        }
    }
}

// 4. Extragem datele actualizate
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// 5. NOU: Extragem istoricul comenzilor ȘI verificăm dacă există o cerere de retur pentru fiecare comandă
$sqlOrders = "
    SELECT o.id, o.total, o.status, o.created_at, 
           (SELECT status FROM cereri_retur WHERE order_id = o.id LIMIT 1) as retur_status 
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
";
$stmtOrders = $mysqli->prepare($sqlOrders);
$stmtOrders->bind_param("i", $user_id);
$stmtOrders->execute();
$resultOrders = $stmtOrders->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Profilul Meu - PC Shop</title>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .profile-container { max-width: 1100px; margin: 0 auto; display: grid; padding: 40px; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .full-width { grid-column: 1 / -1; } 
        
        .header-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; padding: 40px; }
        h1 { margin: 0; color: #fff; }
        .btn-logout { background-color: #c0392b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-home { color: #aaa; text-decoration: none; margin-right: 20px; }

        .card-box { background-color: #1e1e1e; padding: 25px; border-radius: 12px; border: 1px solid #333; display: flex; flex-direction: column;}
        h3 { color: #9b59b6; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; }
        
        label { display: block; margin: 10px 0 5px; color: #aaa; font-size: 14px; }
        input, textarea { width: 100%; padding: 10px; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 6px; box-sizing: border-box; }
        input:focus, textarea:focus { border-color: #9b59b6; outline: none; }

        .btn-save { width: 100%; margin-top: auto; padding: 10px; background: #9b59b6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-save:hover { background: #8e44ad; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background: #27ae60; color: white; }
        .error { background: #c0392b; color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #aaa; border-bottom: 1px solid #444; padding: 10px; }
        td { padding: 12px 10px; border-bottom: 1px solid #333; vertical-align: middle; }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; display: inline-block; font-weight: bold; }
        .status-pending { background: #f39c12; color: #000; }
        
        .btn-retur { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.3s; display: inline-block; border: 1px solid transparent;}
        .btn-retur:hover { background: #c0392b; border-color: #fff; transform: translateY(-2px); }
        .status-retur-text { color: #e74c3c; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px;}
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="header-row" style="max-width: 1100px; margin: 0 auto 30px auto;">
    <div>
        <a href="home.php" class="btn-home">&larr; Înapoi</a>
        <h1>Contul Meu</h1>
    </div>
    <a href="logout.php" class="btn-logout">Deconectare</a>
</div>

<?php if($message): ?>
    <div style="max-width: 1100px; margin: 0 auto;">
        <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
    </div>
<?php endif; ?>

<div class="profile-container">
    <div class="card-box">
        <h3>Date Livrare</h3>
        <form method="POST">
            <input type="hidden" name="update_info" value="1">
            <label>Nume</label>
            <input type="text" value="<?php echo htmlspecialchars($userData['nume']); ?>" disabled style="opacity: 0.5;">
            <label>Telefon</label>
            <input type="text" name="telefon" value="<?php echo htmlspecialchars($userData['telefon'] ?? ''); ?>">
            <label>Adresă</label>
            <textarea name="adresa" rows="3"><?php echo htmlspecialchars($userData['adresa'] ?? ''); ?></textarea><br><br>
            <button type="submit" class="btn-save">Salvează Info</button>
        </form>
    </div>

    <div class="card-box">
        <h3>Metoda de Plată</h3>
        <form method="POST">
            <input type="hidden" name="update_card" value="1">
            <label>Număr Card</label>
            <input type="text" name="card_number" value="<?php echo htmlspecialchars($userData['card_number'] ?? ''); ?>" placeholder="**** **** **** ****">
            <div style="display: flex; gap: 10px;">
                <div style="flex:1;"><label>Titular</label><input type="text" name="card_holder" value="<?php echo htmlspecialchars($userData['card_holder'] ?? ''); ?>"></div>
                <div style="flex:1;"><label>Exp.</label><input type="text" name="card_expiry" value="<?php echo htmlspecialchars($userData['card_expiry'] ?? ''); ?>" placeholder="MM/YY"></div>
            </div><br>
            <button type="submit" class="btn-save">Salvează Card</button>
        </form>
    </div>

    <div class="card-box">
        <h3>Securitate</h3>
        <form method="POST">
            <input type="hidden" name="update_password" value="1">
            <label>Parola Actuală</label><input type="password" name="old_password" required placeholder="Parola veche...">
            <label>Parola Nouă</label><input type="password" name="new_password" required placeholder="Parola nouă...">
            <label>Confirmă Parola Nouă</label><input type="password" name="confirm_password" required placeholder="Confirmă parola...">
            <br><br>
            <button type="submit" class="btn-save">Schimbă Parola</button>
        </form>
    </div>

    <div class="card-box full-width">
        <h3>Istoric Comenzi</h3>
        <?php if($resultOrders->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th> 
                        <th>Dată</th> 
                        <th>Total</th> 
                        <th>Status Comandă</th>
                        <th>Acțiuni / Status Retur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $resultOrders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                        <td style="color:#9b59b6; font-weight:bold;"><?php echo number_format($order['total'], 2, ',', '.'); ?> RON</td>
                        <td><span class="status-badge status-pending"><?php echo htmlspecialchars($order['status']); ?></span></td>
                        
                        <td>
                            <?php if ($order['retur_status']): ?>
                                <span class="status-retur-text">🔄 Retur: <?php echo htmlspecialchars($order['retur_status']); ?></span>
                            <?php else: ?>
                                <a href="formular_retur.php?order_id=<?php echo $order['id']; ?>" class="btn-retur">↪️ Cere Retur</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#888;">Nu ai comenzi înregistrate momentan.</p>
        <?php endif; ?>
    </div>
</div>

<?php 
    require 'cart_modal.php'; 
    require 'footer.php'; 
?>

</body>
</html>