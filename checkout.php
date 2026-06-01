<?php
require 'config.php';

// 1. SECURITATE: Verificăm dacă e logat
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. SECURITATE: Verificăm dacă are produse în coș
if (empty($_SESSION['cart'])) {
    header('Location: home.php');
    exit;
}


// 4. EXTRAGERE DATE UTILIZATOR
$uid = $_SESSION['user_id'];
$query = $mysqli->query("SELECT * FROM users WHERE id = $uid");
$userData = $query->fetch_assoc();

// 5. CALCUL TOTAL DEFAULT
$totalEstimativ = 0;
$discount = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $res = $mysqli->query("SELECT id, pret FROM produse WHERE id IN ($ids)");
    while($row = $res->fetch_assoc()) {
        $totalEstimativ += $row['pret'] * $_SESSION['cart'][$row['id']];
    }
}

if (isset($_SESSION['promo_code']) && $_SESSION['promo_code'] === 'PCSHOP10') {
    $discount = $totalEstimativ * 0.10;
}

$subtotalDupaReducere = $totalEstimativ - $discount;

$costLivrare = ($subtotalDupaReducere > 0 && $subtotalDupaReducere < 500) ? 25 : 0;
$taxaRamburs = 10;
$totalFinal = $subtotalDupaReducere + $costLivrare + $taxaRamburs;
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizare Comandă - PC Shop</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .checkout-container { max-width: 750px; margin: 0 auto; background: #1e1e1e; padding: 40px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: #9b59b6; text-align: center; margin-bottom: 30px; margin-top: 0; }
        h3 { border-bottom: 1px solid #444; padding-bottom: 10px; margin-top: 35px; color: #fff; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #aaa; font-size: 14px; }
        .req-star { color: #e74c3c; font-weight: bold; margin-left: 3px; }
        
        input, textarea, select { width: 100%; padding: 12px; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input:focus, textarea:focus { border-color: #9b59b6; outline: none; }

        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px; }
        .radio-label { display: flex; align-items: center; cursor: pointer; background: #252525; padding: 15px; border-radius: 8px; border: 1px solid #444; transition: 0.2s; }
        .radio-label:hover { border-color: #9b59b6; background: #2a2a2a; }
        .radio-label input { width: auto; margin-right: 15px; transform: scale(1.2); accent-color: #9b59b6; }

        #easyboxDetails, #cardDetailsSection { background-color: #252525; padding: 20px; border-radius: 8px; border: 1px solid #444; margin-top: 15px; display: none; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: 0; } }

        /* BUTON HARTĂ EASYBOX */
        .btn-map { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-map:hover { background: #2980b9; transform: translateY(-2px); }

        /* MODAL HARTĂ EASYBOX */
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

        /* Sumar Checkout */
        .summary-box { background: #252525; padding: 25px; border-radius: 8px; border: 1px solid #333; margin: 30px 0; }
        .summary-row { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 10px; color: #aaa; }
        .summary-discount { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 10px; color: #e74c3c; font-weight: bold; }
        .summary-shipping { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 10px; color: #aaa; }
        .summary-fee { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 15px; color: #f39c12; font-weight: bold; transition: 0.3s;}
        .summary-final { display: flex; justify-content: space-between; font-size: 20px; border-top: 1px solid #444; padding-top: 15px; margin-top: 10px; font-weight: bold; color: #fff; }
        .total-price { color: #27ae60; font-size: 26px; }

        .btn-submit { width: 100%; padding: 16px; background: #9b59b6; color: white; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(155, 89, 182, 0.4); }
        .btn-submit:hover { background: #8e44ad; transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #888; text-decoration: none; transition: 0.3s; }
        .back-link:hover { color: #9b59b6; }

        @media(max-width: 768px) { .map-modal-body { flex-direction: column; } .map-left { flex: 1; min-height: 300px; } .map-right { flex: 1; } }
    </style>
</head>
<body>

<div class="checkout-container">
    <h1>Finalizare Comandă</h1>
    
    <form action="place_order.php" method="POST" id="checkoutForm">
        
        <h3>1. Date de Contact</h3>
        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Nume Destinatar <span class="req-star">*</span></label>
                <input type="text" name="nume" value="<?php echo htmlspecialchars($userData['nume'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label>Adresă de Email <span class="req-star">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" placeholder="nume@exemplu.ro" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Număr de Telefon</label>
            <input type="tel" name="telefon" value="<?php echo htmlspecialchars($userData['telefon'] ?? ''); ?>" placeholder="07xx xxx xxx">
            <p style="font-size:12px; color:#aaa; margin-top: 5px; margin-bottom: 0;">(Opțional, dar recomandat pentru curier)</p>
        </div>
        
        <h3>2. Metoda de Livrare</h3>
        <div class="options-grid">
            <label class="radio-label">
                <input type="radio" name="livrare" value="curier" checked onclick="updateTotals()">
                <div>
                    <div style="font-weight:bold; color: #fff;">Curier Rapid</div>
                    <div style="font-size:12px; color:#aaa;">Acasă (25 RON)</div>
                </div>
            </label>
            <label class="radio-label">
                <input type="radio" name="livrare" value="easybox" onclick="updateTotals()">
                <div>
                    <div style="font-weight:bold; color: #fff;">Sameday Easybox</div>
                    <div style="font-size:12px; color:#aaa;">Locker (15 RON)</div>
                </div>
            </label>
            <label class="radio-label">
                <input type="radio" name="livrare" value="magazin" onclick="updateTotals()">
                <div>
                    <div style="font-weight:bold; color: #fff;">Ridicare Magazin</div>
                    <div style="font-size:12px; color:#aaa;">Gratuit</div>
                </div>
            </label>
        </div>

        <div class="form-group" id="adresaStandard" style="margin-top: 15px;">
            <label>Adresa de Livrare / Domiciliu <span class="req-star" id="reqAdresa">*</span></label>
            <textarea name="adresa" rows="3" placeholder="Oraș, Stradă, Număr, Bloc..." required><?php echo htmlspecialchars($userData['adresa'] ?? ''); ?></textarea>
        </div>

        <div id="easyboxDetails">
            <button type="button" class="btn-map" onclick="openEasyboxMap()">📍 Deschide Harta Easybox</button>
            <label>Locker Selectat: <span class="req-star">*</span></label>
            <input type="text" id="easyboxInput" name="adresa_easybox" placeholder="Nu ai selectat niciun Easybox..." readonly style="background: #1a1a1a; cursor: not-allowed;">
        </div>

        <h3>3. Metoda de Plată</h3>
        <div class="options-grid">
            <label class="radio-label">
                <input type="radio" name="plata" value="ramburs" checked onclick="updateTotals()">
                <div>
                    <div style="font-weight:bold; color: #fff;">Plata la Livrare/Ridicare</div>
                    <div style="font-size:12px; color:#aaa;">(+10,00 RON)</div>
                </div>
            </label>
            <label class="radio-label">
                <input type="radio" name="plata" value="card" onclick="updateTotals()">
                <div>
                    <div style="font-weight:bold; color: #fff;">Card Online</div>
                    <div style="font-size:12px; color:#aaa;">Fără taxe ascunse</div>
                </div>
            </label>
        </div>

        <div id="cardDetailsSection">
            <div class="form-group">
                <label>Număr Card <span class="req-star">*</span></label>
                <input type="text" name="card_number" value="<?php echo htmlspecialchars($userData['card_number'] ?? ''); ?>" placeholder="0000 0000 0000 0000">
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Titular Card <span class="req-star">*</span></label>
                    <input type="text" name="card_holder" value="<?php echo htmlspecialchars($userData['card_holder'] ?? ''); ?>" placeholder="NUME PRENUME">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Expirare (MM/YY) <span class="req-star">*</span></label>
                    <input type="text" name="card_expiry" value="<?php echo htmlspecialchars($userData['card_expiry'] ?? ''); ?>" placeholder="MM/YY">
                </div>
                <div class="form-group" style="width: 80px;">
                    <label>CVV <span class="req-star">*</span></label>
                    <input type="text" name="card_cvv" placeholder="123">
                </div>
            </div>
            <p style="font-size:12px; color:#aaa; margin:0;">* Tranzacție 100% securizată.</p>
        </div>

        <div class="summary-box">
            <div class="summary-row">
                <span>Subtotal Produse:</span>
                <span><?php echo number_format($totalEstimativ, 2, ',', '.'); ?> RON</span>
            </div>
            
            <?php if ($discount > 0): ?>
                <div class="summary-discount">
                    <span>Reducere (Cod: PCSHOP10):</span>
                    <span>- <?php echo number_format($discount, 2, ',', '.'); ?> RON</span>
                </div>
            <?php endif; ?>

            <div class="summary-row">
                <span>Cost Livrare:</span>
                <span id="shippingCostText">
                    <?php if ($costLivrare == 0): ?>
                        <span style="color: #27ae60; font-weight: bold;">Gratuit</span>
                    <?php else: ?>
                        + <?php echo number_format($costLivrare, 2, ',', '.'); ?> RON
                    <?php endif; ?>
                </span>
            </div>

            <div class="summary-fee" id="rambursRow">
                <span>Taxă procesare plată:</span>
                <span id="rambursCostText">+ <?php echo number_format($taxaRamburs, 2, ',', '.'); ?> RON</span>
            </div>

            <div class="summary-final">
                <span>Total de plată:</span>
                <span class="total-price" id="displayTotal"><?php echo number_format($totalFinal, 2, ',', '.'); ?> RON</span>
            </div>
        </div>

        <input type="hidden" name="total_plata" id="hiddenTotalPlata" value="<?php echo number_format($totalFinal, 2, '.', ''); ?>">
        <input type="hidden" name="cost_livrare" id="hiddenCostLivrare" value="<?php echo $costLivrare; ?>">
        <input type="hidden" name="taxa_ramburs" id="hiddenTaxaRamburs" value="<?php echo $taxaRamburs; ?>">

        <button type="submit" class="btn-submit">CONFIRMĂ ȘI PLASEAZĂ COMANDA</button>
    </form>
    
    <a href="home.php" class="back-link">⟵ Înapoi la cumpărături</a>
</div>

<div id="easyboxMapModal" class="map-modal-overlay">
    <div class="map-modal-content">
        <div class="map-modal-header">
            <h3>Alege cel mai apropiat Easybox</h3>
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
    // --- 1. LOGICA DE PREȚURI ---
    const subtotalCorectat = <?php echo number_format($subtotalDupaReducere, 2, '.', ''); ?>;
    const pragGratuit = 500;

    function formatNumberRO(num) { return new Intl.NumberFormat('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num); }

    function updateTotals() {
        const livrare = document.querySelector('input[name="livrare"]:checked').value;
        const plata = document.querySelector('input[name="plata"]:checked').value;
        let costLivrareCalculat = 0, taxaRambursCalculata = 0;

        const easyboxDiv = document.getElementById('easyboxDetails');
        const easyboxInput = document.getElementById('easyboxInput');
        const adresaStandard = document.getElementById('adresaStandard');
        const starAdresa = document.getElementById('reqAdresa');
        
        if (livrare === 'curier') {
            costLivrareCalculat = (subtotalCorectat >= pragGratuit) ? 0 : 25;
            easyboxDiv.style.display = 'none'; easyboxInput.required = false;
            adresaStandard.style.display = 'block'; 
            adresaStandard.querySelector('textarea').required = true;
            starAdresa.style.display = 'inline';
        } else if (livrare === 'easybox') {
            costLivrareCalculat = (subtotalCorectat >= pragGratuit) ? 0 : 15;
            easyboxDiv.style.display = 'block'; easyboxInput.required = true;
            adresaStandard.style.display = 'none'; 
            adresaStandard.querySelector('textarea').required = false;
        } else if (livrare === 'magazin') {
            costLivrareCalculat = 0;
            easyboxDiv.style.display = 'none'; easyboxInput.required = false;
            adresaStandard.style.display = 'none'; 
            adresaStandard.querySelector('textarea').required = false;
        }

        const cardSection = document.getElementById('cardDetailsSection');
        const cardInputs = cardSection.querySelectorAll('input');
        const textTaxaPlata = document.getElementById('textTaxaRamburs');

        if (plata === 'card') {
            taxaRambursCalculata = 0; cardSection.style.display = 'block'; cardInputs.forEach(i => i.required = true); textTaxaPlata.style.display = 'none';
        } else {
            cardSection.style.display = 'none'; cardInputs.forEach(i => i.required = false); textTaxaPlata.style.display = 'block';
            if (livrare === 'magazin') { taxaRambursCalculata = 0; textTaxaPlata.textContent = "(Fără taxe extra în magazin)"; } 
            else { taxaRambursCalculata = 10; textTaxaPlata.textContent = "(+10,00 RON)"; }
        }

        const shippingRowSpan = document.getElementById('shippingCostText');
        if (costLivrareCalculat === 0) shippingRowSpan.innerHTML = '<span style="color: #27ae60; font-weight: bold;">Gratuit</span>';
        else shippingRowSpan.textContent = '+ ' + formatNumberRO(costLivrareCalculat) + ' RON';

        const rambursRow = document.getElementById('rambursRow');
        if (taxaRambursCalculata > 0) { rambursRow.style.display = 'flex'; document.getElementById('rambursCostText').textContent = '+ ' + formatNumberRO(taxaRambursCalculata) + ' RON'; } 
        else rambursRow.style.display = 'none';

        let total = subtotalCorectat + costLivrareCalculat + taxaRambursCalculata;
        document.getElementById('displayTotal').textContent = formatNumberRO(total) + ' RON';
        document.getElementById('hiddenTotalPlata').value = total.toFixed(2);
        document.getElementById('hiddenCostLivrare').value = costLivrareCalculat;
        document.getElementById('hiddenTaxaRamburs').value = taxaRambursCalculata;
    }

    // --- 2. LOGICA HĂRȚII INTERACTIVE (LEAFLET + REVERSE GEOCODING) ---
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
                <button class="btn-select-map" onclick="selectLocker('${l.nume}', '${l.adresa}')">Alege Easybox</button>
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

</body>
</html>