<?php
require 'config.php';
// 1. SECURITATE: Doar Adminii au voie aici!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}

// 2. LOGICĂ SCHIMBARE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
}

// 3. EXTRAGEM COMENZILE (Cele mai noi primele)
$query = "
    SELECT o.*, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
";
$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Admin - Comenzi</title>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        h1 { color: #e74c3c; border-bottom: 2px solid #333; padding-bottom: 10px; }
        
        .nav-admin { margin-bottom: 20px; }
        .nav-admin a { color: #fff; text-decoration: none; margin-right: 20px; font-weight: bold; font-size: 18px; }
        .nav-admin a.active { color: #e74c3c; border-bottom: 2px solid #e74c3c; }
        .nav-admin a:hover { color: #c0392b; }

        table { width: 100%; border-collapse: collapse; background: #1e1e1e; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #252525; color: #aaa; }
        tr:hover { background-color: #2a2a2a; }

        .status-select { padding: 5px; border-radius: 4px; border: none; font-weight: bold; cursor: pointer; }
        .pending { background: #f39c12; color: black; }
        .confirmed { background: #27ae60; color: white; }
        .shipped { background: #2980b9; color: white; }
        
        .btn-update { background: #333; color: white; border: 1px solid #555; padding: 5px 10px; cursor: pointer; margin-left: 5px; }
        .btn-update:hover { background: #555; }
    </style>
</head>
<body>

    <div class="nav-admin">
        <a href="home.php">&larr; Site</a>
        <a href="admin_orders.php" class="active">Comenzi</a>
        <a href="admin_products.php">Adaugă Produse</a>
        <a href="admin_edit_products.php">Editează Produse</a>
    </div>

    <h1>Gestionare Comenzi</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Dată</th>
                <th>Detalii Livrare</th>
                <th>Total</th>
                <th>Status</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php while($order = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td>
                    <b><?php echo htmlspecialchars($order['nume_livrare']); ?></b><br>
                    <small><?php echo htmlspecialchars($order['email']); ?></small><br>
                    <small><?php echo htmlspecialchars($order['telefon_livrare']); ?></small>
                </td>
                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                <td style="font-size: 13px; color: #aaa; max-width: 200px;">
                    <?php echo htmlspecialchars($order['adresa_livrare']); ?><br>
                    Plată: <b><?php echo strtoupper($order['metoda_plata']); ?></b>
                </td>
                <td style="color: #27ae60; font-weight: bold;"><?php echo $order['total']; ?> RON</td>
                
                <td colspan="2">
                    <form method="POST" style="display:flex; align-items:center;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <select name="status" class="status-select <?php echo $order['status']; ?>">
                            <option value="pending" <?php if($order['status']=='pending') echo 'selected'; ?>>Pending</option>
                            <option value="confirmed" <?php if($order['status']=='confirmed') echo 'selected'; ?>>Confirmed</option>
                            <option value="shipped" <?php if($order['status']=='shipped') echo 'selected'; ?>>Shipped</option>
                        </select>

                        <button type="submit" name="update_status" class="btn-update">Salvează</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>