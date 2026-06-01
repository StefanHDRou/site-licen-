<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php'); exit;
}

$msg = "";

// --- LOGICA PENTRU ȘTERGERE PRODUS ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $mysqli->query("DELETE FROM produse WHERE id = $del_id");
    $msg = "Produsul a fost șters cu succes!";
}

// --- LOGICA PENTRU ACTUALIZARE (SALVARE EDITARE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_produs = (int)$_POST['produs_id'];
    $nume = $_POST['nume'];
    $descriere = $_POST['descriere'];
    $pret = $_POST['pret'];
    
    // Extragem prețul vechi. Dacă e gol, îi dăm valoarea 'NULL' pt a ști că nu e redus.
    $pret_vechi = !empty($_POST['pret_vechi']) ? $_POST['pret_vechi'] : null;
    
    $stoc = $_POST['stoc'];
    $categorie_id = $_POST['categorie'];
    
    $query_poza = "";
    if (isset($_FILES['poza']) && $_FILES['poza']['error'] == 0) {
        $ext = pathinfo($_FILES['poza']['name'], PATHINFO_EXTENSION);
        $imagine_nume = preg_replace('/[^a-zA-Z0-9]/', '', $nume) . "_" . time() . "." . $ext; 
        move_uploaded_file($_FILES['poza']['tmp_name'], "images/" . $imagine_nume);
        $query_poza = ", imagine_url = '$imagine_nume'";
    }

    // Actualizăm instrucțiunea SQL ca să includă și `pret_vechi`
    $stmt = $mysqli->prepare("UPDATE produse SET categorie_id=?, nume=?, descriere=?, pret=?, pret_vechi=?, stoc=? $query_poza WHERE id=?");
    $stmt->bind_param("issddii", $categorie_id, $nume, $descriere, $pret, $pret_vechi, $stoc, $id_produs);
    
    if ($stmt->execute()) {
        $msg = "Produsul a fost actualizat cu succes!";
        
        // Ștergem specificațiile vechi și le salvăm pe cele noi de pe ecran
        $mysqli->query("DELETE FROM specificatii_produse WHERE produs_id = $id_produs");

        if (isset($_POST['spec_nume']) && isset($_POST['spec_valoare'])) {
            $nume_specs = $_POST['spec_nume'];
            $val_specs = $_POST['spec_valoare'];
            $home_specs = isset($_POST['spec_home']) ? $_POST['spec_home'] : []; 
            
            $stmt_spec = $mysqli->prepare("INSERT INTO specificatii_produse (produs_id, nume_specificatie, valoare_specificatie, afisare_home) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($nume_specs); $i++) {
                $n = trim($nume_specs[$i]);
                $v = trim($val_specs[$i]);
                $h = (isset($home_specs[$i]) && $home_specs[$i] == '1') ? 1 : 0;
                
                if (!empty($n) && !empty($v)) {
                    $stmt_spec->bind_param("issi", $id_produs, $n, $v, $h);
                    $stmt_spec->execute();
                }
            }
            $stmt_spec->close();
        }
    } else {
        $msg = "Eroare la actualizarea produsului.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Admin - Editează Produse</title>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .nav-admin { margin-bottom: 20px; }
        .nav-admin a { color: #fff; text-decoration: none; margin-right: 20px; font-weight: bold; font-size: 18px; padding-bottom: 5px; }
        .nav-admin a.active { color: #e74c3c; border-bottom: 2px solid #e74c3c; }

        .alert { padding: 15px; background: #27ae60; color: white; margin-bottom: 15px; font-weight: bold; border-radius: 6px; text-align: center; max-width: 900px; }

        /* Stiluri Tabel Produse */
        table.admin-table { width: 100%; max-width: 1000px; border-collapse: collapse; background: #1e1e1e; border-radius: 8px; overflow: hidden; border: 1px solid #333; }
        .admin-table th { background: #252525; padding: 15px; text-align: left; color: #fff; border-bottom: 1px solid #333; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #333; vertical-align: middle; }
        .admin-table tr:hover { background-color: #2a2a2a; }
        .prod-img-small { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        
        .action-buttons { display: flex; gap: 20px; align-items: center; }
        .text-glow-btn { background: transparent; color: #aaa; text-decoration: none; font-size: 15px; font-weight: bold; transition: all 0.3s ease; cursor: pointer; border: none; padding: 0; outline: none; }
        .text-glow-btn:hover { color: #fff; text-shadow: 0 0 8px #9b59b6, 0 0 15px #9b59b6, 0 0 25px #9b59b6; }

        /* MODAL CUSTOM PENTRU ȘTERGERE */
        .del-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .del-modal-content { background: #1a1a1a; padding: 30px; border-radius: 12px; border: 1px solid #e74c3c; width: 450px; max-width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .del-modal-content h2 { margin-top: 0; color: #fff; }
        .checkbox-container { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 25px 0; cursor: pointer; font-size: 16px; color: #ccc; }
        .checkbox-container input { width: 18px; height: 18px; cursor: pointer; }
        .modal-buttons { display: flex; justify-content: center; gap: 20px; }
        .btn-neon { padding: 12px 25px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; border: none; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; color: #fff; }
        .btn-cancel { background: #444; } .btn-cancel:hover { background: #666; box-shadow: 0 0 10px #888, 0 0 20px #888; text-shadow: 0 0 5px #fff; }
        .btn-confirm { background: #c0392b; } .btn-confirm:disabled { background: #333; color: #777; cursor: not-allowed; box-shadow: none; }
        .btn-confirm:not(:disabled):hover { background: #e74c3c; box-shadow: 0 0 15px #e74c3c, 0 0 30px #e74c3c, 0 0 50px #e74c3c; text-shadow: 0 0 8px #fff; transform: scale(1.05); }

        /* Formular de Editare */
        .form-container { max-width: 800px; background: #1e1e1e; padding: 30px; border-radius: 8px; border: 1px solid #333; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; margin: 10px 0; background: #2c2c2c; border: 1px solid #444; color: white; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; min-height: 100px; }
        button.btn-submit { width: 100%; padding: 12px; background: #e74c3c; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 20px; font-size: 16px; border-radius: 6px; }
        button.btn-submit:hover { background: #c0392b; }
        label { color: #aaa; font-size: 14px; display: block; margin-top: 15px; font-weight: bold; }

        .ai-generate-wrapper { display: flex; gap: 10px; align-items: center; }
        .btn-ai { background: #8e44ad; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap; height: 42px; margin-top: 10px; }
        .btn-ai:hover { background: #9b59b6; box-shadow: 0 0 10px #9b59b6, 0 0 20px #8e44ad; transform: translateY(-1px); }
        .btn-ai:disabled { background: #555; cursor: not-allowed; box-shadow: none; transform: none; color: #aaa; }
        .spinner { border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 3px solid #fff; width: 16px; height: 16px; animation: spin 1s linear infinite; display: none; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Specificatii */
        .specs-container { margin-top: 10px; }
        .spec-row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; background: #252525; padding: 10px; border-radius: 6px; border: 1px solid #444; }
        .spec-row.dragging { opacity: 0.5; background: #333; border-color: #9b59b6; }
        .spec-row.section-row { background: #2c3e50; border-color: #34495e; }
        .spec-row.section-row input { font-weight: bold; background: #34495e; color: #fff; }
        .drag-handle { color: #888; font-size: 20px; cursor: grab; padding: 0 10px; user-select: none; }
        .spec-input { flex: 1; margin: 0 !important; }
        button.btn-remove-spec { background: #c0392b; color: white; border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-add-spec { background: #27ae60; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        .btn-add-section { background: #2980b9; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; margin-left: 10px; }
    </style>
</head>
<body>

    <div class="nav-admin">
        <a href="home.php">&larr; Site</a>
        <a href="admin_orders.php">Comenzi</a>
        <a href="admin_products.php">Adaugă Produse</a>
        <a href="admin_edit_products.php" class="active">Editează Produse</a>
    </div>

    <?php if($msg) echo "<div class='alert'>$msg</div>"; ?>

    <?php
    // ==========================================
    // ECRANUL 1: FORMULARUL DE EDITARE PENTRU UN PRODUS
    // ==========================================
    if (isset($_GET['edit_id'])): 
        $edit_id = (int)$_GET['edit_id'];
        $prod_result = $mysqli->query("SELECT * FROM produse WHERE id = $edit_id");
        if ($prod_result->num_rows == 0) { echo "<p>Produsul nu a fost găsit.</p>"; exit; }
        $p = $prod_result->fetch_assoc();
        
        $cats = $mysqli->query("SELECT * FROM categorii");
        $specs = $mysqli->query("SELECT * FROM specificatii_produse WHERE produs_id = $edit_id ORDER BY id ASC");
    ?>
        
        <h1>Editează Produsul: <span style="color:#9b59b6;"><?php echo $p['nume']; ?></span></h1>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="produs_id" value="<?php echo $p['id']; ?>">

                <label>Nume Produs</label>
                <div class="ai-generate-wrapper">
                    <input type="text" id="inputNume" name="nume" required value="<?php echo htmlspecialchars($p['nume']); ?>">
                    <button type="button" class="btn-ai" id="btnAI" onclick="genereazaCuAI()">
                        <span id="aiText">✨ Re-generează cu AI</span>
                        <div class="spinner" id="aiSpinner"></div>
                    </button>
                </div>
                <small style="color:#9b59b6; margin-top:5px; display:block;">*Atenție: Funcția AI va rescrie textul și specificațiile de mai jos!</small>

                <label>Categorie</label>
                <select name="categorie">
                    <?php while($c = $cats->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($c['id'] == $p['categorie_id']) echo 'selected'; ?>><?php echo $c['nume']; ?></option>
                    <?php endwhile; ?>
                </select>

                <div style="display:flex; gap:20px;">
                    <div style="flex:1;">
                        <label>Preț Actual (RON)</label>
                        <input type="number" name="pret" step="0.01" required value="<?php echo $p['pret']; ?>">
                    </div>
                    <div style="flex:1;">
                        <label>Preț Vechi (RON) <span style="font-weight:normal; color:#888;">- Opțional</span></label>
                        <input type="number" name="pret_vechi" step="0.01" value="<?php echo $p['pret_vechi']; ?>" placeholder="Ex: 500">
                        <small style="color:#888; font-size:12px;">Dacă e mai mare decât prețul actual, produsul va apărea la reducere.</small>
                    </div>
                    <div style="flex:1;">
                        <label>Stoc</label>
                        <input type="number" name="stoc" required value="<?php echo $p['stoc']; ?>">
                    </div>
                </div>

                <label>Descriere Produs</label>
                <textarea id="inputDescriere" name="descriere"><?php echo htmlspecialchars($p['descriere']); ?></textarea>

                <label>Specificații Tehnice (Modifică, reordonează sau șterge)</label>
                <div id="specs-wrapper" class="specs-container">
                    <?php while($s = $specs->fetch_assoc()): ?>
                        <?php if ($s['valoare_specificatie'] === '__SECTIUNE__'): ?>
                            <div class="spec-row section-row" draggable="true">
                                <div class="drag-handle" style="color:#fff;">☰</div>
                                <input type="text" name="spec_nume[]" class="spec-input" value="<?php echo htmlspecialchars($s['nume_specificatie']); ?>">
                                <input type="hidden" name="spec_valoare[]" value="__SECTIUNE__">
                                <input type="hidden" name="spec_home[]" value="0"> 
                                <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">✖</button>
                            </div>
                        <?php else: ?>
                            <div class="spec-row" draggable="true">
                                <div class="drag-handle">☰</div>
                                <input type="text" name="spec_nume[]" class="spec-input" value="<?php echo htmlspecialchars($s['nume_specificatie']); ?>">
                                <input type="text" name="spec_valoare[]" class="spec-input" value="<?php echo htmlspecialchars($s['valoare_specificatie']); ?>">
                                
                                <?php 
                                    $isHome = ($s['afisare_home'] == 1) ? 'checked' : ''; 
                                    $homeVal = ($s['afisare_home'] == 1) ? '1' : '0';
                                ?>
                                <label style="margin:0; display:flex; align-items:center; cursor:pointer; background:#333; padding:5px 10px; border-radius:4px;" title="Bifează dacă vrei să apară pe Home">
                                    <input type="hidden" name="spec_home[]" value="<?php echo $homeVal; ?>">
                                    <input type="checkbox" onchange="this.previousElementSibling.value = this.checked ? 1 : 0" <?php echo $isHome; ?>> 🏠
                                </label>

                                <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">✖</button>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
                
                <div style="display: flex;">
                    <button type="button" class="btn-add-spec" onclick="addSpecRow()">+ Adaugă Specificație</button>
                    <button type="button" class="btn-add-section" onclick="addSectionRow()">+ Adaugă Secțiune</button>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #333; margin: 25px 0;">

                <label>Imagine Produs (Lasă gol pentru a păstra imaginea actuală)</label>
                <div style="margin-bottom:10px;">
                    <img src="<?php echo (strpos($p['imagine_url'], 'images') === 0) ? $p['imagine_url'] : "images/" . $p['imagine_url']; ?>" style="height:80px; border-radius:4px;">
                </div>
                <input type="file" name="poza" accept="image/*">

                <button type="submit" class="btn-submit">Salvează Modificările</button>
                <a href="admin_edit_products.php" style="display:block; text-align:center; color:#aaa; margin-top:15px; text-decoration:none;">Anulează</a>
            </form>
        </div>

        <script>
            // --- JAVASCRIPT PENTRU FORMULAR ȘI AI ---
            const specsWrapper = document.getElementById('specs-wrapper');

            function addSpecRow(numeVal = '', valoareVal = '') { 
                const row = document.createElement('div'); row.className = 'spec-row'; row.draggable = true; 
                row.innerHTML = `
                    <div class="drag-handle">☰</div>
                    <input type="text" name="spec_nume[]" class="spec-input" placeholder="Nume" value="${numeVal}">
                    <input type="text" name="spec_valoare[]" class="spec-input" placeholder="Valoare" value="${valoareVal}">
                    <label style="margin:0; display:flex; align-items:center; cursor:pointer; background:#333; padding:5px 10px; border-radius:4px;" title="Bifează dacă vrei să apară pe Home">
                        <input type="hidden" name="spec_home[]" value="0">
                        <input type="checkbox" onchange="this.previousElementSibling.value = this.checked ? 1 : 0"> 🏠
                    </label>
                    <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">✖</button>
                `; 
                addDragEvents(row); specsWrapper.appendChild(row); 
            }
            
            function addSectionRow(numeVal = '') { 
                const row = document.createElement('div'); row.className = 'spec-row section-row'; row.draggable = true; 
                row.innerHTML = `
                    <div class="drag-handle" style="color:#fff;">☰</div>
                    <input type="text" name="spec_nume[]" class="spec-input" placeholder="Nume Secțiune" value="${numeVal}">
                    <input type="hidden" name="spec_valoare[]" value="__SECTIUNE__">
                    <input type="hidden" name="spec_home[]" value="0">
                    <button type="button" class="btn-remove-spec" onclick="this.parentElement.remove()">✖</button>
                `; 
                addDragEvents(row); specsWrapper.appendChild(row); 
            }

            function addDragEvents(row) { row.addEventListener('dragstart', () => row.classList.add('dragging')); row.addEventListener('dragend', () => row.classList.remove('dragging')); }
            document.querySelectorAll('.spec-row').forEach(row => addDragEvents(row));
            
            specsWrapper.addEventListener('dragover', e => { e.preventDefault(); const afterElement = getDragAfterElement(specsWrapper, e.clientY); const draggable = document.querySelector('.dragging'); if (afterElement == null) specsWrapper.appendChild(draggable); else specsWrapper.insertBefore(draggable, afterElement); });
            function getDragAfterElement(container, y) { const draggableElements = [...container.querySelectorAll('.spec-row:not(.dragging)')]; return draggableElements.reduce((closest, child) => { const box = child.getBoundingClientRect(); const offset = y - box.top - box.height / 2; if (offset < 0 && offset > closest.offset) return { offset: offset, element: child }; else return closest; }, { offset: Number.NEGATIVE_INFINITY }).element; }

            // Logica butonului AI
            async function genereazaCuAI() {
                const numeProdus = document.getElementById('inputNume').value.trim();
                if (!numeProdus) { alert("Scrie un nume de produs în căsuță!"); return; }
                if(!confirm("Ești sigur? Generarea cu AI va înlocui descrierea și specificațiile curente de pe ecran.")) return;

                const btn = document.getElementById('btnAI');
                const txt = document.getElementById('aiText');
                const spin = document.getElementById('aiSpinner');
                btn.disabled = true; txt.textContent = 'Gândește...'; spin.style.display = 'inline-block';

                try {
                    const response = await fetch('ai_generator.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ produs: numeProdus }) });
                    const data = await response.json();

                    if (data.error) { alert("Eroare AI: " + data.error); } 
                    else {
                        if (data.descriere) document.getElementById('inputDescriere').value = data.descriere;
                        if (data.specificatii && Array.isArray(data.specificatii)) {
                            specsWrapper.innerHTML = ''; 
                            data.specificatii.forEach(spec => {
                                if (spec.valoare === '__SECTIUNE__') { addSectionRow(spec.nume); } 
                                else if (spec.nume === '__SECTIUNE__') {
                                    let titluReal = (spec.valoare === 'Valoare' || spec.valoare === '') ? 'Secțiune Nouă' : spec.valoare;
                                    addSectionRow(titluReal);
                                } else { addSpecRow(spec.nume, spec.valoare); }
                            });
                        }
                    }
                } catch (error) {
                    console.error(error); alert("Eroare la conexiunea cu Ollama (asigură-te că rulează local).");
                } finally {
                    btn.disabled = false; txt.textContent = '✨ Re-generează cu AI'; spin.style.display = 'none';
                }
            }
        </script>

    <?php 
    // ==========================================
    // ECRANUL 2: LISTA CU PRODUSE
    // ==========================================
    else: 
        $query = "SELECT p.*, c.nume as categorie_nume FROM produse p LEFT JOIN categorii c ON p.categorie_id = c.id ORDER BY p.id DESC";
        $result = $mysqli->query($query);
    ?>
        
        <h1>Gestionare și Editare Produse</h1>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Poză</th>
                    <th>Nume Produs</th>
                    <th>Categorie</th>
                    <th>Preț</th>
                    <th>Stoc</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php while($prod = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <img src="<?php echo (strpos($prod['imagine_url'], 'images') === 0) ? $prod['imagine_url'] : "images/" . $prod['imagine_url']; ?>" class="prod-img-small">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($prod['nume']); ?></strong>
                        <?php if($prod['pret_vechi'] > $prod['pret']): ?>
                            <br><span style="font-size:11px; background:#e74c3c; color:white; padding:2px 6px; border-radius:4px; margin-top:5px; display:inline-block;">LA REDUCERE</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#aaa;"><?php echo htmlspecialchars($prod['categorie_nume']); ?></td>
                    <td>
                        <strong style="color:#27ae60;"><?php echo $prod['pret']; ?> RON</strong>
                        <?php if($prod['pret_vechi'] > $prod['pret']): ?>
                            <br><span style="font-size:12px; color:#888; text-decoration:line-through;"><?php echo $prod['pret_vechi']; ?> RON</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                            if($prod['stoc'] == 0) echo "<span style='color:#e74c3c;font-weight:bold;'>0 (Epuizat)</span>";
                            else echo $prod['stoc'];
                        ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="admin_edit_products.php?edit_id=<?php echo $prod['id']; ?>" class="text-glow-btn">Editează</a>
                            <button type="button" class="text-glow-btn" onclick="openDeleteModal(<?php echo $prod['id']; ?>, '<?php echo htmlspecialchars(addslashes($prod['nume']), ENT_QUOTES); ?>')">Șterge</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="del-modal-overlay" id="deleteModal">
            <div class="del-modal-content">
                <h2>Avertizare</h2>
                <p style="font-size: 16px; color: #ddd; line-height: 1.5;">
                    Produsul <br><strong id="deleteProdName" style="color: #e74c3c; font-size: 18px;"></strong><br> va fi șters definitiv din baza de date! Sunteți sigur?
                </p>
                <label class="checkbox-container">
                    <input type="checkbox" id="agreeCheckbox" onchange="toggleDeleteBtn()"> Sunt de acord
                </label>
                <div class="modal-buttons">
                    <button class="btn-neon btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button class="btn-neon btn-confirm" id="confirmDelBtn" disabled onclick="executeDelete()">Șterge</button>
                </div>
            </div>
        </div>

        <script>
            let currentDeleteId = null;
            function openDeleteModal(id, nume) {
                currentDeleteId = id; document.getElementById('deleteProdName').textContent = nume;
                document.getElementById('agreeCheckbox').checked = false; document.getElementById('confirmDelBtn').disabled = true;
                document.getElementById('deleteModal').style.display = 'flex';
            }
            function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; currentDeleteId = null; }
            function toggleDeleteBtn() { document.getElementById('confirmDelBtn').disabled = !document.getElementById('agreeCheckbox').checked; }
            function executeDelete() { if(currentDeleteId !== null) window.location.href = 'admin_edit_products.php?delete_id=' + currentDeleteId; }
        </script>

    <?php endif; ?>

</body>
</html>