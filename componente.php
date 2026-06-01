<?php
require 'config.php';
$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';

// --- LOGICA DE FILTRARE ȘI SORTARE ---
$categorii_result = $mysqli->query("SELECT * FROM categorii");

$sql = "SELECT p.*, c.nume as nume_categorie FROM produse p LEFT JOIN categorii c ON p.categorie_id = c.id WHERE 1=1";

// Filtru Categorie
$cat_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
if ($cat_id > 0) { $sql .= " AND p.categorie_id = $cat_id"; }

// Filtru Stoc
$in_stoc = isset($_GET['in_stoc']) ? true : false;
if ($in_stoc) { $sql .= " AND p.stoc > 0"; }

// Filtru REDUCERI 
$la_reducere = isset($_GET['la_reducere']) ? true : false;
if ($la_reducere) { $sql .= " AND p.pret_vechi > p.pret"; }

// Sortare
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nou';
if ($sort === 'pret_cresc') { $sql .= " ORDER BY p.pret ASC"; } 
elseif ($sort === 'pret_desc') { $sql .= " ORDER BY p.pret DESC"; } 
else { $sql .= " ORDER BY p.id DESC"; }

$result = $mysqli->query($sql);

// Extragem ID-urile produselor favorite pentru userul curent
$fav_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $q_fav = $mysqli->query("SELECT product_id FROM wishlist WHERE user_id = $uid");
    while($row = $q_fav->fetch_assoc()) { 
        $fav_ids[] = $row['product_id']; 
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog Componente - PC Shop</title>
    <style>
        /* STILURI GLOBALE ȘI NAVBAR */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; overflow-x: hidden; }
        header { background-color: #1f1f1f; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 26px; font-weight: bold; color: #fff; letter-spacing: 1px; text-decoration: none;} .logo span { color: #9b59b6; }
        nav { display: flex; align-items: center; }
        nav a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: color 0.3s; } nav a:hover { color: #9b59b6; }
        .cart-trigger { position: relative; cursor: pointer; margin-left: 30px; display: flex; align-items: center; color: #bbb; transition: color 0.3s; } .cart-trigger:hover { color: #9b59b6; }
        .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 5px; position: absolute; top: -5px; right: -8px; font-weight: bold; }

        /* CATALOG */
        .hero { text-align: center; padding: 50px 20px; background: linear-gradient(180deg, #1f1f1f 0%, #121212 100%); }
        .hero h1 { font-size: 2.5em; margin: 0; color: white; }
        .hero p { color: #888; font-size: 1.2em; margin-top: 10px; }
        .catalog-layout { display: grid; grid-template-columns: 280px 1fr; gap: 30px; max-width: 1400px; margin: 0 auto 60px; padding: 0 40px; }

        /* BARA DIN STÂNGA */
        .sidebar { background-color: #1e1e1e; padding: 25px; border-radius: 12px; border: 1px solid #333; height: fit-content; position: sticky; top: 100px; }
        .sidebar h3 { color: #9b59b6; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; font-size: 18px; }
        .filter-group { margin-bottom: 25px; }
        .filter-label { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer; color: #ccc; font-size: 15px; transition: 0.2s; }
        .filter-label:hover { color: #fff; }
        .filter-label input[type="radio"], .filter-label input[type="checkbox"] { cursor: pointer; transform: scale(1.2); accent-color: #9b59b6; }
        .btn-filter { background-color: #8e44ad; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; font-size: 15px; transition: 0.2s; }
        .btn-filter:hover { background: #9b59b6; }

        /* ZONA DREAPTA (PRODUSE) */
        .catalog-content { display: flex; flex-direction: column; }
        .catalog-header-bar { display: flex; justify-content: space-between; align-items: center; background: #1e1e1e; padding: 15px 20px; border-radius: 12px; border: 1px solid #333; margin-bottom: 25px; }
        .results-count { color: #aaa; font-weight: bold; }
        .sort-select { background: #252525; color: #fff; border: 1px solid #444; padding: 8px 12px; border-radius: 6px; outline: none; cursor: pointer; }
        .sort-select:focus { border-color: #9b59b6; }

        /* GRILĂ PRODUSE */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .product-card { background-color: #1e1e1e; border-radius: 12px; overflow: hidden; border: 1px solid #333; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; position: relative; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(155, 89, 182, 0.2); border-color: #8e44ad; }
        
        .badge-container { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 5px; z-index: 10; pointer-events: none; }
        .badge { padding: 5px 10px; font-size: 12px; font-weight: bold; border-radius: 4px; width: fit-content; }
        .stock-out { background-color: #c0392b; color: white; } .stock-low { background-color: #f39c12; color: #111; } .stock-ok { background-color: #27ae60; color: white; }
        .badge-sale { background-color: #e74c3c; color: white; box-shadow: 0 2px 10px rgba(231, 76, 60, 0.4); font-size: 11px; }

        /* STIL NOU PENTRU WISHLIST LINK */
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

        @media (max-width: 900px) { .catalog-layout { grid-template-columns: 1fr; } .sidebar { position: relative; top: 0; } }

        /* CHAT UI */
        .chat-toggle-btn { position: fixed; bottom: 30px; right: 30px; background-color: #8e44ad; color: white; border: none; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; box-shadow: 0 4px 15px rgba(142, 68, 173, 0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .chat-toggle-btn:hover { transform: scale(1.1); }
        .chat-container { position: fixed; bottom: 100px; right: 30px; width: 340px; height: 480px; background-color: #1e1e1e; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); display: none; flex-direction: column; overflow: hidden; z-index: 9999; font-size: 14px; border: 1px solid #333; }
        .chat-header { background-color: #8e44ad; color: white; padding: 15px 20px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; }
        .message { max-width: 80%; padding: 12px 16px; line-height: 1.4; font-size: 14px; }
        .bot-message { background-color: #ecf0f1; color: #2c3e50; border-radius: 20px 20px 20px 5px; align-self: flex-start; }
        .user-message { background-color: #9b59b6; color: white; border-radius: 20px 20px 5px 20px; align-self: flex-end; }
        .chat-footer { padding: 15px; background-color: #252525; display: flex; gap: 10px; border-top: 1px solid #333; }
        .chat-input { flex: 1; padding: 12px 15px; border-radius: 25px; border: 1px solid #8e44ad; background-color: #1e1e1e; color: #fff; outline: none; }
        .send-btn { background-color: #9b59b6; width: 45px; height: 45px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
    
    <div class="hero">
        <h1>Catalog Componente</h1>
        <p>Procesoare, Plăci Video, Memorii și multe altele.</p>
    </div>

    <form action="componente.php" method="GET" id="filterForm">
        <div class="catalog-layout">
            <aside class="sidebar">
                <div class="filter-group">
                    <h3>Categorii</h3>
                    <label class="filter-label"><input type="radio" name="categorie" value="0" <?php if($cat_id == 0) echo 'checked'; ?>> Toate categoriile</label>
                    <?php while($cat = $categorii_result->fetch_assoc()): ?>
                        <label class="filter-label"><input type="radio" name="categorie" value="<?php echo $cat['id']; ?>" <?php if($cat_id == $cat['id']) echo 'checked'; ?>> <?php echo htmlspecialchars($cat['nume']); ?></label>
                    <?php endwhile; ?>
                </div>
                
                <div class="filter-group">
                    <h3>Disponibilitate</h3>
                    <label class="filter-label">
                        <input type="checkbox" name="in_stoc" value="1" <?php if($in_stoc) echo 'checked'; ?>> 
                        Doar produse în stoc
                    </label>
                    
                    <label class="filter-label" style="color: #e74c3c; font-weight: bold; margin-top: 10px;">
                        <input type="checkbox" name="la_reducere" value="1" <?php if($la_reducere) echo 'checked'; ?>> 
                        % Produse la reducere
                    </label>
                </div>
                
                <button type="submit" class="btn-filter">Aplică Filtrele</button>
            </aside>

            <div class="catalog-content">
                <div class="catalog-header-bar">
                    <div class="results-count">Afișare <?php echo $result->num_rows; ?> produse</div>
                    <div>
                        <label style="color:#aaa; margin-right:10px;">Sortează după:</label>
                        <select name="sort" class="sort-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="nou" <?php if($sort == 'nou') echo 'selected'; ?>>Cele mai noi</option>
                            <option value="pret_cresc" <?php if($sort == 'pret_cresc') echo 'selected'; ?>>Preț: Crescător</option>
                            <option value="pret_desc" <?php if($sort == 'pret_desc') echo 'selected'; ?>>Preț: Descrescător</option>
                        </select>
                    </div>
                </div>

                <div class="products-grid">
                    <?php 
                    if ($result->num_rows > 0): 
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
                            
                            // Verificăm dacă e favorit
                            $este_favorit = in_array($produs['id'], $fav_ids);
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
                    <?php endwhile; else: ?>
                        <h3 style="color:#aaa; grid-column: 1 / -1; text-align: center; padding: 50px;">Nu am găsit produse conform filtrelor selectate.</h3>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <button class="chat-toggle-btn" onclick="toggleChat()">
        <svg viewBox="0 0 24 24" width="30" height="30" fill="white"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
    </button>
    <div class="chat-container" id="chatBox">
        <div class="chat-header"><span>Asistent PC Shop</span><span class="close-chat" onclick="toggleChat()" style="cursor:pointer;">✖</span></div>
        <div class="chat-body" id="chatBody"><div class="message bot-message">Salut! Întreabă-mă despre componente.</div></div>
        <div class="chat-footer">
            <input type="text" class="chat-input" id="userInput" placeholder="Scrie un mesaj..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button class="send-btn" onclick="sendMessage()"><svg viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>
        </div>
    </div>

    <script>
        let chatHistory=[]; 
        function toggleChat(){ 
            const c=document.getElementById('chatBox'); 
            c.style.display=(c.style.display==='none'||c.style.display==='')?'flex':'none'; 
            if(c.style.display==='flex') setTimeout(()=>document.getElementById('userInput').focus(),100); 
        }
        
        async function sendMessage(){ 
            const i=document.getElementById('userInput'); 
            const txt=i.value.trim(); 
            const b=document.getElementById('chatBody'); 
            if(!txt)return; 
            
            b.innerHTML+=`<div class="message user-message">${txt}</div>`; 
            chatHistory.push({role:"user",content:txt}); 
            i.value=""; 
            b.scrollTop=b.scrollHeight; 
            
            b.innerHTML+=`<div class="message bot-message" id="loading-indicator">...</div>`; 
            b.scrollTop=b.scrollHeight; 
            
            try{ 
                const r=await fetch('chat_api.php',{
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({history:chatHistory})
                }); 
                const d=await r.json(); 
                document.getElementById('loading-indicator').remove(); 
                b.innerHTML+=`<div class="message bot-message">${d.reply||"Eroare."}</div>`; 
                chatHistory.push({role:"assistant",content:d.reply}); 
            }catch(e){ 
                document.getElementById('loading-indicator').remove(); 
                b.innerHTML+=`<div class="message bot-message">Eroare conexiune.</div>`; 
            } 
            b.scrollTop=b.scrollHeight; 
        }


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

    <?php require 'cart_modal.php'; ?>
    <?php require 'footer.php'; ?>

</body>
</html>