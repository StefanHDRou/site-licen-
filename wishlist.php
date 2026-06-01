<?php
require 'config.php';

// Dacă nu e logat, nu are ce căuta aici
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';


$user_id = (int)$_SESSION['user_id'];

// Extragem produsele din wishlist + categoria lor (pentru designul cardului)
$sql = "SELECT p.*, c.nume as nume_categorie 
        FROM produse p 
        INNER JOIN wishlist w ON p.id = w.product_id 
        LEFT JOIN categorii c ON p.categorie_id = c.id 
        WHERE w.user_id = $user_id 
        ORDER BY w.id DESC"; // Le afișăm în ordinea în care au fost adăugate (ultimele primele)
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoritele Mele - PC Shop</title>
    <style>
        /* STILURI GLOBALE ȘI NAVBAR (identice) */
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; }
        header { background-color: #1f1f1f; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 26px; font-weight: bold; color: #fff; letter-spacing: 1px; text-decoration: none;} .logo span { color: #9b59b6; }
        nav { display: flex; align-items: center; }
        nav a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: color 0.3s; } nav a:hover { color: #9b59b6; }
        .cart-trigger { position: relative; cursor: pointer; margin-left: 30px; display: flex; align-items: center; color: #bbb; transition: color 0.3s; } .cart-trigger:hover { color: #9b59b6; }
        .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 5px; position: absolute; top: -5px; right: -8px; font-weight: bold; }

        /* HEADER WISHLIST */
        .hero { text-align: center; padding: 40px 20px; background: linear-gradient(180deg, #1f1f1f 0%, #121212 100%); }
        .hero h1 { font-size: 2.5em; margin: 0; color: #9b59b6; }
        .hero p { color: #888; font-size: 1.2em; margin-top: 10px; }

        .container { max-width: 1400px; margin: 0 auto 60px; padding: 0 40px; }
        .no-products { text-align: center; color: #aaa; margin-top: 50px; font-size: 18px; padding: 50px; border: 1px dashed #444; border-radius: 12px; }

        /* GRILĂ PRODUSE (exact ca la componente) */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .product-card { background-color: #1e1e1e; border-radius: 12px; overflow: hidden; border: 1px solid #333; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; position: relative; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(155, 89, 182, 0.2); border-color: #8e44ad; }
        
        .badge-container { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 5px; z-index: 10; pointer-events: none; }
        .badge { padding: 5px 10px; font-size: 12px; font-weight: bold; border-radius: 4px; width: fit-content; }
        .stock-out { background-color: #c0392b; color: white; } .stock-low { background-color: #f39c12; color: #111; } .stock-ok { background-color: #27ae60; color: white; }
        .badge-sale { background-color: #e74c3c; color: white; box-shadow: 0 2px 10px rgba(231, 76, 60, 0.4); font-size: 11px; }

        /* STIL NOU PENTRU WISHLIST LINK (CU SVG) */
        .wishlist-link { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); width: 36px; height: 36px; border-radius: 50%; text-decoration: none; z-index: 15; transition: 0.3s; border: 1px solid #444; display: flex; align-items: center; justify-content: center; }
        .wishlist-link:hover { background: rgba(0,0,0,0.9); transform: scale(1.1); border-color: #e74c3c; }
        .wishlist-link svg {transition: 0.3s;}

        .product-img { width: 100%; height: 220px; object-fit: contain; background-color: #fff; border-bottom: 1px solid #333; }
        .product-info { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .category-tag { font-size: 12px; text-transform: uppercase; color: #8e44ad; font-weight: bold; margin-bottom: 5px; }
        .product-title { font-size: 18px; font-weight: 600; margin: 0 0 10px 0; color: #fff; height: 50px; overflow: hidden; text-overflow: ellipsis; }
        .specs-list { font-size: 13px; color: #aaa; margin-bottom: 15px; flex: 1; padding-left: 20px; } .specs-list li { margin-bottom: 4px; } .specs-list strong { color: #ccc; }
        
        .price-box { margin-top: auto; border-top: 1px solid #333; padding-top: 15px; display: flex; justify-content: space-between; align-items: flex-end; }
        .price-old { color: #888; text-decoration: line-through; font-size: 14px; margin-bottom: 2px; }
        .price-new { color: #e74c3c; font-weight: bold; font-size: 22px; }
        .price-normal { color: #9b59b6; font-weight: bold; font-size: 22px; }
        
        .btn-buy { background-color: #9b59b6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
        .btn-buy:hover { background-color: #8e44ad; } .btn-buy:disabled { background-color: #444; cursor: not-allowed; color: #888; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="hero">
    <h1>❤️ Produse Favorite</h1>
    <p>Aici sunt salvate componentele tale preferate.</p>
</div>

<div class="container">
    <?php if ($result->num_rows == 0): ?>
        <div class="no-products">
            <p>Nu ai salvat niciun produs momentan.</p>
            <a href="componente.php" style="color: #9b59b6; text-decoration: none; font-weight: bold;">Către catalog ⟶</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php 
            while($produs = $result->fetch_assoc()): 
                // Logica Stoc
                $stocClass = 'stock-ok'; $stocText = 'În Stoc';
                if ($produs['stoc'] == 0) { $stocClass = 'stock-out'; $stocText = 'Epuizat'; } 
                elseif ($produs['stoc'] < 5) { $stocClass = 'stock-low'; $stocText = 'Stoc Limitat'; }

                // Logica Imagine
                $imgSrc = !empty($produs['imagine_url']) ? ((strpos($produs['imagine_url'], 'images') === 0) ? $produs['imagine_url'] : "images/" . $produs['imagine_url']) : "https://placehold.co/400x300/2c2c2c/a0a0a0?text=" . urlencode($produs['nume']);

                // Logica Reduceri
                $pret_afisat = $produs['pret'];
                $pret_vechi = !empty($produs['pret_vechi']) ? $produs['pret_vechi'] : 0;
                $is_on_sale = ($pret_vechi > $pret_afisat);
                $procent_reducere = 0;
                if ($is_on_sale) {
                    $procent_reducere = round((($pret_vechi - $pret_afisat) / $pret_vechi) * 100);
                }

                // Specificatii
                $id_p = $produs['id'];
                $sql_specs = "SELECT * FROM specificatii_produse WHERE produs_id = $id_p AND afisare_home = 1 LIMIT 4";
                $result_specs = $mysqli->query($sql_specs);
            ?>
            
            <div class="product-card">
                <button class="wishlist-link" onclick="toggleFav(event, this, <?php echo $produs['id']; ?>)" title="Favorite">
    <?php if (isset($este_favorit) && $este_favorit): ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#e74c3c" stroke="#e74c3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
    <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
    <?php endif; ?>
                </button>

                <div class="badge-container">
                    <?php if($is_on_sale): ?>
                        <div class="badge badge-sale">-<?php echo $procent_reducere; ?>% REDUCERE</div>
                    <?php endif; ?>
                    <div class="badge <?php echo $stocClass; ?>"><?php echo $stocText; ?></div>
                </div>

                <a href="produs.php?id=<?php echo $produs['id']; ?>" style="text-decoration: none; display: block;">
                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($produs['nume']); ?>" class="product-img">
                </a>

                <div class="product-info">
                    <div class="category-tag"><?php echo $produs['nume_categorie']; ?></div>
                    <a href="produs.php?id=<?php echo $produs['id']; ?>" style="text-decoration: none;">
                        <h3 class="product-title" style="transition: color 0.3s;" onmouseover="this.style.color='#9b59b6'" onmouseout="this.style.color='#fff'"><?php echo $produs['nume']; ?></h3>
                    </a>
                    
                    <ul class="specs-list">
                        <?php 
                        if ($result_specs->num_rows > 0) {
                            while($spec = $result_specs->fetch_assoc()) {
                                echo "<li><strong>" . htmlspecialchars($spec['nume_specificatie']) . ":</strong> " . htmlspecialchars($spec['valoare_specificatie']) . "</li>";
                            }
                        } else { echo "<li>Detalii standard disponibile.</li>"; }
                        ?>
                    </ul>

                    <div class="price-box">
                        <div>
                            <?php if($is_on_sale): ?>
                                <div class="price-old"><?php echo number_format($pret_vechi, 2, ',', '.'); ?> RON</div>
                                <div class="price-new"><?php echo number_format($pret_afisat, 2, ',', '.'); ?> RON</div>
                            <?php else: ?>
                                <div class="price-normal"><?php echo number_format($pret_afisat, 2, ',', '.'); ?> RON</div>
                            <?php endif; ?>
                        </div>
                        <?php if($produs['stoc'] > 0): ?>
                            <button type="button" class="btn-buy" onclick="addToCart(<?php echo $produs['id']; ?>)">Adaugă în coș</button>
                        <?php else: ?>
                            <button type="button" class="btn-buy" disabled>Indisponibil</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
// Includem modalul de coș pentru a face funcțional butonul de adăugare
require 'cart_modal.php'; 
require 'footer.php'; 
?>

<script>
async function toggleFav(event, btn, productId) {
    event.preventDefault(); // Oprește refresh-ul

    let formData = new FormData();
    formData.append('product_id', productId);

    try {
        let response = await fetch('wishlist_action.php', { method: 'POST', body: formData });
        let result = await response.json();

        if (result.status === 'not_logged_in') {
            window.location.href = 'login.php';
            return;
        }

        // ========================================================
        // NOU: ACTUALIZĂM DROPDOWN-UL DIN HEADER INSTANT!
        const dropdownMenu = document.querySelector('.wishlist-hover-menu');
        if (dropdownMenu && result.html) {
            dropdownMenu.innerHTML = result.html;
        }
        // ========================================================

        // Modificăm culorile inimioarei de pe card
        const svg = btn.querySelector('svg');
        const path = btn.querySelector('path');

        if (result.status === 'added') {
            svg.setAttribute('fill', '#e74c3c');
            path.setAttribute('stroke', '#e74c3c');
        } else if (result.status === 'removed') {
            svg.setAttribute('fill', 'none');
            path.setAttribute('stroke', '#ffffff');
            
            // Dacă suntem pe pagina wishlist.php, ascundem cardul
            if (window.location.pathname.includes('wishlist.php')) {
                btn.closest('.product-card').style.display = 'none';
            }
        }
    } catch (error) { console.error("Eroare la favorite", error); }
}
</script>

</body>
</html>