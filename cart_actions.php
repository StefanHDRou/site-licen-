<?php
require 'config.php';
header('Content-Type: application/json');


if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
// Sesiune pentru codul promoțional
if (!isset($_SESSION['promo_code'])) { $_SESSION['promo_code'] = ''; }

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id_produs = $input['id'] ?? 0;
$promo = $input['code'] ?? '';

// 1. ADAUGĂ / CREȘTE
if ($action === 'add' && $id_produs > 0) {
    $stmt = $mysqli->prepare("SELECT stoc FROM produse WHERE id = ?");
    $stmt->bind_param("i", $id_produs);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $stoc_db = (int)$res->fetch_assoc()['stoc'];
        $qty_in_cart = isset($_SESSION['cart'][$id_produs]) ? $_SESSION['cart'][$id_produs] : 0;

        if ($qty_in_cart < $stoc_db) {
            $_SESSION['cart'][$id_produs] = $qty_in_cart + 1;
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Stoc insuficient!"]);
        }
    }
    exit;
}

// 2. SCADE CANTITATE
if ($action === 'decrease' && $id_produs > 0) {
    if (isset($_SESSION['cart'][$id_produs])) {
        if ($_SESSION['cart'][$id_produs] > 1) {
            $_SESSION['cart'][$id_produs]--;
        } else {
            unset($_SESSION['cart'][$id_produs]);
        }
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// 3. ȘTERGE PRODUSUL
if ($action === 'remove' && $id_produs > 0) {
    if (isset($_SESSION['cart'][$id_produs])) {
        unset($_SESSION['cart'][$id_produs]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// 4. APLICĂ COD PROMO
if ($action === 'apply_promo') {
    $promo_upper = strtoupper(trim($promo));
    // Aici poți defini mai multe coduri. Pentru noi, PCSHOP10 este "cheia"
    if ($promo_upper === 'PCSHOP10') {
        $_SESSION['promo_code'] = $promo_upper;
        echo json_encode(['status' => 'success', 'message' => 'Cod aplicat! Reducere 10% activată.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cod invalid sau expirat.']);
    }
    exit;
}

// 5. ȘTERGE COD PROMO
if ($action === 'remove_promo') {
    $_SESSION['promo_code'] = '';
    echo json_encode(['status' => 'success']);
    exit;
}

// 6. AFIȘEAZĂ COȘUL (GET)
if ($action === 'get') {
    if (empty($_SESSION['cart'])) {
        echo json_encode(['products' => [], 'total' => 0, 'discount' => 0, 'final_total' => 0, 'count' => 0, 'promo_code' => '']);
        exit;
    }

    $ids = implode(',', array_keys($_SESSION['cart']));
    $sql = "SELECT id, nume, pret, imagine_url FROM produse WHERE id IN ($ids)";
    $result = $mysqli->query($sql);

    $cartData = [];
    $totalGeneral = 0;
    $totalCount = 0;

    while ($row = $result->fetch_assoc()) {
        $qty = $_SESSION['cart'][$row['id']];
        $subtotal = $row['pret'] * $qty;
        
        $imgName = $row['imagine_url'];
        $row['imagine_url'] = (!empty($imgName)) ? ((strpos($imgName, 'images') === 0) ? $imgName : "images/" . $imgName) : "https://placehold.co/100?text=Img";

        $row['qty'] = $qty;
        $row['subtotal'] = number_format($subtotal, 0, ',', '.');
        $row['pret_format'] = number_format($row['pret'], 0, ',', '.');
        
        $cartData[] = $row;
        $totalGeneral += $subtotal;
        $totalCount += $qty;
    }

    // Logica Reducerii (10% din total)
    $discountAmount = 0;
    $finalTotal = $totalGeneral;
    
    if ($_SESSION['promo_code'] === 'PCSHOP10') {
        $discountAmount = $totalGeneral * 0.10; // 10%
        $finalTotal = $totalGeneral - $discountAmount;
    }

    echo json_encode([
        'products' => $cartData,
        'total' => number_format($totalGeneral, 0, ',', '.'), // Fara reducere
        'discount' => number_format($discountAmount, 0, ',', '.'), // Valoarea reducerii
        'final_total' => number_format($finalTotal, 0, ',', '.'), // De platit
        'count' => $totalCount,
        'promo_code' => $_SESSION['promo_code']
    ]);
    exit;
}
?>