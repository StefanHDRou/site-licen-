<?php
require 'config.php';
if (!isset($linkProfil)) {
    $linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$pages_without_search = ['contact.php', 'checkout.php'];
$show_search = !in_array($current_page, $pages_without_search);

// --- Preluăm ultimele 7 produse din wishlist pentru Dropdown ---
$header_wishlist_items = [];
if (isset($_SESSION['user_id']) && isset($mysqli)) {
    $uid = (int)$_SESSION['user_id'];
    $wl_query = $mysqli->query("
        SELECT p.id, p.nume, p.pret, p.imagine_url 
        FROM wishlist w 
        JOIN produse p ON w.product_id = p.id 
        WHERE w.user_id = $uid 
        ORDER BY w.id DESC 
        LIMIT 7
    ");
    if ($wl_query) {
        while($row = $wl_query->fetch_assoc()) {
            $header_wishlist_items[] = $row;
        }
    }
}
?>

<style>
    header { background-color: #1f1f1f; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0; z-index: 1000; }
    .logo { font-size: 26px; font-weight: bold; color: #fff; letter-spacing: 1px; text-decoration: none; white-space: nowrap; } 
    .logo span { color: #9b59b6; }
    
    /* Grupare partea dreaptă */
    .header-right { display: flex; align-items: center; }
    
    nav { display: flex; align-items: center; }
    nav > a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: color 0.3s; white-space: nowrap; } 
    nav > a:hover { color: #9b59b6; }
    
    .header-icons { display: flex; align-items: center; margin-left: 15px; }

    .cart-trigger { position: relative; cursor: pointer; margin-left: 25px; display: flex; align-items: center; color: #bbb; transition: color 0.3s; font-size: 20px;} 
    .cart-trigger:hover { color: #9b59b6; }
    .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 6px; position: absolute; top: -8px; right: -12px; font-weight: bold; }

    /* Stiluri Search Bar */
    .search-container { position: relative; flex: 1; max-width: 450px; margin: 0 40px; }
    .search-input-wrapper { display: flex; align-items: center; background: #121212; border: 1px solid #444; border-radius: 25px; padding: 5px 15px; transition: 0.3s; }
    .search-input-wrapper:focus-within { border-color: #9b59b6; box-shadow: 0 0 10px rgba(155, 89, 182, 0.3); }
    .search-input-wrapper input { background: transparent; border: none; color: #fff; width: 100%; padding: 8px 5px; outline: none; font-size: 15px; }
    .search-icon { color: #888; font-size: 18px; }
    .search-results-dropdown { position: absolute; top: 110%; left: 0; width: 100%; background: #1e1e1e; border: 1px solid #333; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); z-index: 2000; display: none; overflow: hidden; }
    .search-result-item { display: flex; align-items: center; gap: 15px; padding: 12px 15px; border-bottom: 1px solid #333; text-decoration: none; color: #fff; transition: 0.2s; }
    .search-result-item:hover { background: #2a2a2a; }
    .search-result-item img { width: 50px; height: 50px; object-fit: contain; background: #fff; border-radius: 6px; }
    .search-result-title { font-size: 14px; font-weight: bold; margin: 0 0 5px 0; color: #e0e0e0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;}
    .search-result-price { color: #9b59b6; font-size: 14px; font-weight: bold; }

    /* STILURI DROPDOWN WISHLIST HEADER */
    .header-wishlist-trigger { position: relative; cursor: pointer; margin-left: 20px; padding: 10px 0; font-size: 20px; text-decoration: none; display: flex; align-items: center; color: #bbb; transition: 0.3s;}
    .header-wishlist-trigger:hover { color: #e74c3c; }
    .wishlist-hover-menu { position: absolute; top: 120%; right: -50px; width: 320px; background: #1e1e1e; border: 1px solid #333; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.7); z-index: 2500; opacity: 0; visibility: hidden; transition: all 0.3s ease; display: flex; flex-direction: column; padding: 10px; transform: translateY(10px); pointer-events: none;}
    .header-wishlist-trigger:hover .wishlist-hover-menu { opacity: 1; visibility: visible; transform: translateY(0); pointer-events: auto; }
    .wishlist-hover-menu::before { content: ""; position: absolute; top: -16px; right: 55px; border: 8px solid transparent; border-bottom-color: #333; }
    .wishlist-hover-menu::after { content: ""; position: absolute; top: -14px; right: 55px; border: 8px solid transparent; border-bottom-color: #1e1e1e; }
    .wl-menu-title { font-size: 13px; color: #888; text-transform: uppercase; font-weight: bold; padding: 5px 10px 10px 10px; border-bottom: 1px solid #333; margin: 0 0 10px 0; }
    .wl-mini-item { display: flex; align-items: center; gap: 12px; padding: 8px 10px; border-radius: 8px; text-decoration: none; color: #fff; transition: 0.2s; }
    .wl-mini-item:hover { background: #2a2a2a; }
    .wl-mini-item img { width: 40px; height: 40px; object-fit: contain; background: #fff; border-radius: 4px; }
    .wl-mini-info { flex: 1; overflow: hidden; }
    .wl-mini-name { font-size: 13px; font-weight: bold; margin: 0 0 3px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #e0e0e0; }
    .wl-mini-price { font-size: 12px; color: #9b59b6; font-weight: bold; }
    .wl-btn-more { display: block; background: #e74c3c; color: white; text-align: center; text-decoration: none; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 14px; margin-top: 10px; transition: 0.2s; }
    .wl-btn-more:hover { background: #c0392b; }

    /* ========================================= */
    /* RESPONSIVE & HAMBURGER MENU               */
    /* ========================================= */
    .hamburger-btn { display: none; background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; margin-left: 20px; transition: 0.3s; }
    .hamburger-btn:hover { color: #9b59b6; }
    
    @media (max-width: 992px) {
        .hamburger-btn { display: block; }
        
        /* Navigația mobilă - animată și fixată */
        nav { 
            display: flex; /* O ținem pe flex ca să o putem anima */
            position: absolute; 
            top: 100%; 
            left: 0; 
            width: 100%; 
            background: #1f1f1f; 
            flex-direction: column; 
            align-items: flex-start;
            border-bottom: 2px solid #8e44ad; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            
            /* -- ANIMAȚIA DE SLIDE-DOWN -- */
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none; /* Ca să nu dai click din greșeală când e ascunsă */
            padding-bottom: 0;
        }
        
        nav.active { 
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
            padding-bottom: 10px;
        }
        
        nav > a { 
            width: 100%; 
            box-sizing: border-box; 
            padding: 15px 40px; 
            margin: 0; 
            border-top: 1px solid #333; 
        }

        /* Ajustăm căsuța de căutare să încapă mai bine */
        .search-container { margin: 0 15px; }
        header { padding: 15px 20px; }
        
        /* Pe ecrane foarte mici ascundem textul logo-ului, lăsăm doar PC SHOP */
        .logo { font-size: 22px; }
    }

    @media (max-width: 600px) {
        /* Ascundem search-ul în header pe mobil (opțional, pentru spațiu) */
        .search-container { display: none; }
        .wishlist-hover-menu { right: -80px; width: 280px; }
        .wishlist-hover-menu::before, .wishlist-hover-menu::after { right: 85px; }
    }
</style>

<header>
    <a href="home.php" class="logo">PC <span>SHOP</span></a>
    
    <?php if ($show_search): ?>
        <div class="search-container" id="searchContainer">
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="liveSearchInput" placeholder="Caută componente..." oninput="liveSearch(this.value)" autocomplete="off">
            </div>
            <div id="searchResults" class="search-results-dropdown"></div>
        </div>
    <?php endif; ?>

    <div class="header-right">
        <nav id="mobileNav">
            <a href="home.php">Acasă</a>
            <a href="componente.php">Componente</a>
            <a href="configurator.php">Configurator</a>
            <a href="contact.php">Contact</a>
            
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_products.php" style="color:#e74c3c; font-weight:bold;">ADMIN</a>
            <?php endif; ?>
        </nav>

        <div class="header-icons">
            <div class="header-wishlist-trigger" title="Favoritele mele">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                
                <div class="wishlist-hover-menu">
                    <div class="wl-menu-title">Favorite adăugate recent</div>
                    
                    <?php if (empty($header_wishlist_items)): ?>
                        <p style="text-align: center; color: #888; font-size: 13px; padding: 20px 0;">Nu ai produse salvate. 🤍</p>
                    <?php else: ?>
                        <?php foreach ($header_wishlist_items as $item): ?>
                            <?php $imgSrc = (!empty($item['imagine_url'])) ? ((strpos($item['imagine_url'], 'images') === 0) ? $item['imagine_url'] : "images/" . $item['imagine_url']) : "https://placehold.co/100?text=Fara+Poza"; ?>
                            <a href="produs.php?id=<?php echo $item['id']; ?>" class="wl-mini-item">
                                <img src="<?php echo $imgSrc; ?>" alt="Poza">
                                <div class="wl-mini-info">
                                    <p class="wl-mini-name"><?php echo htmlspecialchars($item['nume']); ?></p>
                                    <span class="wl-mini-price"><?php echo number_format($item['pret'], 2, ',', '.'); ?> RON</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        
                        <a href="wishlist.php" class="wl-btn-more">Vezi mai multe ❤️</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="<?php echo $linkProfil; ?>" title="Contul Meu" style="font-size: 20px; margin-left: 20px; text-decoration: none;">👤</a>
            
            <div class="cart-trigger" onclick="toggleCartModal()" title="Coșul tău">
                🛒 <span class="cart-count" id="cartCount">0</span>
            </div>
            
            <button class="hamburger-btn" onclick="toggleHamburgerMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
        </div>
    </div>
</header>

<script>
    /* Script Hamburger Menu */
    function toggleHamburgerMenu() {
        const nav = document.getElementById('mobileNav');
        nav.classList.toggle('active');
    }

    /* Scriptul Live Search */
    let searchTimeout = null;
    function liveSearch(query) {
        clearTimeout(searchTimeout);
        const resultsBox = document.getElementById('searchResults');
        if (!resultsBox) return; 

        if (query.trim().length < 2) {
            resultsBox.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`search_api.php?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                resultsBox.innerHTML = '';
                if (data.length === 0) {
                    resultsBox.innerHTML = '<div class="search-no-results">Nu am găsit componente pentru "' + query + '" 😔</div>';
                } else {
                    data.forEach(prod => {
                        let saleBadge = (prod.pret_vechi > prod.pret) ? `<span style="background:#e74c3c; color:white; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:10px;">REDUCERE</span>` : '';
                        resultsBox.innerHTML += `
                            <a href="produs.php?id=${prod.id}" class="search-result-item">
                                <img src="${prod.imagine_url}" alt="Imagine Produs">
                                <div class="search-result-info">
                                    <div class="search-result-title">${prod.nume} ${saleBadge}</div>
                                    <div class="search-result-price">${prod.pret_formatat} RON</div>
                                </div>
                            </a>
                        `;
                    });
                }
                resultsBox.style.display = 'block';
            } catch (error) { console.error("Eroare:", error); }
        }, 300);
    }

    document.addEventListener('click', function(e) {
        const searchContainer = document.getElementById('searchContainer');
        if (searchContainer && !searchContainer.contains(e.target)) {
            const results = document.getElementById('searchResults');
            if (results) results.style.display = 'none';
        }
    });
</script>