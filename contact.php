<?php
session_start();
require 'send_email.php'; // Includem funcția de trimitere mail

// Conectare DB (pentru consistență sesiuni/cart)
$host = '127.0.0.1'; $db = 'db'; $user = 'root'; $pass = '';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try { $mysqli = new mysqli($host, $user, $pass, $db); } catch (Exception $e) {}

// Logica Link Profil
$linkProfil = isset($_SESSION['user_id']) ? 'profile.php' : 'login.php';

$msg = "";
$msgType = "";

// LOGICA TRIMITE MESAJ CONTACT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $nume = htmlspecialchars($_POST['nume']);
    $email = htmlspecialchars($_POST['email']);
    $subiect = htmlspecialchars($_POST['subiect']);
    $mesaj = htmlspecialchars($_POST['mesaj']);

    // Construim corpul emailului pe care îl primești TU (Adminul)
    $emailBody = "
        <h3>Mesaj Nou de pe Site</h3>
        <p><b>Nume:</b> $nume</p>
        <p><b>Email:</b> $email</p>
        <p><b>Subiect:</b> $subiect</p>
        <hr>
        <p><b>Mesaj:</b><br> $mesaj</p>
    ";

    // Trimitem email către TINE (Admin)
    // Înlocuiește 'adresa.ta@gmail.com' cu adresa unde vrei să primești mesajele
    if (trimiteEmail('adresa.ta@gmail.com', 'Admin PC Shop', "Contact: $subiect", $emailBody)) {
        $msg = "Mesajul a fost trimis! Îți vom răspunde curând.";
        $msgType = "success";
    } else {
        $msg = "Eroare la trimitere. Verifică conexiunea.";
        $msgType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - PC Shop</title>
    <style>
        /* ================= STILURI GLOBALE (Aceleași ca Home) ================= */
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background-color: #121212; color: #e0e0e0; }

        /* HEADER & NAV */
        header { background-color: #1f1f1f; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #8e44ad; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 26px; font-weight: bold; color: #fff; letter-spacing: 1px; text-decoration: none;}
        .logo span { color: #9b59b6; }
        
        nav { display: flex; align-items: center; }
        nav a { color: #bbb; text-decoration: none; margin-left: 25px; font-size: 16px; transition: color 0.3s; }
        nav a:hover, nav a.active { color: #9b59b6; }
        .cart-trigger { position: relative; cursor: pointer; margin-left: 30px; display: flex; align-items: center; color: #bbb; transition: color 0.3s; }
        .cart-trigger:hover { color: #9b59b6; }
        .cart-count { background-color: #9b59b6; color: white; border-radius: 50%; font-size: 11px; padding: 2px 5px; position: absolute; top: -5px; right: -8px; font-weight: bold; }

        /* ================= STILURI CONTACT ================= */
        .hero { text-align: center; padding: 50px 20px; background: linear-gradient(180deg, #1f1f1f 0%, #121212 100%); }
        .hero h1 { font-size: 2.5em; margin: 0; color: white; }
        .hero p { color: #888; margin-top: 10px; }

        .contact-container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
        
        .info-box { background: #1e1e1e; padding: 30px; border-radius: 12px; border: 1px solid #333; height: fit-content; }
        .info-item { display: flex; align-items: flex-start; margin-bottom: 25px; }
        .icon-circle { background: #252525; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; color: #9b59b6; flex-shrink: 0; }
        .info-content h3 { margin: 0 0 5px 0; font-size: 16px; color: #fff; }
        .info-content p { margin: 0; color: #aaa; font-size: 14px; }

        .form-box { background: #1e1e1e; padding: 30px; border-radius: 12px; border: 1px solid #333; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0 20px 0; background: #2c2c2c; border: 1px solid #444; color: white; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        input:focus, textarea:focus { border-color: #9b59b6; outline: none; }
        label { color: #ccc; font-size: 14px; }
        
        .btn-send { width: 100%; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 16px; transition: background 0.2s; }
        .btn-send:hover { background: #8e44ad; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background: #27ae60; color: white; }
        .error { background: #c0392b; color: white; }

        /* Map */
        .map-frame { width: 100%; height: 250px; border: 0; border-radius: 8px; margin-top: 20px; filter: brightness(75%) contrast(150%); }

        /* CHAT UI */
        .chat-toggle-btn { position: fixed; bottom: 30px; right: 30px; background-color: #8e44ad; color: white; border: none; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; box-shadow: 0 4px 15px rgba(142, 68, 173, 0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; }
        .chat-toggle-btn:hover { transform: scale(1.1); }
        .chat-container { position: fixed; bottom: 100px; right: 30px; width: 340px; height: 480px; background-color: #1e1e1e; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); display: none; flex-direction: column; overflow: hidden; z-index: 9999; border: 1px solid #333; }
        .chat-header { background-color: #8e44ad; color: white; padding: 15px 20px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background-color: #1e1e1e; }
        .message { max-width: 80%; padding: 12px 16px; font-size: 14px; }
        .bot-message { background-color: #ecf0f1; color: #2c3e50; border-radius: 20px 20px 20px 5px; align-self: flex-start; }
        .user-message { background-color: #9b59b6; color: white; border-radius: 20px 20px 5px 20px; align-self: flex-end; }
        .chat-footer { padding: 15px; background-color: #252525; display: flex; align-items: center; gap: 10px; border-top: 1px solid #333; }
        .chat-input { flex: 1; padding: 12px 15px; border-radius: 25px; border: 1px solid #8e44ad; background-color: #1e1e1e; color: #ddd; outline: none; margin: 0; }
        .send-btn { background-color: #9b59b6; width: 45px; height: 45px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        @media (max-width: 768px) { .contact-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
    
    <div class="hero">
        <h1>Contactează-ne</h1>
        <p>Suntem aici să te ajutăm cu orice întrebare despre componente PC.</p>
    </div>

    <div class="contact-container">
        
        <div class="info-box">
            <div class="info-item">
                <div class="icon-circle">📍</div>
                <div class="info-content">
                    <h3>Adresă Showroom</h3>
                    <p>Strada Libertății 10, București<br>Sector 1, 010101</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="icon-circle">📞</div>
                <div class="info-content">
                    <h3>Telefon</h3>
                    <p>0722 123 456<br>Luni - Vineri: 09:00 - 18:00</p>
                </div>
            </div>

            <div class="info-item">
                <div class="icon-circle">✉️</div>
                <div class="info-content">
                    <h3>Email</h3>
                    <p>suport@pcshop.ro<br>Răspundem în maxim 24h</p>
                </div>
            </div>

            <iframe class="map-frame" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d182348.67389478476!2d25.968798952097036!3d44.434771383749445!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1f93abf3cad4f%3A0ac0d6d67c791932!2sBucharest!5e0!3m2!1sen!2sro!4v1715000000000!5m2!1sen!2sro" allowfullscreen="" loading="lazy"></iframe>
        </div>

        <div class="form-box">
            <?php if($msg): ?>
                <div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="contact_form" value="1">
                
                <label>Numele Tău</label>
                <input type="text" name="nume" required placeholder="Ex: Ion Popescu">

                <label>Email</label>
                <input type="email" name="email" required placeholder="adresa@email.com">

                <label>Subiect</label>
                <input type="text" name="subiect" required placeholder="Ex: Întrebare despre stoc">

                <label>Mesaj</label>
                <textarea name="mesaj" rows="6" required placeholder="Scrie mesajul tău aici..."></textarea>

                <button type="submit" class="btn-send">Trimite Mesaj</button>
            </form>
        </div>
    </div>

    <button class="chat-toggle-btn" onclick="toggleChat()">
        <svg viewBox="0 0 24 24" style="width:30px;height:30px;fill:white;"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
    </button>
    <div class="chat-container" id="chatBox">
        <div class="chat-header"><span>Asistent PC Shop</span><span onclick="toggleChat()" style="cursor:pointer;">✖</span></div>
        <div class="chat-body" id="chatBody"><div class="message bot-message">Salut! Cum te pot ajuta?</div></div>
        <div class="chat-footer">
            <input type="text" class="chat-input" id="userInput" placeholder="Scrie..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button class="send-btn" onclick="sendMessage()"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:white;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button>
        </div>
    </div>

    <script>
        // --- LOGICĂ CHAT ---
        let chatHistory = [];
        function toggleChat() {
            const c = document.getElementById('chatBox');
            c.style.display = (c.style.display === 'none' || c.style.display === '') ? 'flex' : 'none';
        }
        async function sendMessage() {
            const inp = document.getElementById('userInput');
            const txt = inp.value.trim();
            if(!txt) return;
            const body = document.getElementById('chatBody');
            
            body.innerHTML += `<div class="message user-message" style="align-self:flex-end; margin-bottom:10px;">${txt}</div>`;
            chatHistory.push({ role: "user", content: txt });
            inp.value = '';
            body.scrollTop = body.scrollHeight;

            const loading = document.createElement('div');
            loading.className = 'message bot-message'; loading.textContent = '...'; loading.id = 'loading';
            body.appendChild(loading);

            try {
                const res = await fetch('chat_api.php', { method: 'POST', body: JSON.stringify({ history: chatHistory }) });
                const d = await res.json();
                document.getElementById('loading').remove();
                body.innerHTML += `<div class="message bot-message" style="align-self:flex-start; margin-bottom:10px;">${d.reply}</div>`;
                chatHistory.push({ role: "assistant", content: d.reply });
            } catch(e) {
                document.getElementById('loading').remove();
            }
            body.scrollTop = body.scrollHeight;
        }
    </script>

    <?php require 'cart_modal.php'; ?>
    <?php require 'footer.php'; ?>
</body>
</html>