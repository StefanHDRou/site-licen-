<?php
require 'config.php'; // Aduce conexiunea, sesiunea și $linkProfil automat
// Extragem 12 produse pentru home
$query = "SELECT p.*, c.nume as nume_categorie 
          FROM produse p 
          LEFT JOIN categorii c ON p.categorie_id = c.id
          ORDER BY p.id DESC LIMIT 12";
$result = $mysqli->query($query);

// Extragem categoriile unice din produsele afișate pentru a construi Tab-urile
$categorii_tabs = [];
$produse_array = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $produse_array[] = $row;
        if (!in_array($row['nume_categorie'], $categorii_tabs) && !empty($row['nume_categorie'])) {
            $categorii_tabs[] = $row['nume_categorie'];
        }
    }
}

// Extragem ID-urile produselor favorite pentru userul curent (pentru inimioare)
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
    <title>PC Shop - Componente Hardware Premium</title>
    <style>
        /* ================= STILURI DE BAZĂ ================= */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; overflow-x: hidden; }
        
        /* 1. TOP BAR NOTIFICĂRI */
        .top-bar { background: linear-gradient(90deg, #f39c12, #e67e22); color: #000; text-align: center; padding: 8px 15px; font-size: 13px; font-weight: bold; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 1002;}
        .top-bar span { background: #000; color: #f39c12; padding: 2px 8px; border-radius: 4px; margin-left: 10px; }

        /* HEADER */
        header { background-color: #1f1f1f; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0px; z-index: 100; }
        .logo { font-size: 26px; font-weight: bold; color: #fff; text-decoration: none;} .logo span { color: #9b59b6; }
        nav { display: flex; align-items: center; }
        nav a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: 0.3s; } nav a:hover { color: #9b59b6; }
        .cart-trigger { position: relative; cursor: pointer; margin-left: 30px; display: flex; align-items: center; color: #bbb; transition: 0.3s; } .cart-trigger:hover { color: #9b59b6; }
        .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 5px; position: absolute; top: -5px; right: -8px; font-weight: bold; }

        /* 2. HERO CAROUSEL */
        .carousel-container { position: relative; width: 100%; height: 400px; overflow: hidden; background: #1a1a1a; border-bottom: 1px solid #333; }
        .carousel-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease-in-out; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column; background-size: cover; background-position: center; }
        .carousel-slide.active { opacity: 1; z-index: 10; }
        .carousel-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1; }
        .carousel-content { position: relative; z-index: 2; max-width: 800px; padding: 20px; }
        .carousel-content h1 { font-size: 3.5em; margin: 0 0 15px 0; color: #fff; text-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        .carousel-content p { font-size: 1.2em; color: #ddd; margin-bottom: 25px; }
        .btn-hero { background: #9b59b6; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px; transition: 0.3s; box-shadow: 0 4px 15px rgba(155,89,182,0.4); }
        .btn-hero:hover { background: #8e44ad; transform: translateY(-2px); }
        .carousel-dots { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 20; display: flex; gap: 10px; }
        .dot { width: 12px; height: 12px; background: rgba(255,255,255,0.3); border-radius: 50%; cursor: pointer; transition: 0.3s; }
        .dot.active { background: #9b59b6; box-shadow: 0 0 10px #9b59b6; }

        /* 3. FLASH SALE BANNER */
        .flash-sale-wrapper { max-width: 1200px; margin: 40px auto 0; padding: 0 20px; }
        .flash-sale-box { background: linear-gradient(45deg, #2c0e3a, #1a0822); border: 2px solid #8e44ad; border-radius: 12px; padding: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(142, 68, 173, 0.2); position: relative; overflow: hidden; }
        .flash-sale-box::before { content: "🔥"; position: absolute; font-size: 150px; opacity: 0.05; right: -20px; top: -40px; }
        .flash-info h2 { margin: 0 0 10px 0; color: #fff; font-size: 28px; }
        .flash-info p { margin: 0; color: #aaa; font-size: 16px; }
        .timer-box { display: flex; gap: 15px; text-align: center; }
        .time-unit { background: rgba(0,0,0,0.5); padding: 10px 15px; border-radius: 8px; border: 1px solid #444; min-width: 60px; }
        .time-unit span { display: block; font-size: 24px; font-weight: bold; color: #f39c12; }
        .time-unit small { font-size: 11px; color: #888; text-transform: uppercase; }

        /* 4. TAB-URI CATEGORII */
        .container { padding: 40px 20px; max-width: 1400px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 30px; color: #fff; font-size: 28px; position: relative; }
        .tabs-container { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; flex-wrap: wrap; }
        .tab-btn { background: #1e1e1e; color: #aaa; border: 1px solid #333; padding: 10px 25px; border-radius: 25px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .tab-btn:hover { border-color: #8e44ad; color: #fff; }
        .tab-btn.active { background: #8e44ad; color: #fff; border-color: #8e44ad; box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3); }

        /* ================= GRID PRODUSE (Sincronizat cu catalogul) ================= */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; max-width: 1400px; margin: 0 auto; padding: 0 40px; }
        .product-card { background-color: #1e1e1e; border-radius: 12px; overflow: hidden; border: 1px solid #333; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; position: relative; text-align: left; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(155, 89, 182, 0.2); border-color: #8e44ad; }

        .badge-container { position: absolute; top: 10px; left: 10px; display: flex; flex-direction: column; gap: 5px; z-index: 10; pointer-events: none; }
        .badge { padding: 5px 10px; font-size: 12px; font-weight: bold; border-radius: 4px; width: fit-content; }
        .stock-out { background-color: #c0392b; color: white; } .stock-low { background-color: #f39c12; color: #111; } .stock-ok { background-color: #27ae60; color: white; }
        .badge-sale { background-color: #e74c3c; color: white; box-shadow: 0 2px 10px rgba(231, 76, 60, 0.4); font-size: 11px; }

        /* STIL WISHLIST LINK (CU SVG) */
        .wishlist-link { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); width: 36px; height: 36px; border-radius: 50%; text-decoration: none; z-index: 15; transition: 0.3s; border: 1px solid #444; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
        .wishlist-link:hover { background: rgba(0,0,0,0.9); transform: scale(1.1); border-color: #e74c3c; }
        .wishlist-link svg { transition: 0.3s; }

        .product-img { width: 100%; height: 220px; object-fit: contain; background-color: #fff; border-bottom: 1px solid #333; }
        .product-info { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .category-tag { font-size: 12px; text-transform: uppercase; color: #8e44ad; font-weight: bold; margin-bottom: 5px; }
        .product-title { font-size: 18px; font-weight: 600; margin: 0 0 10px 0; color: #fff; height: 50px; overflow: hidden; text-overflow: ellipsis; }
        .specs-list { font-size: 13px; color: #aaa; margin-bottom: 15px; flex: 1; padding-left: 20px; margin-top: 0; } .specs-list li { margin-bottom: 4px; } .specs-list strong { color: #ccc; }

        .price-box { margin-top: auto; border-top: 1px solid #333; padding-top: 15px; display: flex; justify-content: space-between; align-items: flex-end; }
        .price-old { color: #888; text-decoration: line-through; font-size: 14px; margin-bottom: 2px; }
        .price-new { color: #e74c3c; font-weight: bold; font-size: 22px; }
        .price-normal { color: #9b59b6; font-weight: bold; font-size: 22px; }

        .btn-buy { background-color: #9b59b6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
        .btn-buy:hover { background-color: #8e44ad; } .btn-buy:disabled { background-color: #444; cursor: not-allowed; color: #888; }

        @media (max-width: 768px) { .flash-sale-box { flex-direction: column; text-align: center; gap: 20px; } .carousel-content h1 { font-size: 2em; } }
    </style>
</head>
<body>

    <div class="top-bar" style="position: sticky; top: 0; z-index: 100;">
        🚚 Transport Gratuit la comenzi de peste 500 RON! Folosește codul: <span>PCSHOP10</span> pentru 10% Extra Reducere!
    </div>

    <?php include 'header.php'; ?>

    <div class="carousel-container">
        <div class="carousel-slide active" style="background-image: url('https://images.unsplash.com/photo-1587202372634-32705e3bf49c?q=80&w=2000&auto=format&fit=crop');">
            <div class="carousel-overlay"></div>
            <div class="carousel-content">
                <h1>Asamblează PC-ul de Vis</h1>
                <p>Folosește configuratorul nostru inteligent pentru a alege componente 100% compatibile.</p>
                <a href="configurator.php" class="btn-hero">Deschide Configuratorul ➔</a>
            </div>
        </div>
        <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=2000&auto=format&fit=crop');">
            <div class="carousel-overlay"></div>
            <div class="carousel-content">
                <h1>Săptămâna Performanței</h1>
                <p>Până la -20% reducere la procesoare și plăci video selecționate. Stoc limitat!</p>
                <a href="componente.php?la_reducere=1" class="btn-hero">Vezi Ofertele</a>
            </div>
        </div>
        <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1603302576837-37561b2e2302?q=80&w=2000&auto=format&fit=crop');">
            <div class="carousel-overlay"></div>
            <div class="carousel-content">
                <h1>Setup-uri de Top</h1>
                <p>Echipează-ți biroul cu cele mai noi periferice și accesorii pentru gaming și productivitate.</p>
                <a href="componente.php" class="btn-hero">Explorează Gama</a>
            </div>
        </div>

        <div class="carousel-dots">
            <div class="dot active" onclick="goToSlide(0)"></div>
            <div class="dot" onclick="goToSlide(1)"></div>
            <div class="dot" onclick="goToSlide(2)"></div>
        </div>
    </div>

    <div class="flash-sale-wrapper" id="flashSaleBanner">
        <div class="flash-sale-box">
            <div class="flash-info">
                <h2 id="flashTitle">⚡ Oferta Zilei</h2>
                <p id="flashText">Prețuri imbatabile la componente premium. Grăbește-te, oferta expiră în:</p>
            </div>
            <div class="timer-box" id="countdownTimer">
                <div class="time-unit"><span id="days">00</span><small>Zile</small></div>
                <div class="time-unit"><span id="hours">00</span><small>Ore</small></div>
                <div class="time-unit"><span id="mins">00</span><small>Min</small></div>
                <div class="time-unit"><span id="secs">00</span><small>Sec</small></div>
            </div>
        </div>
    </div>

    <main class="container" id="produse">
        <h2 class="section-title">Produse Recomandate</h2>
        
        <div class="tabs-container">
            <button class="tab-btn active" onclick="filterProducts('toate', this)">Toate Produsele</button>
            <?php foreach($categorii_tabs as $cat): ?>
                <button class="tab-btn" onclick="filterProducts('<?php echo htmlspecialchars($cat); ?>', this)">
                    <?php echo htmlspecialchars($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="products-grid">
            <?php 
            if (!empty($produse_array)): 
                foreach($produse_array as $produs): 
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
            
            <div class="product-card" data-category="<?php echo htmlspecialchars($produs['nume_categorie']); ?>">
                <button class="wishlist-link" onclick="toggleFav(event, this, <?php echo $produs['id']; ?>)" title="<?php echo $este_favorit ? 'Elimină de la favorite' : 'Adaugă la favorite'; ?>">
                    <?php if ($este_favorit): ?>
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
                    <div class="category-tag"><?php echo isset($produs['nume_categorie']) ? htmlspecialchars($produs['nume_categorie']) : 'Componentă PC'; ?></div>
                    
                    <a href="produs.php?id=<?php echo $produs['id']; ?>" style="text-decoration: none;">
                        <h3 class="product-title" style="transition: color 0.3s;" onmouseover="this.style.color='#9b59b6'" onmouseout="this.style.color='#fff'"><?php echo htmlspecialchars($produs['nume']); ?></h3>
                    </a>
                    
                    <ul class="specs-list">
                        <?php 
                        if ($result_specs && $result_specs->num_rows > 0) {
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

            <?php 
                endforeach; 
            else: 
            ?>
                <p style="text-align:center; width:100%;">Nu există produse în baza de date.</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 50px; margin-bottom: 20px;">
            <a href="componente.php" style="background: transparent; color: #9b59b6; border: 2px solid #9b59b6; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; transition: 0.3s; display: inline-block;">
                Vezi oferta completă de componente ➔
            </a>
        </div>
    </main>

    <script>
        /* LOGICĂ CAROUSEL */
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.dot');
        const slideInterval = 5000;
        
        function goToSlide(index) {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            currentSlide = index;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }
        setInterval(() => { let next = (currentSlide + 1) % slides.length; goToSlide(next); }, slideInterval);

        /* LOGICĂ COUNTDOWN TIMER */
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(23, 59, 59, 0);

        let timer;
        function updateTimer() {
            const now = new Date().getTime();
            const distance = tomorrow - now;

            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdownTimer").style.display = "none";
                document.getElementById("flashTitle").innerText = "Oferta a Expirat 😔";
                document.getElementById("flashText").innerText = "Stai pe aproape! Următoarea ofertă se deblochează curând.";
                document.getElementById("flashSaleBanner").style.opacity = "0.6";
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerText = days < 10 ? '0'+days : days;
            document.getElementById("hours").innerText = hours < 10 ? '0'+hours : hours;
            document.getElementById("mins").innerText = minutes < 10 ? '0'+minutes : minutes;
            document.getElementById("secs").innerText = seconds < 10 ? '0'+seconds : seconds;
        }
        timer = setInterval(updateTimer, 1000);
        updateTimer();

        /* LOGICĂ TABS (FILTRARE FRONTEND) */
        function filterProducts(category, btnElement) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (category === 'toate' || card.getAttribute('data-category') === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        /* LOGICĂ FAVORITE FĂRĂ REFRESH */
        async function toggleFav(event, btn, productId) {
            event.preventDefault(); 

            let formData = new FormData();
            formData.append('product_id', productId);

            try {
                let response = await fetch('wishlist_action.php', { method: 'POST', body: formData });
                let result = await response.json();

                if (result.status === 'not_logged_in') {
                    window.location.href = 'login.php';
                    return;
                }

                const dropdownMenu = document.querySelector('.wishlist-hover-menu');
                if (dropdownMenu && result.html) {
                    dropdownMenu.innerHTML = result.html;
                }

                const svg = btn.querySelector('svg');
                const path = btn.querySelector('path');

                if (result.status === 'added') {
                    svg.setAttribute('fill', '#e74c3c');
                    path.setAttribute('stroke', '#e74c3c');
                } else if (result.status === 'removed') {
                    svg.setAttribute('fill', 'none');
                    path.setAttribute('stroke', '#ffffff');
                }
            } catch (error) { console.error("Eroare la favorite", error); }
        }
    </script>

    <?php require 'cart_modal.php'; ?>
    <?php require 'footer.php'; ?>
</body>
</html>