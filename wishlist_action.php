<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$status = '';

if ($product_id > 0) {
    // Verificăm și adăugăm/ștergem din baza de date
    $check = $mysqli->query("SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");

    if ($check->num_rows > 0) {
        $mysqli->query("DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
        $status = 'removed';
    } else {
        $mysqli->query("INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)");
        $status = 'added';
    }
}

// === NOU: GENERĂM NOUL HTML PENTRU DROPDOWN-UL DIN HEADER ===
$html = '<div class="wl-menu-title">Favorite adăugate recent</div>';

$wl_query = $mysqli->query("
    SELECT p.id, p.nume, p.pret, p.imagine_url 
    FROM wishlist w 
    JOIN produse p ON w.product_id = p.id 
    WHERE w.user_id = $user_id 
    ORDER BY w.id DESC 
    LIMIT 7
");

if ($wl_query && $wl_query->num_rows > 0) {
    while($item = $wl_query->fetch_assoc()) {
        $imgSrc = (!empty($item['imagine_url'])) ? ((strpos($item['imagine_url'], 'images') === 0) ? $item['imagine_url'] : "images/" . $item['imagine_url']) : "https://placehold.co/100?text=Fara+Poza";
        $pret = number_format($item['pret'], 2, ',', '.');
        $nume = htmlspecialchars($item['nume']);
        
        $html .= '
        <a href="produs.php?id=' . $item['id'] . '" class="wl-mini-item">
            <img src="' . $imgSrc . '" alt="Poza">
            <div class="wl-mini-info">
                <p class="wl-mini-name">' . $nume . '</p>
                <span class="wl-mini-price">' . $pret . ' RON</span>
            </div>
        </a>';
    }
    $html .= '<a href="wishlist.php" class="wl-btn-more">Vezi mai multe ❤️</a>';
} else {
    $html .= '<p style="text-align: center; color: #888; font-size: 13px; padding: 20px 0;">Nu ai produse salvate. 🤍</p>';
}

// Returnăm și statusul, și noul design al meniului
echo json_encode(['status' => $status, 'html' => $html]);
?>