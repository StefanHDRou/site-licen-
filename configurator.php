<?php
require 'config.php';

// --- 1. INIȚIALIZAREA SESIUNII CONFIGURATORULUI ---
if (!isset($_SESSION['configurator'])) {
    $_SESSION['configurator'] = [
        'cpu' => null, 'cooler' => null, 'gpu' => null, 'ram' => null,
        'stocare' => [], 'mobo' => null, 'psu' => null, 'case' => null, 'fans' => []
    ];
}

// Resetare configurator
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    unset($_SESSION['configurator']);
    header('Location: configurator.php'); exit;
}

// --- 2. LOGICA PAȘILOR ---
$steps = [
    1 => ['id' => 'cpu', 'nume' => 'Procesor', 'cat_id' => 1],
    2 => ['id' => 'cooler', 'nume' => 'Cooler CPU', 'cat_id' => 8],
    3 => ['id' => 'gpu', 'nume' => 'Placă Video', 'cat_id' => 2],
    4 => ['id' => 'ram', 'nume' => 'Memorie RAM', 'cat_id' => 3],
    5 => ['id' => 'stocare', 'nume' => 'Stocare', 'cat_id' => 5],
    6 => ['id' => 'mobo', 'nume' => 'Placă de bază', 'cat_id' => 4],
    7 => ['id' => 'psu', 'nume' => 'Sursă', 'cat_id' => 6],
    8 => ['id' => 'case', 'nume' => 'Carcasă', 'cat_id' => 7],
    9 => ['id' => 'fans', 'nume' => 'Ventilatoare', 'cat_id' => 9],
    10 => ['id' => 'summary', 'nume' => 'Sumar', 'cat_id' => 0] 
];

$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if (!array_key_exists($current_step, $steps)) $current_step = 1;

$step_info = $steps[$current_step];
$cat_id_curent = $step_info['cat_id'];

// --- 3. SALVAREA ALEGERII SAU SĂRIREA PESTE PAS ---
if (isset($_GET['skip']) && $_GET['skip'] == '1') {
    $next_step = $current_step + 1;
    if ($next_step <= count($steps)) { header("Location: configurator.php?step=$next_step"); exit; }
}

if (isset($_POST['alege_produs'])) {
    $id_ales = (int)$_POST['id_produs'];
    $step_key = $step_info['id'];
    
    if ($step_key === 'stocare' || $step_key === 'fans') {
        $_SESSION['configurator'][$step_key][] = $id_ales;
    } else {
        $_SESSION['configurator'][$step_key] = $id_ales;
    }
    
    $next_step = $current_step + 1;
    if ($next_step <= count($steps)) {
        header("Location: configurator.php?step=$next_step"); exit;
    }
}

// --- 4. MOTORUL DE COMPATIBILITATE ---
$compat_filters = "";
$socket_cpu = "";

if ($current_step < 10) { 
    if (!empty($_SESSION['configurator']['cpu'])) {
        $cpu_id = (int)$_SESSION['configurator']['cpu'];
        $q_socket = $mysqli->query("SELECT valoare_specificatie FROM specificatii_produse WHERE produs_id = $cpu_id AND nume_specificatie = 'Socket' LIMIT 1");
        if ($q_socket->num_rows > 0) { $socket_cpu = $q_socket->fetch_assoc()['valoare_specificatie']; }
    }

    if ($step_info['id'] === 'cooler' && $socket_cpu !== "") {
        $compat_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE nume_specificatie LIKE '%Socket%' AND valoare_specificatie LIKE '%$socket_cpu%')";
    }

    if ($step_info['id'] === 'mobo' && $socket_cpu !== "") {
        $compat_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE nume_specificatie = 'Socket' AND valoare_specificatie = '$socket_cpu')";
        if (!empty($_SESSION['configurator']['ram'])) {
            $ram_id = (int)$_SESSION['configurator']['ram'];
            $q_ram = $mysqli->query("SELECT valoare_specificatie FROM specificatii_produse WHERE produs_id = $ram_id AND nume_specificatie = 'Tip memorie' LIMIT 1");
            if ($q_ram->num_rows > 0) {
                $tip_ram = $q_ram->fetch_assoc()['valoare_specificatie'];
                $compat_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE nume_specificatie = 'Tip memorie' AND valoare_specificatie = '$tip_ram')";
            }
        }
    }

    if ($step_info['id'] === 'psu') {
        $total_tdp = 150; 
        if (!empty($_SESSION['configurator']['cpu'])) {
            $cpu_id = (int)$_SESSION['configurator']['cpu'];
            $q_tdp_cpu = $mysqli->query("SELECT valoare_specificatie FROM specificatii_produse WHERE produs_id = $cpu_id AND nume_specificatie LIKE '%TDP%' LIMIT 1");
            if ($q_tdp_cpu->num_rows > 0) { $total_tdp += (int)$q_tdp_cpu->fetch_assoc()['valoare_specificatie']; }
        }
        if (!empty($_SESSION['configurator']['gpu'])) {
            $gpu_id = (int)$_SESSION['configurator']['gpu'];
            $q_tdp_gpu = $mysqli->query("SELECT valoare_specificatie FROM specificatii_produse WHERE produs_id = $gpu_id AND (nume_specificatie LIKE '%TDP%' OR nume_specificatie LIKE '%Consum%') LIMIT 1");
            if ($q_tdp_gpu->num_rows > 0) { $total_tdp += (int)$q_tdp_gpu->fetch_assoc()['valoare_specificatie']; }
        }
        $compat_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE nume_specificatie LIKE '%Putere%' AND CAST(valoare_specificatie AS UNSIGNED) >= $total_tdp)";
    }

    if ($step_info['id'] === 'case') {
        if (!empty($_SESSION['configurator']['mobo'])) {
            $mobo_id = (int)$_SESSION['configurator']['mobo'];
            $q_format = $mysqli->query("SELECT valoare_specificatie FROM specificatii_produse WHERE produs_id = $mobo_id AND nume_specificatie LIKE '%Format%' LIMIT 1");
            if ($q_format->num_rows > 0) {
                $format_mobo = $mysqli->real_escape_string($q_format->fetch_assoc()['valoare_specificatie']);
                $compat_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE (nume_specificatie LIKE '%Format%' OR nume_specificatie LIKE '%Placi de baza compatibile%') AND valoare_specificatie LIKE '%$format_mobo%')";
            }
        }
    }

    // --- FILTRE UTILIZATOR ---
    $user_filters = "";
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
    if ($min_price > 0) $user_filters .= " AND p.pret >= $min_price";
    if ($max_price > 0) $user_filters .= " AND p.pret <= $max_price";

    if (isset($_GET['filter_spec']) && is_array($_GET['filter_spec'])) {
        foreach ($_GET['filter_spec'] as $spec_nume => $valori) {
            $nume_safe = $mysqli->real_escape_string($spec_nume);
            $valori_safe = implode("','", array_map([$mysqli, 'real_escape_string'], $valori));
            $user_filters .= " AND p.id IN (SELECT produs_id FROM specificatii_produse WHERE nume_specificatie = '$nume_safe' AND valoare_specificatie IN ('$valori_safe'))";
        }
    }

    $sql_produse = "SELECT p.* FROM produse p WHERE p.categorie_id = $cat_id_curent AND p.stoc > 0 $compat_filters $user_filters";
    $result_produse = $mysqli->query($sql_produse);

    $sql_specs = "SELECT DISTINCT sp.nume_specificatie, sp.valoare_specificatie 
                  FROM specificatii_produse sp 
                  JOIN produse p ON p.id = sp.produs_id 
                  WHERE p.categorie_id = $cat_id_curent AND p.stoc > 0 $compat_filters
                  ORDER BY sp.nume_specificatie, sp.valoare_specificatie";
    $result_specs = $mysqli->query($sql_specs);

    $filtre_disponibile = [];
    while($row = $result_specs->fetch_assoc()) {
        if ($row['valoare_specificatie'] === '__SECTIUNE__') continue; 
        $filtre_disponibile[$row['nume_specificatie']][] = $row['valoare_specificatie'];
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurator PC - PC Shop</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #121212; color: #e0e0e0; margin: 0; }
        .config-container { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .page-header h1 { color: #9b59b6; margin: 0; }
        .btn-reset { background: #c0392b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .btn-reset:hover { background: #e74c3c; }

        .stepper { display: flex; overflow-x: auto; gap: 10px; padding-bottom: 15px; margin-bottom: 30px; }
        .step { flex: 1; min-width: 120px; text-align: center; padding: 15px 10px; background: #1e1e1e; border: 1px solid #333; border-radius: 8px; color: #888; text-decoration: none; position: relative; transition: 0.3s; }
        .step.active { background: #2a1b38; border-color: #9b59b6; color: #fff; box-shadow: 0 0 15px rgba(155, 89, 182, 0.3); font-weight: bold; }
        .step.completed { border-color: #27ae60; color: #27ae60; }
        .step-number { font-size: 12px; display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }

        /* SCROLLBAR BARA DE PASI */
        .stepper::-webkit-scrollbar { height: 8px; }
        .stepper::-webkit-scrollbar-track { background: #1a1a1a; border-radius: 4px; }
        .stepper::-webkit-scrollbar-thumb { background: #444; border-radius: 4px; }
        .stepper::-webkit-scrollbar-thumb:hover { background: #8e44ad; }

        .config-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        
        .filters { background: #1e1e1e; padding: 25px; border-radius: 12px; border: 1px solid #333; height: fit-content; max-height: 85vh; overflow-y: auto; position: sticky; top: 100px; }
        .filters::-webkit-scrollbar { width: 6px; } .filters::-webkit-scrollbar-thumb { background: #8e44ad; border-radius: 4px; } .filters::-webkit-scrollbar-track { background: #1a1a1a; }
        .filters h3 { margin-top: 0; color: #fff; border-bottom: 2px solid #8e44ad; padding-bottom: 15px; margin-bottom: 20px; }
        
        .price-filter-row { display: flex; gap: 10px; margin-bottom: 25px; }
        .price-filter-row input { width: 100%; padding: 10px; background: #252525; border: 1px solid #444; color: #fff; border-radius: 6px; outline: none; transition: 0.3s; }
        .price-filter-row input:focus { border-color: #9b59b6; }
        
        .filter-group { margin-bottom: 20px; max-height: 250px; overflow-y: auto; padding-right: 5px; }
        .filter-group::-webkit-scrollbar { width: 4px; } .filter-group::-webkit-scrollbar-thumb { background: #555; border-radius: 4px; }
        .filter-title { color: #9b59b6; display: block; margin-bottom: 10px; border-top: 1px dashed #333; padding-top: 15px; font-weight: bold; letter-spacing: 0.5px; }
        .filter-checkbox { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; color: #ccc; cursor: pointer; font-size: 13px; line-height: 1.4; transition: 0.2s; }
        .filter-checkbox:hover { color: #fff; } .filter-checkbox input { margin-top: 3px; accent-color: #9b59b6; cursor: pointer; transform: scale(1.1); }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 15px; text-align: center; display: flex; flex-direction: column; transition: 0.3s; position: relative; }
        .product-card:hover { border-color: #9b59b6; transform: translateY(-5px); box-shadow: 0 5px 15px rgba(155,89,182,0.2); }
        
        /* STILURI REDUCERI */
        .badge-container { position: absolute; top: 10px; left: 10px; display: flex; z-index: 10; pointer-events: none; }
        .badge-sale { background-color: #e74c3c; color: white; padding: 5px 10px; font-size: 11px; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 10px rgba(231, 76, 60, 0.4); text-transform: uppercase; }
        .price-box { margin-bottom: 15px; display: flex; flex-direction: column; align-items: center; }
        .price-old { color: #888; text-decoration: line-through; font-size: 14px; margin-bottom: 2px; }
        .price-new { color: #e74c3c; font-weight: bold; font-size: 20px; }
        .price-normal { color: #9b59b6; font-weight: bold; font-size: 20px; margin-bottom: 15px;}

        .product-img { width: 100%; height: 150px; object-fit: contain; margin-bottom: 15px; background: #fff; border-radius: 6px; cursor: pointer; }
        .product-title { font-size: 16px; color: #fff; margin: 0 0 10px 0; height: 40px; overflow: hidden; cursor: pointer; transition: 0.3s; }
        .product-title:hover { color: #9b59b6; }
        .btn-select { background: #27ae60; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: auto; }
        .btn-select:hover { background: #2ecc71; }

        .btn-skip { background: transparent; border: 1px solid #777; color: #ccc; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-skip:hover { background: #333; border-color: #aaa; color: #fff; }

        /* MODAL */
        .info-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 4000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .info-modal-content { background: #121212; width: 95%; max-width: 1200px; height: 85vh; border-radius: 12px; border: 2px solid #9b59b6; box-shadow: 0 10px 40px rgba(155, 89, 182, 0.4); display: flex; flex-direction: column; }
        .modal-top-bar { height: 40px; background: #1a1a1a; border-radius: 10px 10px 0 0; display: flex; justify-content: flex-end; align-items: center; padding: 0 15px; border-bottom: 1px solid #333; }
        .close-info-modal { color: #aaa; font-size: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; line-height: 1; }
        .close-info-modal:hover { color: #e74c3c; transform: scale(1.1); }
        #productIframe { width: 100%; flex: 1; border: none; border-radius: 0 0 10px 10px; background: #121212; }

        /* STILURI PENTRU PASUL 10 (SUMAR BON FISCAL) */
        .summary-container { background: #1e1e1e; border: 2px solid #9b59b6; border-radius: 12px; padding: 40px; max-width: 800px; margin: 0 auto; box-shadow: 0 10px 30px rgba(155,89,182,0.15); }
        .summary-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed #444; }
        .summary-item img { width: 50px; height: 50px; object-fit: contain; background: #fff; border-radius: 6px; margin-right: 15px; }
        .summary-item-details { flex: 1; display: flex; align-items: center; }
        .summary-item-title { font-weight: bold; font-size: 16px; color: #fff; }
        .summary-item-cat { color: #8e44ad; font-size: 12px; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 3px; }
        .summary-item-price { font-size: 18px; color: #ccc; font-weight: bold; }
        .summary-total { display: flex; justify-content: space-between; align-items: center; padding-top: 30px; margin-top: 15px; border-top: 2px solid #8e44ad; }
        .summary-total-label { font-size: 24px; color: #fff; text-transform: uppercase; letter-spacing: 2px; }
        .summary-total-price { font-size: 36px; color: #9b59b6; font-weight: bold; }
        .btn-add-all { background: #27ae60; color: white; border: none; padding: 15px 40px; border-radius: 8px; font-size: 20px; font-weight: bold; cursor: pointer; transition: 0.3s; width: 100%; margin-top: 30px; box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4); }
        .btn-add-all:hover { background: #2ecc71; transform: translateY(-3px); }

        @media (max-width: 900px) { .config-layout { grid-template-columns: 1fr; } .stepper { flex-wrap: nowrap; } .summary-item { flex-direction: column; text-align: center; gap: 10px; } .summary-item-details { flex-direction: column; } .summary-item img { margin: 0 0 10px 0; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="config-container">
        
        <div class="page-header">
            <h1>Configurator PC Profesional</h1>
            <a href="configurator.php?reset=1" class="btn-reset" onclick="return confirm('Ești sigur? Vei pierde tot progresul de până acum!')">✖ Resetează Configurația</a>
        </div>

        <div class="stepper">
            <?php foreach ($steps as $num => $info): 
                $statusClass = '';
                if ($num == $current_step) $statusClass = 'active';
                elseif (!empty($_SESSION['configurator'][$info['id']])) $statusClass = 'completed';
            ?>
                <a href="configurator.php?step=<?php echo $num; ?>" class="step <?php echo $statusClass; ?>">
                    <span class="step-number">Pasul <?php echo $num; ?></span>
                    <?php echo $info['nume']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($current_step == 10): ?>
            <?php
            $total_sistem = 0;
            $selected_ids = [];
            $componente_alese = [];
            $lista_finala_js = [];

            foreach ($_SESSION['configurator'] as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $v) $selected_ids[] = (int)$v;
                } elseif ($val !== null) {
                    $selected_ids[] = (int)$val;
                }
            }

            if (!empty($selected_ids)) {
                $ids_str = implode(',', $selected_ids);
                $res_sum = $mysqli->query("SELECT id, nume, pret, imagine_url FROM produse WHERE id IN ($ids_str)");
                while ($row = $res_sum->fetch_assoc()) {
                    $componente_alese[$row['id']] = $row;
                }
            }
            ?>
            
            <div class="summary-container">
                <h2 style="text-align: center; color: #fff; margin-top: 0; margin-bottom: 30px;">Bon Fiscal Digital</h2>
                
                <?php foreach ($_SESSION['configurator'] as $key => $val): ?>
                    <?php 
                    $nume_afisare_cat = "";
                    foreach($steps as $s) { if($s['id'] == $key) $nume_afisare_cat = $s['nume']; }
                    
                    if (is_array($val)) {
                        foreach ($val as $v) {
                            if (isset($componente_alese[$v])) {
                                $p = $componente_alese[$v];
                                $total_sistem += $p['pret'];
                                $lista_finala_js[] = $p['id']; 
                                $img = !empty($p['imagine_url']) ? ((strpos($p['imagine_url'], 'images') === 0) ? $p['imagine_url'] : "images/" . $p['imagine_url']) : "https://placehold.co/100?text=Poza";
                                ?>
                                <div class="summary-item">
                                    <div class="summary-item-details">
                                        <img src="<?php echo $img; ?>">
                                        <div>
                                            <span class="summary-item-cat"><?php echo $nume_afisare_cat; ?></span>
                                            <span class="summary-item-title"><?php echo htmlspecialchars($p['nume']); ?></span>
                                        </div>
                                    </div>
                                    <div class="summary-item-price"><?php echo number_format($p['pret'], 0, ',', '.'); ?> RON</div>
                                </div>
                                <?php
                            }
                        }
                    } elseif ($val !== null) {
                        if (isset($componente_alese[$val])) {
                            $p = $componente_alese[$val];
                            $total_sistem += $p['pret'];
                            $lista_finala_js[] = $p['id'];
                            $img = !empty($p['imagine_url']) ? ((strpos($p['imagine_url'], 'images') === 0) ? $p['imagine_url'] : "images/" . $p['imagine_url']) : "https://placehold.co/100?text=Poza";
                            ?>
                            <div class="summary-item">
                                <div class="summary-item-details">
                                    <img src="<?php echo $img; ?>">
                                    <div>
                                        <span class="summary-item-cat"><?php echo $nume_afisare_cat; ?></span>
                                        <span class="summary-item-title"><?php echo htmlspecialchars($p['nume']); ?></span>
                                    </div>
                                </div>
                                <div class="summary-item-price"><?php echo number_format($p['pret'], 0, ',', '.'); ?> RON</div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                <?php endforeach; ?>

                <div class="summary-total">
                    <div class="summary-total-label">Total Sistem</div>
                    <div class="summary-total-price"><?php echo number_format($total_sistem, 0, ',', '.'); ?> RON</div>
                </div>
                
                <?php if ($total_sistem > 0): ?>
                    <button id="btn-add-sys" class="btn-add-all" onclick="addWholeSystemToCart()">🛒 Adaugă Sistemul în Coș</button>
                <?php else: ?>
                    <p style="text-align: center; color: #e74c3c;">Nu ai selectat nicio componentă!</p>
                <?php endif; ?>
            </div>

            <script>
            const arrayProduseJs = <?php echo json_encode($lista_finala_js); ?>;
            async function addWholeSystemToCart() {
                const btn = document.getElementById('btn-add-sys');
                btn.innerHTML = 'Se procesează piesele... ⏳';
                btn.disabled = true;
                btn.style.background = '#e67e22';

                for (let id of arrayProduseJs) {
                    await fetch('cart_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'add', id: id})
                    });
                }
                btn.innerHTML = '✔ PC Adăugat cu Succes!';
                btn.style.background = '#27ae60';
                refreshCart();
                toggleCartModal();
            }
            </script>

        <?php else: ?>
            <div class="config-layout">
                <aside class="filters">
                    <h3>Filtre <?php echo $step_info['nume']; ?></h3>
                    <form action="configurator.php" method="GET" id="configFiltersForm">
                        <input type="hidden" name="step" value="<?php echo $current_step; ?>">
                        
                        <span class="filter-title" style="border-top: none; padding-top: 0;">Preț (RON)</span>
                        <div class="price-filter-row">
                            <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>" onchange="document.getElementById('configFiltersForm').submit()">
                            <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price > 0 ? $max_price : ''; ?>" onchange="document.getElementById('configFiltersForm').submit()">
                        </div>

                        <?php foreach ($filtre_disponibile as $nume_spec => $valori): ?>
                            <div class="filter-group">
                                <span class="filter-title"><?php echo htmlspecialchars($nume_spec); ?></span>
                                <?php foreach ($valori as $val): 
                                    $checked = (isset($_GET['filter_spec'][$nume_spec]) && in_array($val, $_GET['filter_spec'][$nume_spec])) ? 'checked' : '';
                                ?>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" name="filter_spec[<?php echo htmlspecialchars($nume_spec); ?>][]" value="<?php echo htmlspecialchars($val); ?>" <?php echo $checked; ?> onchange="document.getElementById('configFiltersForm').submit()">
                                        <?php echo htmlspecialchars($val); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($filtre_disponibile)): ?>
                            <p style="color:#aaa; font-size: 14px;">Nu există alte specificații pentru filtrare.</p>
                        <?php endif; ?>
                    </form>
                </aside>

                <div>
                    <h2 style="margin-top: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <span>Alege <?php echo $step_info['nume']; ?></span>
                        <?php if ($current_step > 1): ?>
                            <a href="configurator.php?step=<?php echo $current_step; ?>&skip=1" class="btn-skip">Sari peste acest pas ➔</a>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="products-grid">
                        <?php if ($result_produse->num_rows > 0): ?>
                            <?php while($p = $result_produse->fetch_assoc()): 
                                $img = !empty($p['imagine_url']) ? ((strpos($p['imagine_url'], 'images') === 0) ? $p['imagine_url'] : "images/" . $p['imagine_url']) : "https://placehold.co/200?text=Fara+Poza";
                                $id_p = $p['id'];
                                $specs_card = $mysqli->query("SELECT * FROM specificatii_produse WHERE produs_id = $id_p AND afisare_home = 1 LIMIT 4");
                                
                                // LOGICA REDUCERI
                                $pret_afisat = $p['pret'];
                                $pret_vechi = !empty($p['pret_vechi']) ? $p['pret_vechi'] : 0;
                                $is_on_sale = ($pret_vechi > $pret_afisat);
                                $procent_reducere = 0;
                                if ($is_on_sale) {
                                    $procent_reducere = round((($pret_vechi - $pret_afisat) / $pret_vechi) * 100);
                                }
                            ?>
                                <div class="product-card">
                                    
                                    <?php if($is_on_sale): ?>
                                        <div class="badge-container">
                                            <div class="badge-sale">-<?php echo $procent_reducere; ?>%</div>
                                        </div>
                                    <?php endif; ?>

                                    <img src="<?php echo $img; ?>" class="product-img" onclick="openProductModal(<?php echo $id_p; ?>)" title="Click pentru detalii complete">
                                    <h3 class="product-title" onclick="openProductModal(<?php echo $id_p; ?>)"><?php echo htmlspecialchars($p['nume']); ?></h3>
                                    
                                    <ul style="text-align: left; font-size: 13px; color: #aaa; margin: 0 0 15px 0; padding-left: 20px; flex: 1; list-style-type: square;">
                                        <?php while($sc = $specs_card->fetch_assoc()): ?>
                                            <li><strong style="color:#ccc;"><?php echo htmlspecialchars($sc['nume_specificatie']); ?>:</strong> <?php echo htmlspecialchars($sc['valoare_specificatie']); ?></li>
                                        <?php endwhile; ?>
                                    </ul>

                                    <div class="price-box">
                                        <?php if($is_on_sale): ?>
                                            <span class="price-old"><?php echo number_format($pret_vechi, 0, ',', '.'); ?> RON</span>
                                            <span class="price-new"><?php echo number_format($pret_afisat, 0, ',', '.'); ?> RON</span>
                                        <?php else: ?>
                                            <div class="price-normal"><?php echo number_format($pret_afisat, 0, ',', '.'); ?> RON</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="id_produs" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="alege_produs" class="btn-select">Adaugă Componenta</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color:#e74c3c; grid-column: 1/-1;">Nu am găsit produse disponibile sau compatibile cu celelalte piese alese de tine.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
    
    <div id="productInfoModal" class="info-modal-overlay" onclick="closeProductModal(event)">
        <div class="info-modal-content">
            <div class="modal-top-bar"><span class="close-info-modal" onclick="closeProductModalBtn()">&times;</span></div>
            <iframe id="productIframe" src=""></iframe>
        </div>
    </div>

    <script>
        function openProductModal(id) {
            document.getElementById('productIframe').src = 'produs.php?id=' + id + '&modal=1';
            document.getElementById('productInfoModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeProductModal(e) {
            if (e.target.id === 'productInfoModal') {
                document.getElementById('productInfoModal').style.display = 'none';
                document.getElementById('productIframe').src = ''; 
                document.body.style.overflow = 'auto';
            }
        }
        function closeProductModalBtn() {
            document.getElementById('productInfoModal').style.display = 'none';
            document.getElementById('productIframe').src = ''; 
            document.body.style.overflow = 'auto';
        }
    </script>
    
    <?php include 'cart_modal.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>