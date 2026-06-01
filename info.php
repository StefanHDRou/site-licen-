<?php
require 'config.php';

$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';

// Preluăm parametrul din URL (ex: info.php?p=gdpr)
$page = isset($_GET['p']) ? $_GET['p'] : 'termeni';

$titlu_pagina = "";
$continut = "";

switch ($page) {
    case 'gdpr':
        $titlu_pagina = "Politica de Confidențialitate (GDPR)";
        $continut = "
            <h3>1. Colectarea Datelor</h3>
            <p>PC Shop colectează date personale (nume, adresa de email, telefon, adresa de livrare) strict în scopul procesării comenzilor și emiterii facturilor fiscale, conform legislației în vigoare.</p>
            <h3>2. Protecția Datelor</h3>
            <p>Datele dumneavoastră sunt stocate în siguranță pe serverele noastre și nu sunt vândute sau partajate cu terțe părți în scopuri de marketing. Parola contului este criptată.</p>
            <h3>3. Drepturile Dumneavoastră</h3>
            <p>Aveți dreptul de a solicita ștergerea completă a contului și a datelor asociate (Dreptul de a fi uitat) trimițând un email la adresa noastra de contact.</p>";
        break;
    case 'retur':
        $titlu_pagina = "Politica de Retur și Garanții";
        $continut = "
            <h3>1. Dreptul de Retur (14 zile)</h3>
            <p>Conform OUG 34/2014, consumatorul are dreptul să notifice în scris comerciantului că renunță la cumpărare, fără penalități și fără invocarea unui motiv, în termen de 14 zile calendaristice de la primirea produsului.</p>
            <h3>2. Condiții de Retur</h3>
            <p>Produsul returnat trebuie să fie în aceeași stare în care a fost livrat (în ambalajul original cu toate accesoriile, cu etichetele intacte și documentele care l-au însoțit). Componentele PC care prezintă urme de montaj zgârieturi sau pini îndoiți nu sunt acceptate.</p>
            <h3>3. Garanție</h3>
            <p>Toate componentele hardware vândute beneficiază de o garanție comercială de 24 de luni, cu excepția cazurilor în care producătorul specifică altfel.</p>";
        break;
    case 'termeni':
    default:
        $titlu_pagina = "Termeni și Condiții";
        $continut = "
            <h3>1. Dispoziții Generale</h3>
            <p>Vizitarea, folosirea, sau comandarea produselor vizualizate pe site-ul PC Shop implică acceptarea Termenilor și Condițiilor de mai jos.</p>
            <h3>2. Prețuri și Stocuri</h3>
            <p>Prețurile sunt actualizate zilnic și includ TVA. Ne rezervăm dreptul de a anula comenzile pentru produse care afișează prețuri eronate din cauza unor defecțiuni tehnice ale platformei.</p>
            <h3>3. Litigii</h3>
            <p>Orice conflict apărut între PC Shop și clienți se va rezolva pe cale amiabilă. În cazul în care acest lucru nu este posibil, litigiul va fi soluționat de instanțele judecătorești din România.</p>";
        break;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titlu_pagina; ?> - PC Shop</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; }
        .info-container { max-width: 800px; margin: 50px auto; background: #1e1e1e; padding: 40px; border-radius: 12px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: #9b59b6; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;}
        h3 { color: #f39c12; margin-top: 30px; }
        p { line-height: 1.6; color: #bbb; font-size: 15px; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="info-container">
    <h1><?php echo $titlu_pagina; ?></h1>
    <div class="info-content">
        <?php echo $continut; ?>
    </div>
</div>

<?php 
require 'cart_modal.php'; 
require 'footer.php'; 
?>
</body>
</html>