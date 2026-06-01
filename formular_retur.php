<?php
require 'config.php';
// 1. INCLUDEM SISTEMUL DE EMAIL (NOU)
require 'send_email.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id === 0) {
    header('Location: profile.php');
    exit;
}

// 2. SECURITATE: Verificăm dacă comanda există și aparține acestui user
$stmtCheck = $mysqli->prepare("SELECT id, total, created_at FROM orders WHERE id = ? AND user_id = ?");
$stmtCheck->bind_param("ii", $order_id, $user_id);
$stmtCheck->execute();
$orderData = $stmtCheck->get_result()->fetch_assoc();

if (!$orderData) {
    header('Location: profile.php');
    exit;
}

// Preluăm adresa, numele și email-ul userului pentru precompletare și notificare (MODIFICAT)
$stmtUser = $mysqli->prepare("SELECT nume, email, adresa FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$adresa_user = $userData['adresa'] ?? '';
$email_client = $userData['email'] ?? '';
$nume_client = $userData['nume'] ?? 'Client';

$message = "";
$messageType = "";

// 3. PROCESAREA FORMULARULUI DE RETUR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_retur'])) {
    $produse_selectate = isset($_POST['produse_retur']) ? $_POST['produse_retur'] : [];
    $motiv = $mysqli->real_escape_string($_POST['motiv']);
    $metoda_retur = $mysqli->real_escape_string($_POST['metoda_retur']);
    
    // Stabilim adresa de preluare în funcție de metoda aleasă
    if ($metoda_retur === 'easybox') {
        $adresa_preluare = "Locker: " . $mysqli->real_escape_string($_POST['adresa_easybox']);
    } else {
        $adresa_preluare = "Domiciliu: " . $mysqli->real_escape_string($_POST['adresa_curier']);
    }

    if (empty($produse_selectate)) {
        $message = "Eroare: Trebuie să selectezi cel puțin un produs pentru retur!";
        $messageType = "error";
    } elseif (empty($motiv)) {
        $message = "Eroare: Te rugăm să alegi un motiv pentru retur.";
        $messageType = "error";
    } elseif ($metoda_retur === 'easybox' && empty($_POST['adresa_easybox'])) {
        $message = "Eroare: Te rugăm să selectezi un locker Easybox de pe hartă.";
        $messageType = "error";
    } else {
        $stmtInsert = $mysqli->prepare("INSERT INTO cereri_retur (order_id, user_id, product_id, motiv, metoda_retur, adresa_preluare) VALUES (?, ?, ?, ?, ?, ?)");
        
        $produse_procesate = 0;
        $nume_produse_returnate = []; // Salvăm numele produselor pentru email

        foreach ($produse_selectate as $pid) {
            $pid = (int)$pid;
            
            $checkDublura = $mysqli->query("SELECT id FROM cereri_retur WHERE order_id = $order_id AND product_id = $pid LIMIT 1");
            if ($checkDublura->num_rows == 0) {
                $stmtInsert->bind_param("iiisss", $order_id, $user_id, $pid, $motiv, $metoda_retur, $adresa_preluare);
                $stmtInsert->execute();
                $produse_procesate++;

                // Extragem numele produsului pentru a-l pune în email
                $qNume = $mysqli->query("SELECT nume FROM produse WHERE id = $pid");
                if ($rNume = $qNume->fetch_assoc()) {
                    $nume_produse_returnate[] = $rNume['nume'];
                }
            }
        }
        
        if ($produse_procesate > 0) {
            $message = "Cererea a fost înregistrată cu succes! Ai primit un email cu detaliile returului.";
            $messageType = "success";

            // --- NOU: GENERĂM ȘI TRIMITEM EMAIL-UL ---
            if (!empty($email_client)) {
                $lista_html = "<ul style='padding-left: 20px; margin-bottom: 20px;'>";
                foreach($nume_produse_returnate as $n) {
                    $lista_html .= "<li style='margin-bottom: 5px; color: #555;'>" . htmlspecialchars($n) . "</li>";
                }
                $lista_html .= "</ul>";

                $emailBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                    <div style='background-color: #e74c3c; padding: 20px; text-align: center; color: white;'>
                        <h2 style='margin: 0; font-size: 24px;'>Confirmare Cerere Retur</h2>
                    </div>
                    <div style='padding: 30px; background-color: #ffffff; color: #333;'>
                        <p style='font-size: 16px; margin-top: 0;'>Salut <b>$nume_client</b>,</p>
                        <p style='font-size: 15px; line-height: 1.6;'>Am primit cererea ta de retur pentru comanda <b>#$order_id</b>. Colegii noștri vor analiza solicitarea în cel mai scurt timp.</p>
                        
                        <h3 style='color: #e74c3c; border-bottom: 2px solid #f2f2f2; padding-bottom: 8px; margin-top: 25px;'>Produse vizate:</h3>
                        $lista_html
                        
                        <h3 style='color: #e74c3c; border-bottom: 2px solid #f2f2f2; padding-bottom: 8px; margin-top: 25px;'>Detalii Preluare</h3>
                        <p style='margin: 5px 0;'><b>Motiv retur:</b> $motiv</p>
                        <p style='margin: 5px 0;'><b>Metodă:</b> " . ucfirst($metoda_retur) . "</p>
                        <p style='margin: 5px 0;'><b>Adresă / Locker:</b> $adresa_preluare</p>
                        
                        <div style='margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #f39c12; border-radius: 4px;'>
                            <p style='margin: 0; font-size: 14px; color: #555;'>Asigură-te că produsele sunt ambalate corespunzător, în cutia originală, alături de toate accesoriile primite.</p>
                        </div>
                    </div>
                    <div style='background-color: #2c3e50; color: #aaa; text-align: center; padding: 15px; font-size: 12px;'>
                        &copy; " . date('Y') . " PC Shop. Acesta este un mesaj automat.
                    </div>
                </div>
                ";

                trimiteEmail($email_client, $nume_client, "Cerere Retur Inregistrata - Comanda #$order_id", $emailBody);
            }
            // -----------------------------------------

        } else {
            $message = "Produsele selectate se află deja într-o cerere de retur activă.";
            $messageType = "error";
        }
    }
}

// 4. Extragem produsele din această comandă specifică
$sqlItems = "SELECT oi.product_id, oi.cantitate, oi.pret_unitar, p.nume, p.imagine_url 
             FROM order_items oi 
             JOIN produse p ON oi.product_id = p.id 
             WHERE oi.order_id = $order_id";
$resultItems = $mysqli->query($sqlItems);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Formular Retur - PC Shop</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .retur-container { max-width: 800px; margin: 50px auto; background: #1e1e1e; padding: 40px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: #e74c3c; margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 15px;}
        h3 { color: #fff; margin-top: 30px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .order-info { color: #aaa; margin-bottom: 30px; font-size: 15px; }
        .order-info strong { color: #fff; }

        .product-list { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
        .product-item { display: flex; align-items: center; background: #252525; padding: 15px; border-radius: 8px; border: 1px solid #444; cursor: pointer; transition: 0.2s;}
        .product-item:hover { border-color: #e74c3c; background: #2a2a2a; }
        .product-item input[type="checkbox"] { width: 20px; height: 20px; margin-right: 20px; accent-color: #e74c3c; cursor: pointer; }
        .product-item img { width: 60px; height: 60px; object-fit: contain; background: #fff; border-radius: 6px; margin-right: 20px; }
        .product-details { flex: 1; }
        .product-details h4 { margin: 0 0 5px 0; color: #fff; }
        .product-details p { margin: 0; color: #9b59b6; font-weight: bold; font-size: 14px; }

        .form-group { margin-bottom: 25px; }
        label { display: block; color: #aaa; margin-bottom: 10px; font-weight: bold; }
        select, textarea, input[type="text"] { width: 100%; padding: 12px; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 6px; font-size: 15px; outline: none; box-sizing: border-box; }
        select:focus, textarea:focus, input[type="text"]:focus { border-color: #e74c3c; }

        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .radio-label { display: flex; align-items: center; cursor: pointer; background: #252525; padding: 15px; border-radius: 8px; border: 1px solid #444; transition: 0.2s; }
        .radio-label:hover { border-color: #e74c3c; background: #2a2a2a; }
        .radio-label input { width: auto; margin-right: 15px; transform: scale(1.2); accent-color: #e74c3c; }
        
        #curierDetails, #easyboxDetails { display: none; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: 0; } }

        .btn-map { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-map:hover { background: #2980b9; transform: translateY(-2px); }
        .map-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 5000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .map-modal-content { background: #1e1e1e; width: 90%; max-width: 1000px; height: 80vh; border-radius: 12px; border: 2px solid #3498db; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .map-modal-header { background: #252525; padding: 15px 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .map-modal-header h3 { margin: 0; color: #fff; }
        .close-map-btn { color: #aaa; font-size: 28px; cursor: pointer; line-height: 1; transition: 0.2s; } .close-map-btn:hover { color: #e74c3c; }
        .map-modal-body { display: flex; flex: 1; overflow: hidden; }
        .map-left { flex: 2; position: relative; display: flex; align-items: center; justify-content: center; background: #121212; z-index: 1;}
        .map-right { flex: 1; background: #252525; border-left: 1px solid #333; overflow-y: auto; padding: 20px; z-index: 2;}
        .locker-item { background: #1e1e1e; border: 1px solid #444; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: 0.2s; }
        .locker-item:hover { border-color: #3498db; background: #2c3e50; }
        .locker-name { font-weight: bold; color: #fff; margin-bottom: 5px; font-size: 15px; }
        .locker-address { color: #aaa; font-size: 13px; }
        .leaflet-popup-content-wrapper { background: #252525; color: #fff; border-radius: 8px; }
        .leaflet-popup-tip { background: #252525; }
        .leaflet-popup-content b { color: #3498db; }
        .btn-select-map { background: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; width: 100%; transition: 0.3s; }
        .btn-select-map:hover { background: #c0392b; }

        .btn-submit { width: 100%; background: #e74c3c; color: white; border: none; padding: 15px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        .btn-submit:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-back { display: inline-block; color: #aaa; text-decoration: none; margin-bottom: 20px; transition: 0.2s; }
        .btn-back:hover { color: #fff; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center; font-weight: bold; }
        .success { background: #27ae60; color: white; }
        .error { background: #c0392b; color: white; }

        @media(max-width: 768px) { .map-modal-body { flex-direction: column; } .map-left { flex: 1; min-height: 300px; } .map-right { flex: 1; } }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="retur-container">
    <a href="profile.php" class="btn-back">&larr; Înapoi la contul meu</a>
    
    <h1>Formular de Retur</h1>
    <div class="order-info">
        Selectează produsele pe care dorești să le returnezi din Comanda <strong>#<?php echo $orderData['id']; ?></strong>.
    </div>

    <?php if($message): ?>
        <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($messageType !== 'success'): ?>
        <form method="POST" action="" id="returForm">
            
            <h3>1. Ce produse returnezi?</h3>
            <div class="product-list">
                <?php while($item = $resultItems->fetch_assoc()): 
                    $imgSrc = (!empty($item['imagine_url'])) ? ((strpos($item['imagine_url'], 'images') === 0) ? $item['imagine_url'] : "images/" . $item['imagine_url']) : "https://placehold.co/100?text=Poza";
                ?>
                    <label class="product-item">
                        <input type="checkbox" name="produse_retur[]" value="<?php echo $item['product_id']; ?>">
                        <img src="<?php echo $imgSrc; ?>" alt="Produs">
                        <div class="product-details">
                            <h4><?php echo htmlspecialchars($item['nume']); ?></h4>
                            <p><?php echo $item['cantitate']; ?> buc x <?php echo number_format($item['pret_unitar'], 2, ',', '.'); ?> RON</p>
                        </div>
                    </label>
                <?php endwhile; ?>
            </div>

            <div class="form-group">
                <label>De ce dorești să returnezi produsele?</label>
                <select name="motiv" required>
                    <option value="">-- Alege motivul --</option>
                    <option value="Produs defect / Neconform">Produs defect / Neconform</option>
                    <option value="M-am răzgândit / Nu mai am nevoie">M-am răzgândit / Nu mai am nevoie</option>
                    <option value="Incompatibilitate cu sistemul meu">Incompatibilitate cu sistemul meu</option>
                    <option value="Ambalaj deteriorat la livrare">Ambalaj deteriorat la livrare</option>
                    <option value="Alt motiv">Alt motiv</option>
                </select>
            </div>

            <h3>2. De unde ridicăm coletul?</h3>
            <div class="options-grid">
                <label class="radio-label">
                    <input type="radio" name="metoda_retur" value="curier" checked onclick="togglePickupMethod()">
                    <div>
                        <div style="font-weight:bold; color: #fff;">Curier (Acasă)</div>
                        <div style="font-size:12px; color:#aaa;">Preluare de la ușă</div>
                    </div>
                </label>
                <label class="radio-label">
                    <input type="radio" name="metoda_retur" value="easybox" onclick="togglePickupMethod()">
                    <div>
                        <div style="font-weight:bold; color: #fff;">Sameday Easybox</div>
                        <div style="font-size:12px; color:#aaa;">Lasă-l la locker</div>
                    </div>
                </label>
            </div>

            <div class="form-group" id="curierDetails">
                <label>Adresa de Preluare</label>
                <textarea name="adresa_curier" rows="3" placeholder="Oraș, Stradă, Număr, Bloc..." required><?php echo htmlspecialchars($adresa_user); ?></textarea>
            </div>

            <div class="form-group" id="easyboxDetails">
                <button type="button" class="btn-map" onclick="openEasyboxMap()">📍 Deschide Harta Easybox</button>
                <label>Locker Selectat pentru retur:</label>
                <input type="text" id="easyboxInput" name="adresa_easybox" placeholder="Nu ai selectat niciun Easybox..." readonly style="background: #1a1a1a; cursor: not-allowed;">
            </div>

            <button type="submit" name="submit_retur" class="btn-submit">CONFIRMĂ ȘI TRIMITE CEREREA</button>
        </form>
    <?php endif; ?>
</div>

<div id="easyboxMapModal" class="map-modal-overlay">
    <div class="map-modal-content">
        <div class="map-modal-header">
            <h3>Alege Easybox-ul unde vei lăsa coletul</h3>
            <span class="close-map-btn" onclick="closeEasyboxMap()">&times;</span>
        </div>
        <div class="map-modal-body">
            <div class="map-left" id="mapContainer">
                <p id="gpsLoading" style="color: #fff; font-size: 18px; z-index: 10;">Se caută locația ta GPS... ⏳</p>
                <div id="map" style="width: 100%; height: 100%; position: absolute; top:0; left:0; display:none;"></div>
            </div>
            <div class="map-right">
                <h4 style="color:#3498db; margin-top:0;">Lockere în zona ta:</h4>
                <div id="lockerListContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePickupMethod() {
        const metoda = document.querySelector('input[name="metoda_retur"]:checked').value;
        const curierDiv = document.getElementById('curierDetails');
        const easyboxDiv = document.getElementById('easyboxDetails');
        const inputCurier = curierDiv.querySelector('textarea');
        const inputEasybox = document.getElementById('easyboxInput');

        if (metoda === 'curier') {
            curierDiv.style.display = 'block';
            easyboxDiv.style.display = 'none';
            inputCurier.required = true;
            inputEasybox.required = false;
        } else {
            curierDiv.style.display = 'none';
            easyboxDiv.style.display = 'block';
            inputCurier.required = false;
            inputEasybox.required = true;
        }
    }
    togglePickupMethod();

    let map = null;
    let markers = [];

    function openEasyboxMap() {
        document.getElementById('easyboxMapModal').style.display = 'flex';
        const loadingText = document.getElementById('gpsLoading');
        loadingText.textContent = 'Se caută locația ta GPS... ⏳';
        loadingText.style.display = 'block';
        document.getElementById('map').style.display = 'none';
        document.getElementById('lockerListContainer').innerHTML = '';

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => { loadLeafletMap(position.coords.latitude, position.coords.longitude, true); },
                (error) => { loadLeafletMap(44.4268, 26.1025, false); }
            );
        } else {
            loadLeafletMap(44.4268, 26.1025, false);
        }
    }

    async function loadLeafletMap(lat, lng, hasGPS) {
        let orasCurent = "București"; 
        
        if (hasGPS) {
            document.getElementById('gpsLoading').textContent = "Se identifică orașul tău... 🌍";
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10&addressdetails=1`);
                const data = await response.json();
                if (data && data.address) {
                    orasCurent = data.address.city || data.address.town || data.address.municipality || data.address.county || "Oraș necunoscut";
                }
            } catch (error) { console.error("Eroare la identificarea orașului:", error); }
        }

        document.getElementById('gpsLoading').style.display = 'none';
        document.getElementById('map').style.display = 'block';

        if (!map) {
            map = L.map('map').setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        } else { map.setView([lat, lng], 14); }

        setTimeout(() => { map.invalidateSize(); }, 200);

        markers.forEach(m => map.removeLayer(m));
        markers = [];

        let userMarker = L.marker([lat, lng]).addTo(map).bindPopup("<b style='color:#27ae60;'>📍 Locația Ta</b>").openPopup();
        markers.push(userMarker);

        let lockere = [];
        if (hasGPS) {
            lockere = [
                { nume: "Easybox Supermarket", adresa: `${orasCurent}, Strada Aproape, Nr. 12`, lat: lat + 0.002, lng: lng + 0.003 },
                { nume: "Easybox Benzinărie", adresa: `${orasCurent}, Bulevardul Principal, Nr. 45`, lat: lat - 0.003, lng: lng - 0.002 },
                { nume: "Easybox Mall", adresa: `${orasCurent}, Strada Comercială, Nr. 1`, lat: lat + 0.001, lng: lng - 0.004 }
            ];
        } else {
            lockere = [
                { nume: "Easybox Centru Vechi", adresa: "București, Piața Unirii, Nr. 1", lat: 44.4278, lng: 26.1035 },
                { nume: "Easybox Universitate", adresa: "București, Bulevardul Elisabeta", lat: 44.4355, lng: 26.1025 },
                { nume: "Easybox Unirea", adresa: "București, Magazin Unirea, Parter", lat: 44.4285, lng: 26.1055 }
            ];
            document.getElementById('lockerListContainer').innerHTML += `<p style="color:#e74c3c; font-size:12px; margin-bottom:15px;">*Locația ta nu a putut fi accesată. Afișăm lockere de test.</p>`;
        }

        let listContainer = document.getElementById('lockerListContainer');
        
        lockere.forEach((l) => {
            listContainer.innerHTML += `
                <div class="locker-item" onclick="selectLocker('${l.nume}', '${l.adresa}')">
                    <div class="locker-name">${l.nume}</div>
                    <div class="locker-address">${l.adresa}</div>
                </div>
            `;

            let marker = L.marker([l.lat, l.lng]).addTo(map);
            marker.bindPopup(`
                <b style="font-size:14px;">${l.nume}</b><br>
                <span style="color:#aaa;">${l.adresa}</span><br>
                <button type="button" class="btn-select-map" onclick="selectLocker('${l.nume}', '${l.adresa}')">Alege Easybox</button>
            `);
            markers.push(marker);
        });
    }

    function selectLocker(nume, adresa) {
        document.getElementById('easyboxInput').value = nume + " - " + adresa;
        closeEasyboxMap();
    }

    function closeEasyboxMap() { document.getElementById('easyboxMapModal').style.display = 'none'; }
</script>

<?php 
require 'cart_modal.php'; 
require 'footer.php'; 
?>

</body>
</html>