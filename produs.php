<?php
require 'config.php';

$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: home.php"); exit; }
$id_produs = (int)$_GET['id'];

$stmt = $mysqli->prepare("SELECT p.*, c.nume as nume_categorie FROM produse p LEFT JOIN categorii c ON p.categorie_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $id_produs);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h1 style='color:white; text-align:center; margin-top:50px;'>Produsul nu a fost găsit. <a href='home.php' style='color:#9b59b6;'>Întoarce-te.</a></h1>";
    exit;
}
$produs = $result->fetch_assoc();

$stmt_specs = $mysqli->prepare("SELECT * FROM specificatii_produse WHERE produs_id = ?");
$stmt_specs->bind_param("i", $id_produs);
$stmt_specs->execute();
$specs = $stmt_specs->get_result();

$imgSrc = !empty($produs['imagine_url']) ? ((strpos($produs['imagine_url'], 'images') === 0) ? $produs['imagine_url'] : "images/" . $produs['imagine_url']) : "https://placehold.co/600x600/2c2c2c/a0a0a0?text=" . urlencode($produs['nume']);

$stocClass = 'stock-ok'; $stocText = 'În Stoc';
if ($produs['stoc'] == 0) { $stocClass = 'stock-out'; $stocText = 'Epuizat'; } 
elseif ($produs['stoc'] < 5) { $stocClass = 'stock-low'; $stocText = 'Stoc Limitat'; }

// LOGICA REDUCERI DIN BAZA DE DATE PENTRU PAGINA DE PRODUS
$pret_afisat = $produs['pret'];
$pret_vechi = !empty($produs['pret_vechi']) ? $produs['pret_vechi'] : 0;
$is_on_sale = ($pret_vechi > $pret_afisat);
$procent_reducere = 0;
if ($is_on_sale) {
    $procent_reducere = round((($pret_vechi - $pret_afisat) / $pret_vechi) * 100);
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $produs['nume']; ?> - PC Shop</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; }
        header { background-color: #1f1f1f; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 26px; font-weight: bold; color: #fff; letter-spacing: 1px; text-decoration: none;} .logo span { color: #9b59b6; }
        nav { display: flex; align-items: center; }
        nav a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: color 0.3s; } nav a:hover { color: #9b59b6; }
        .cart-trigger { position: relative; cursor: pointer; margin-left: 30px; display: flex; align-items: center; color: #bbb; transition: color 0.3s; } .cart-trigger:hover { color: #9b59b6; }
        .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 5px; position: absolute; top: -5px; right: -8px; font-weight: bold; }

        .product-page-container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
        
        .top-section { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 50px; }
        .product-image-section { background: #1e1e1e; border-radius: 12px; padding: 20px; border: 1px solid #333; display: flex; align-items: center; justify-content: center; }
        .product-image-section img { max-width: 100%; max-height: 500px; object-fit: contain; border-radius: 8px; }
        .product-buy-section { display: flex; flex-direction: column; justify-content: center; }
        
        .breadcrumb { color: #888; font-size: 14px; margin-bottom: 10px; } .breadcrumb a { color: #9b59b6; text-decoration: none; } .breadcrumb a:hover { text-decoration: underline; }
        .product-title-large { font-size: 32px; color: #fff; margin: 0 0 15px 0; line-height: 1.2; }
        .badge { display: inline-block; padding: 6px 12px; font-size: 14px; font-weight: bold; border-radius: 4px; margin-bottom: 20px; width: fit-content; }
        .stock-out { background-color: #c0392b; color: white; } .stock-low { background-color: #f39c12; color: #111; } .stock-ok { background-color: #27ae60; color: white; }
        
        /* Prețuri pentru pagina de produs */
        .price-old-large { color: #888; text-decoration: line-through; font-size: 20px; margin-bottom: 5px; }
        .price-new-large { font-size: 40px; font-weight: bold; color: #e74c3c; margin-bottom: 30px; }
        .price-normal-large { font-size: 40px; font-weight: bold; color: #9b59b6; margin-bottom: 30px; }
        .badge-sale-large { background-color: #e74c3c; color: white; display: inline-block; padding: 6px 12px; font-size: 14px; font-weight: bold; border-radius: 4px; margin-bottom: 10px; width: fit-content; }

        .btn-buy-large { background-color: #9b59b6; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 18px; transition: background 0.2s; width: 100%; max-width: 350px; box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3); }
        .btn-buy-large:hover { background-color: #8e44ad; transform: translateY(-2px); } .btn-buy-large:disabled { background-color: #444; cursor: not-allowed; color: #888; box-shadow: none; transform: none; }

        .extended-info-section { background: #1e1e1e; border-radius: 12px; padding: 40px; border: 1px solid #333; }
        .section-header { font-size: 24px; color: #fff; border-bottom: 2px solid #8e44ad; padding-bottom: 10px; margin-top: 0; margin-bottom: 25px; display: inline-block; }
        .description-content { color: #ccc; line-height: 1.8; font-size: 16px; margin-bottom: 50px; }
        
        .specs-table { width: 100%; border-collapse: collapse; background: #252525; border-radius: 8px; overflow: hidden; border: 1px solid #333; }
        .specs-table th { background: #1a1a1a; padding: 15px; text-align: left; color: #fff; font-size: 18px; border-bottom: 1px solid #333; }
        .specs-table td { padding: 15px; border-bottom: 1px solid #333; color: #ccc; }
        .specs-table tr:hover td { background-color: #2a2a2a; }
        .specs-table tr:last-child td { border-bottom: none; }
        .specs-table td:first-child { font-weight: bold; width: 35%; color: #9b59b6; border-right: 1px solid #333; }

        @media (max-width: 768px) { .top-section { grid-template-columns: 1fr; } .extended-info-section { padding: 20px; } .specs-table td:first-child { width: 50%; } }

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
<?php if(!isset($_GET['modal'])) include 'header.php'; ?>
    
    <main class="product-page-container">
        
        <div class="top-section">
            <div class="product-image-section">
                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($produs['nume']); ?>">
            </div>

            <div class="product-buy-section">
                <div class="breadcrumb">
                    <a href="home.php">Acasă</a> &gt; 
                    <a href="#"><?php echo $produs['nume_categorie']; ?></a> &gt; 
                    <span><?php echo $produs['nume']; ?></span>
                </div>

                <h1 class="product-title-large"><?php echo $produs['nume']; ?></h1>
                
                <div style="display: flex; gap: 10px;">
                    <?php if($is_on_sale): ?>
                        <div class="badge-sale-large">-<?php echo $procent_reducere; ?>% REDUCERE</div>
                    <?php endif; ?>
                    <div class="badge <?php echo $stocClass; ?>"><?php echo $stocText; ?></div>
                </div>

                <?php if($is_on_sale): ?>
                    <div class="price-old-large"><?php echo number_format($pret_vechi, 0, ',', '.'); ?> RON</div>
                    <div class="price-new-large"><?php echo number_format($pret_afisat, 0, ',', '.'); ?> RON</div>
                <?php else: ?>
                    <div class="price-normal-large"><?php echo number_format($pret_afisat, 0, ',', '.'); ?> RON</div>
                <?php endif; ?>

                <?php if(!isset($_GET['modal'])): ?>
                    <?php if($produs['stoc'] > 0): ?>
                        <button class="btn-buy-large" onclick="addToCart(<?php echo $produs['id']; ?>)">🛒 Adaugă în coș</button>
                    <?php else: ?>
                        <button class="btn-buy-large" disabled>Stoc Epuizat</button>
                    <?php endif; ?>
                <?php endif; ?>
                
            </div>
        </div>

        <div class="extended-info-section">
            
            <h2 class="section-header">Prezentare Produs</h2>
            <div class="description-content">
                <?php 
                    if (!empty($produs['descriere'])) {
                        echo nl2br(htmlspecialchars($produs['descriere'])); 
                    } else {
                        echo "Descrierea detaliată pentru acest produs nu a fost încă adăugată.";
                    }
                ?>
            </div>

            <h2 class="section-header">Specificații Tehnice</h2>
            <table class="specs-table">
                <?php 
                if ($specs->num_rows > 0) {
                    while($spec = $specs->fetch_assoc()) {
                        if ($spec['valoare_specificatie'] === '__SECTIUNE__') {
                            echo "<tr style='background: #333;'>";
                            echo "<td colspan='2' style='color: #fff; font-size: 18px; font-weight: bold; padding: 10px 15px; border-bottom: 1px solid #444; text-transform: uppercase;'>";
                            echo htmlspecialchars($spec['nume_specificatie']);
                            echo "</td>";
                            echo "</tr>";
                        } else {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($spec['nume_specificatie']) . "</td>";
                            echo "<td>" . htmlspecialchars($spec['valoare_specificatie']) . "</td>";
                            echo "</tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='2' style='text-align:center; color:#888;'>Nu există specificații detaliate pentru acest produs.</td></tr>";
                }
                ?>
            </table>

        </div>

    </main>

    <?php if(!isset($_GET['modal'])): ?>
        <button class="chat-toggle-btn" onclick="toggleChat()"><svg viewBox="0 0 24 24" width="30" height="30" fill="white"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg></button> 
        <div class="chat-container" id="chatBox">
            <div class="chat-header"><span>Asistent PC Shop</span><span class="close-chat" onclick="toggleChat()">✖</span></div>
            <div class="chat-body" id="chatBody"><div class="message bot-message">Salut! Întreabă-mă despre componente.</div></div>
            <div class="chat-footer">
                <input type="text" class="chat-input" id="userInput" placeholder="Scrie un mesaj..." onkeypress="if(event.key==='Enter') sendMessage()">
                <button class="send-btn" onclick="sendMessage()"><svg viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>
            </div>
        </div>

        <script>
            let chatHistory=[]; function toggleChat(){ const c=document.getElementById('chatBox'); c.style.display=(c.style.display==='none'||c.style.display==='')?'flex':'none'; if(c.style.display==='flex') setTimeout(()=>document.getElementById('userInput').focus(),100); }
            async function sendMessage(){ const i=document.getElementById('userInput'); const txt=i.value.trim(); const b=document.getElementById('chatBody'); if(!txt)return; b.innerHTML+=`<div class="message user-message">${txt}</div>`; chatHistory.push({role:"user",content:txt}); i.value=""; b.scrollTop=b.scrollHeight; b.innerHTML+=`<div class="message bot-message" id="loading-indicator">...</div>`; b.scrollTop=b.scrollHeight; try{ const r=await fetch('chat_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({history:chatHistory})}); const d=await r.json(); document.getElementById('loading-indicator').remove(); b.innerHTML+=`<div class="message bot-message">${d.reply||"Eroare."}</div>`; chatHistory.push({role:"assistant",content:d.reply}); }catch(e){ document.getElementById('loading-indicator').remove(); b.innerHTML+=`<div class="message bot-message">Eroare conexiune.</div>`; } b.scrollTop=b.scrollHeight; }
        </script>
    <?php endif; ?>

    <?php include 'cart_modal.php'; ?>
    <?php if(!isset($_GET['modal'])) include 'footer.php'; ?>
</body>
</html>