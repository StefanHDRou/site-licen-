<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.php'); exit;
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume = $_POST['nume'];
    $descriere = $_POST['descriere'];
    $pret = $_POST['pret'];
    $stoc = $_POST['stoc'];
    $categorie_id = $_POST['categorie'];
    
    $imagine_nume = "";
    if (isset($_FILES['poza']) && $_FILES['poza']['error'] == 0) {
        $ext = pathinfo($_FILES['poza']['name'], PATHINFO_EXTENSION);
        $imagine_nume = preg_replace('/[^a-zA-Z0-9]/', '', $nume) . "_" . time() . "." . $ext; 
        $destinatie = "images/" . $imagine_nume;
        move_uploaded_file($_FILES['poza']['tmp_name'], $destinatie);
    }

    $stmt = $mysqli->prepare("INSERT INTO produse (categorie_id, nume, descriere, pret, stoc, imagine_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdis", $categorie_id, $nume, $descriere, $pret, $stoc, $imagine_nume);
    
    if ($stmt->execute()) {
        $produs_id = $stmt->insert_id; 
        $msg = "Produsul a fost adăugat cu succes!";

        if (isset($_POST['spec_nume']) && isset($_POST['spec_valoare'])) {
            $nume_specs = $_POST['spec_nume'];
            $val_specs = $_POST['spec_valoare'];
            
            if (isset($_POST['spec_nume']) && isset($_POST['spec_valoare'])) {
    $nume_specs = $_POST['spec_nume'];
    $val_specs = $_POST['spec_valoare'];
    $home_specs = $_POST['spec_home']; // Preluăm bifările
    
    $stmt_spec = $mysqli->prepare("INSERT INTO specificatii_produse (produs_id, nume_specificatie, valoare_specificatie, afisare_home) VALUES (?, ?, ?, ?)");
    
    for ($i = 0; $i < count($nume_specs); $i++) {
        $n = trim($nume_specs[$i]);
        $v = trim($val_specs[$i]);
        $h = (isset($home_specs[$i]) && $home_specs[$i] == '1') ? 1 : 0;
        
        if (!empty($n) && !empty($v)) {
            $stmt_spec->bind_param("issi", $produs_id, $n, $v, $h);
            $stmt_spec->execute();
        }
    }
    $stmt_spec->close();}
        }
    } else {
        $msg = "Eroare la adăugarea produsului.";
    }
    $stmt->close();
}

$cats = $mysqli->query("SELECT * FROM categorii");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Admin - Adaugă Produse cu AI</title>
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .nav-admin { margin-bottom: 20px; }
        .nav-admin a { color: #fff; text-decoration: none; margin-right: 20px; font-weight: bold; font-size: 18px; }
        .nav-admin a.active { color: #e74c3c; border-bottom: 2px solid #e74c3c; }
        .nav-admin a:hover { color: #c0392b; }

        .form-container { max-width: 800px; background: #1e1e1e; padding: 30px; border-radius: 8px; border: 1px solid #333; position: relative; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; margin: 10px 0; background: #2c2c2c; border: 1px solid #444; color: white; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; min-height: 120px; }
        
        button.btn-submit { width: 100%; padding: 12px; background: #e74c3c; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 20px; font-size: 16px; border-radius: 6px; }
        button.btn-submit:hover { background: #c0392b; }
        .alert { padding: 15px; background: #27ae60; color: white; margin-bottom: 15px; font-weight: bold; border-radius: 6px; text-align: center; }
        label { color: #aaa; font-size: 14px; display: block; margin-top: 15px; font-weight: bold; }

        /* BUTONUL AI MAGIC */
        .ai-generate-wrapper { display: flex; gap: 10px; align-items: center; }
        .btn-ai {
            background: #8e44ad; color: #fff; border: none; padding: 10px 20px; border-radius: 6px;
            font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; white-space: nowrap; height: 42px; margin-top: 10px;
        }
        .btn-ai:hover { background: #9b59b6; box-shadow: 0 0 10px #9b59b6, 0 0 20px #8e44ad; transform: translateY(-1px); }
        .btn-ai:disabled { background: #555; cursor: not-allowed; box-shadow: none; transform: none; color: #aaa; }
        .spinner { border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 3px solid #fff; width: 16px; height: 16px; animation: spin 1s linear infinite; display: none; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Specificații */
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
        <a href="admin_products.php" class="active">Adaugă Produse</a>
        <a href="admin_edit_products.php">Editează Produse</a>
    </div>

    <h1>Adaugă Produs Nou</h1>

    <div class="form-container">
        <?php if($msg) echo "<div class='alert'>$msg</div>"; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Nume Produs</label>
            <div class="ai-generate-wrapper">
                <input type="text" id="inputNume" name="nume" required placeholder="Ex: ASUS ROG Strix GeForce RTX 4090">
                <button type="button" class="btn-ai" id="btnAI" onclick="genereazaCuAI()">
                    <span id="aiText">✨ Auto-Completează cu AI</span>
                    <div class="spinner" id="aiSpinner"></div>
                </button>
            </div>
            <small style="color:#9b59b6; margin-top:5px; display:block;">*Scrie numele și apasă butonul pentru a lăsa AI-ul să genereze descrierea și specificațiile!</small>

            <label>Categorie</label>
            <select name="categorie">
                <?php while($c = $cats->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['nume']; ?></option>
                <?php endwhile; ?>
            </select>

            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <label>Preț (RON)</label>
                    <input type="number" name="pret" step="0.01" required placeholder="2100.00">
                </div>
                <div style="flex:1;">
                    <label>Stoc</label>
                    <input type="number" name="stoc" required placeholder="15">
                </div>
            </div>

            <label>Descriere Produs</label>
            <textarea id="inputDescriere" name="descriere" placeholder="Prezentarea generală a produsului..."></textarea>

            <label>Specificații Tehnice (Pot fi adăugate manual sau generate de AI)</label>
            <div id="specs-wrapper" class="specs-container"></div>
            
            <div style="display: flex;">
                <button type="button" class="btn-add-spec" onclick="addSpecRow()">+ Adaugă Specificație</button>
                <button type="button" class="btn-add-section" onclick="addSectionRow()">+ Adaugă Secțiune</button>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #333; margin: 25px 0;">

            <label>Imagine Produs</label>
            <input type="file" name="poza" accept="image/*" required>

            <button type="submit" class="btn-submit">Publică Produsul</button>
        </form>
    </div>

    <script>
        const specsWrapper = document.getElementById('specs-wrapper');

        // Am modificat funcțiile ca să poată primi parametri (folositor pentru cand le creează AI-ul)
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

        // --- LOGICA MAGICĂ A AI-ULUI ---
        async function genereazaCuAI() {
            const numeProdus = document.getElementById('inputNume').value.trim();
            if (!numeProdus) {
                alert("Te rog să scrii mai întâi un nume de produs (ex: Ryzen 5 7600X)!");
                document.getElementById('inputNume').focus();
                return;
            }

            const btn = document.getElementById('btnAI');
            const txt = document.getElementById('aiText');
            const spin = document.getElementById('aiSpinner');

            // Setăm butonul în stare de "Încărcare"
            btn.disabled = true;
            txt.textContent = 'Gândește...';
            spin.style.display = 'inline-block';

            try {
                const response = await fetch('ai_generator.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ produs: numeProdus })
                });

                const data = await response.json();

                if (data.error) {
                    alert("Eroare AI: " + data.error);
                } else {
                    // 1. Completăm descrierea
                    if (data.descriere) {
                        document.getElementById('inputDescriere').value = data.descriere;
                    }
                    
                    // 2. Curățăm specificațiile vechi și le adăugăm pe cele noi
                    if (data.specificatii && Array.isArray(data.specificatii)) {
                        specsWrapper.innerHTML = ''; // Curățăm ecranul
                        
                        data.specificatii.forEach(spec => {
                            if (spec.valoare === '__SECTIUNE__') {
                                addSectionRow(spec.nume);
                            } else {
                                addSpecRow(spec.nume, spec.valoare);
                            }
                        });
                    }
                }
            } catch (error) {
                console.error(error);
                alert("Nu m-am putut conecta la AI. Verifică dacă Ollama rulează pe fundal.");
            } finally {
                // Resetăm butonul
                btn.disabled = false;
                txt.textContent = '✨ Auto-Completează cu AI';
                spin.style.display = 'none';
            }
        }

        // --- DRAG & DROP LOGIC ---
        function addDragEvents(row) { row.addEventListener('dragstart', () => row.classList.add('dragging')); row.addEventListener('dragend', () => row.classList.remove('dragging')); }
        document.querySelectorAll('.spec-row').forEach(row => addDragEvents(row));
        specsWrapper.addEventListener('dragover', e => { e.preventDefault(); const afterElement = getDragAfterElement(specsWrapper, e.clientY); const draggable = document.querySelector('.dragging'); if (afterElement == null) specsWrapper.appendChild(draggable); else specsWrapper.insertBefore(draggable, afterElement); });
        function getDragAfterElement(container, y) { const draggableElements = [...container.querySelectorAll('.spec-row:not(.dragging)')]; return draggableElements.reduce((closest, child) => { const box = child.getBoundingClientRect(); const offset = y - box.top - box.height / 2; if (offset < 0 && offset > closest.offset) return { offset: offset, element: child }; else return closest; }, { offset: Number.NEGATIVE_INFINITY }).element; }

        window.onload = function() { addSpecRow(); };
    </script>
</body>
</html>