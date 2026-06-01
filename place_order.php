<?php
require 'config.php';
// 1. INCLUDEM SISTEMUL DE EMAIL
require 'send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    
    $user_id = (int)$_SESSION['user_id'];
    
    // 2. PRELUĂM DATELE DE CONTACT DIN FORMULAR (inclusiv noul câmp de email)
    $nume = $mysqli->real_escape_string($_POST['nume']);
    $telefon = isset($_POST['telefon']) ? $mysqli->real_escape_string($_POST['telefon']) : '';
    $email_client = $mysqli->real_escape_string($_POST['email']);
    
    // 3. PRELUĂM LOGICA DE LIVRARE ȘI PLATĂ
    $metoda_livrare = $mysqli->real_escape_string($_POST['livrare']);
    $metoda_plata = $mysqli->real_escape_string($_POST['plata']);

    // Construim adresa finală pe care o vom salva în baza de date
    if ($metoda_livrare === 'easybox') {
        $adresa_finala = "Sameday Easybox: " . $mysqli->real_escape_string($_POST['adresa_easybox']);
    } elseif ($metoda_livrare === 'magazin') {
        $adresa_finala = "Ridicare personală din magazin.";
    } else {
        $adresa_finala = "Domiciliu: " . $mysqli->real_escape_string($_POST['adresa']);
    }

    // Preluăm taxele calculate de checkout.php
    $total_plata = (float)$_POST['total_plata'];
    $cost_livrare = (float)$_POST['cost_livrare'];
    $taxa_ramburs = (float)$_POST['taxa_ramburs'];

    // 4. CALCULĂM PRODUSELE PENTRU EMAIL ȘI BAZA DE DATE
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $mysqli->query("SELECT id, nume, pret FROM produse WHERE id IN ($ids)");
    
    $subtotal_produse = 0;
    $produse_db = []; 
    
    // -- PREGĂTIM HTML-UL PENTRU EMAIL (Începutul) --
    $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
        <h2 style='color:#8e44ad;'>Salut $nume,</h2>
        <p>Îți mulțumim pentru comandă! Iată detaliile de plată și livrare:</p>
        
        <table style='width:100%; border-collapse: collapse; border: 1px solid #ddd;'>
            <tr style='background-color: #2c3e50; color: white;'>
                <th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Produs</th>
                <th style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Cantitate</th>
                <th style='padding: 10px; border: 1px solid #ddd; text-align: right;'>Preț</th>
            </tr>
    ";

    while($row = $result->fetch_assoc()) {
        $qty = $_SESSION['cart'][$row['id']];
        $subtotal = $row['pret'] * $qty;
        $subtotal_produse += $subtotal;
        
        // Salvăm datele complete pt inserare
        $produse_db[$row['id']] = [
            'pret' => $row['pret'],
            'nume' => $row['nume']
        ];

        // -- ADĂUGĂM RÂND ÎN EMAIL --
        $emailBody .= "
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'>{$row['nume']}</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>{$qty}</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: right;'>" . number_format($subtotal, 2, ',', '.') . " RON</td>
            </tr>
        ";
    }

    // Calculăm reducerea dacă există
    $discount = 0;
    if (isset($_SESSION['promo_code']) && $_SESSION['promo_code'] === 'PCSHOP10') {
        $discount = $subtotal_produse * 0.10;
    }

    // -- FINALIZĂM BONUL FISCAL ÎN EMAIL --
    $emailBody .= "
            <tr style='background-color: #f9f9f9;'>
                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>Subtotal Produse:</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>" . number_format($subtotal_produse, 2, ',', '.') . " RON</td>
            </tr>
    ";

    if ($discount > 0) {
        $emailBody .= "
            <tr style='background-color: #f9f9f9;'>
                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right; color: #e74c3c; font-weight:bold;'>Reducere aplicată:</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: right; color: #e74c3c; font-weight:bold;'>- " . number_format($discount, 2, ',', '.') . " RON</td>
            </tr>
        ";
    }

    $textLivrare = ($cost_livrare == 0) ? "<span style='color:#27ae60; font-weight:bold;'>Gratuit</span>" : "+ " . number_format($cost_livrare, 2, ',', '.') . " RON";
    $emailBody .= "
            <tr style='background-color: #f9f9f9;'>
                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>Cost Livrare (" . ucfirst($metoda_livrare) . "):</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>$textLivrare</td>
            </tr>
    ";

    if ($taxa_ramburs > 0) {
        $emailBody .= "
            <tr style='background-color: #f9f9f9;'>
                <td colspan='2' style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>Taxă procesare plată:</td>
                <td style='padding: 10px; border: 1px solid #ddd; text-align: right; color:#888;'>+ " . number_format($taxa_ramburs, 2, ',', '.') . " RON</td>
            </tr>
        ";
    }

    $emailBody .= "
            <tr style='background-color: #ecf0f1; font-size: 18px; font-weight: bold;'>
                <td colspan='2' style='padding: 15px 10px; border: 1px solid #ddd; text-align: right;'>TOTAL DE PLATĂ:</td>
                <td style='padding: 15px 10px; border: 1px solid #ddd; text-align: right; color: #8e44ad;'>" . number_format($total_plata, 2, ',', '.') . " RON</td>
            </tr>
        </table>
        
        <div style='background: #fdfdfd; border: 1px solid #eee; padding: 15px; margin-top: 20px; border-radius: 6px;'>
            <p style='margin:0 0 10px 0;'><b>📍 Adresă de livrare:</b> <br> $adresa_finala</p>
            <p style='margin:0 0 10px 0;'><b>💳 Metodă de plată:</b> <br> " . strtoupper($metoda_plata) . "</p>
            <p style='margin:0;'><b>📞 Telefon contact:</b> <br> " . ($telefon ? $telefon : "Nefurnizat") . "</p>
        </div>
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
        <p style='text-align:center; color:#888; font-size: 12px;'>Acesta este un email automat. Te rugăm să nu răspunzi la acest mesaj.<br>Echipa PC Shop</p>
        </div>
    ";

    // 5. INSERĂM COMANDA ÎN TABELUL orders
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, total, nume_livrare, telefon_livrare, adresa_livrare, metoda_plata) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssss", $user_id, $total_plata, $nume, $telefon, $adresa_finala, $metoda_plata);
    $stmt->execute();
    $order_id = $stmt->insert_id; 
    $stmt->close();

    // 6. INSERĂM PRODUSELE ÎN order_items + SCĂDEM STOCUL
    $stmt_item = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, cantitate, pret_unitar) VALUES (?, ?, ?, ?)");
    $stmt_stoc = $mysqli->prepare("UPDATE produse SET stoc = stoc - ? WHERE id = ?");
    
    foreach ($_SESSION['cart'] as $pid => $qty) {
        // Inserăm produsul în comandă
        $pret = $produse_db[$pid]['pret'];
        $stmt_item->bind_param("iiid", $order_id, $pid, $qty, $pret);
        $stmt_item->execute();
        
        // Scădem stocul produsului cumpărat
        $stmt_stoc->bind_param("ii", $qty, $pid);
        $stmt_stoc->execute();
    }
    
    $stmt_item->close();
    $stmt_stoc->close();

    // 7. TRIMITEM EMAILUL EFECTIV
    trimiteEmail($email_client, $nume, "Confirmare Comanda #$order_id - PC Shop", $emailBody);

    // 8. GOLIM COȘUL, VOUCHER-UL ȘI AFIȘĂM SUCCES
    unset($_SESSION['cart']);
    unset($_SESSION['promo_code']);
    
    echo '<!DOCTYPE html><html lang="ro"><head><meta charset="UTF-8"><style>body{background:#121212;color:white;font-family:sans-serif;text-align:center;padding:50px;} h1{color:#27ae60;} .loader {border: 4px solid #333; border-top: 4px solid #27ae60; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto;} @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style></head><body>';
    echo "<h1>🎉 Comandă Plasată cu Succes!</h1>";
    echo "<p>Mulțumim, <b>$nume</b>! Comanda ta cu numărul <b>#$order_id</b> a fost înregistrată.</p>";
    echo "<p style='color:#aaa'>Bonul fiscal și detaliile au fost trimise la: <b>$email_client</b>.</p>";
    echo "<div class='loader'></div>";
    echo "<p>Vei fi redirecționat către magazin în 5 secunde...</p>";
    echo "<script>setTimeout(function(){ window.location.href = 'home.php'; }, 5000);</script>";
    echo '</body></html>';
    
} else {
    header('Location: home.php');
}
?>