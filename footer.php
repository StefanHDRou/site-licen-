<style>
    /* ========================================= */
    /* STILURI FOOTER CYBERPUNK / TECH           */
    /* ========================================= */
    .tech-footer {
        background-color: #151515;
        border-top: 2px solid #8e44ad;
        box-shadow: 0 -5px 25px rgba(142, 68, 173, 0.15); /* Glow-ul mov subtil în sus */
        color: #ccc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-top: 60px;
    }

    /* 1. BANDA DE ÎNCREDERE (Top) */
    .footer-trust-bar {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        padding: 20px 10%;
        background-color: #1a1a1a;
        border-bottom: 1px solid #2a2a2a;
        gap: 20px;
    }
    .trust-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #fff;
        font-size: 15px;
        letter-spacing: 0.5px;
    }
    .trust-item svg { width: 28px; height: 28px; fill: #9b59b6; filter: drop-shadow(0 0 5px rgba(155,89,182,0.5)); }

    /* 2. ZONA PRINCIPALĂ (Mijloc) */
    .footer-main {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 50px;
        padding: 50px 10%;
        max-width: 1400px;
        margin: 0 auto;
    }
    .footer-col h3 {
        color: #fff;
        margin-top: 0;
        margin-bottom: 25px;
        position: relative;
        display: inline-block;
        font-size: 18px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    /* Linia mov de sub titluri */
    .footer-col h3::after {
        content: '';
        position: absolute;
        left: 0; bottom: -8px;
        width: 40px; height: 3px;
        background: #9b59b6;
        box-shadow: 0 0 10px #9b59b6;
        transition: width 0.3s;
    }
    .footer-col:hover h3::after { width: 100%; } /* Efect șmecher la hover pe coloană */

    .footer-logo { font-size: 32px; font-weight: bold; color: #fff; letter-spacing: 1.5px; margin-bottom: 15px; display: inline-block; text-decoration: none; }
    .footer-logo span { color: #9b59b6; text-shadow: 0 0 15px rgba(155, 89, 182, 0.6); }
    .footer-desc { line-height: 1.6; font-size: 14px; margin-bottom: 25px; }

    /* Link-uri */
    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: 12px; }
    .footer-links a { color: #aaa; text-decoration: none; transition: all 0.3s ease; display: inline-flex; align-items: center; font-size: 15px; }
    .footer-links a::before { content: '▸'; color: #9b59b6; margin-right: 8px; font-size: 12px; transition: transform 0.3s; }
    .footer-links a:hover { color: #fff; text-shadow: 0 0 8px rgba(155,89,182,0.8); transform: translateX(5px); }
    .footer-links a:hover::before { transform: translateX(3px); }

    /* Social Media Buttons Glow */
    .social-icons { display: flex; gap: 15px; }
    .social-icons a {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 8px;
        background: #222; color: #aaa; transition: all 0.3s ease;
        border: 1px solid #333;
    }
    .social-icons a:hover { 
        background: #8e44ad; color: #fff; border-color: #9b59b6;
        box-shadow: 0 0 15px #9b59b6, 0 0 30px #8e44ad; 
        transform: translateY(-5px); 
    }
    .social-icons svg { width: 20px; height: 20px; fill: currentColor; }

    /* 3. ZONA DE JOS (Copyright, Plăți, ANPC) */
    .footer-bottom {
        background-color: #111;
        padding: 20px 10%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        font-size: 13px;
        border-top: 1px solid #252525;
        gap: 20px;
    }
    .footer-bottom-left { color: #777; }
    .footer-bottom-left strong { color: #9b59b6; }
    
    .footer-bottom-right { display: flex; gap: 25px; align-items: center; flex-wrap: wrap; }
    
    /* Iconițe Plată făcute din CSS pentru design minimalist */
    .payment-methods { display: flex; gap: 10px; }
    .pay-badge { 
        padding: 5px 10px; background: #222; border: 1px solid #333; 
        border-radius: 4px; font-weight: bold; font-size: 12px; color: #888;
        transition: 0.3s; cursor: default;
    }
    .pay-badge:hover { color: #fff; border-color: #9b59b6; background: #2a1b38; }

    /* Butoane ANPC */
    .anpc-links { display: flex; gap: 10px; }
    .anpc-btn {
        display: inline-block; padding: 6px 12px; border: 1px solid #444; border-radius: 4px;
        color: #aaa; text-decoration: none; font-weight: bold; font-size: 11px;
        transition: 0.3s; background: #1a1a1a;
    }
    .anpc-btn:hover { border-color: #fff; color: #111; background: #fff; }

    @media (max-width: 768px) {
        .footer-bottom { flex-direction: column; text-align: center; justify-content: center; }
        .footer-trust-bar { flex-direction: column; align-items: flex-start; padding: 20px 5%; }
    }
</style>

<footer class="tech-footer">
    
    <div class="footer-trust-bar">
        <div class="trust-item">
            <svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
            Livrare Rapidă
        </div>
        <div class="trust-item">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            Garanție 24 Luni
        </div>
        <div class="trust-item">
            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
            Plată Securizată
        </div>
        <div class="trust-item">
            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.18-5.01l-3.92-2.35V7h1.5v4.86l3.18 1.91-.76 1.22z"/></svg>
            Suport 24/7
        </div>
    </div>

    <div class="footer-main">
        
        <div class="footer-col">
            <a href="home.php" class="footer-logo">PC <span>SHOP</span></a>
            <p class="footer-desc">Hardware premium pentru gameri, creatori de conținut și profesioniști. Performanță extremă, prețuri corecte și suport pe măsură.</p>
            <div class="social-icons">
                <a href="#" title="Discord"><svg viewBox="0 0 24 24"><path d="M19.27 5.33C17.94 4.71 16.5 4.26 15 4a.09.09 0 0 0-.07.03c-.18.33-.39.76-.53 1.09a16.09 16.09 0 0 0-4.8 0c-.14-.34-.35-.76-.54-1.09c-.01-.02-.04-.03-.07-.03c-1.5.26-2.93.71-4.27 1.33c-.01 0-.02.01-.03.02c-2.72 4.07-3.47 8.03-3.1 11.95c0 .02.01.04.03.05c1.8 1.32 3.53 2.12 5.24 2.65c.03.01.06 0 .07-.02c.4-.55.76-1.13 1.07-1.74c.02-.04 0-.08-.04-.09c-.57-.22-1.11-.48-1.64-.78c-.04-.02-.04-.08-.01-.11c.11-.08.22-.17.33-.25c.02-.02.05-.02.07-.01c3.44 1.57 7.15 1.57 10.55 0c.02-.01.05-.01.07.01c.11.09.22.17.33.26c.04.03.04.09-.01.11c-.52.31-1.07.56-1.64.78c-.04.01-.05.06-.04.09c.32.61.68 1.19 1.07 1.74c.02.02.05.03.08.02c1.71-.53 3.44-1.33 5.24-2.65c.02-.01.03-.03.03-.05c.44-4.53-.73-8.46-3.1-11.95c-.01-.01-.02-.02-.04-.02zM8.52 14.91c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.84 2.12-1.89 2.12zm6.97 0c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.83 2.12-1.89 2.12z"/></svg></a>
                <a href="#" title="YouTube"><svg viewBox="0 0 24 24"><path d="M21.58 7.19c-.23-.86-.91-1.54-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42c-.86.23-1.54.91-1.77 1.77C2 8.75 2 12 2 12s0 3.25.42 4.81c.23.86.91 1.54 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42c.86-.23 1.54-.91 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81zM10 15V9l5.2 3-5.2 3z"/></svg></a>
                <a href="#" title="Instagram"><svg viewBox="0 0 24 24"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg></a>
            </div>
        </div>

        <div class="footer-col">
            <h3>Navigare Rapidă</h3>
            <ul class="footer-links">
                <li><a href="home.php">Acasă</a></li>
                <li><a href="components.php">Componente PC</a></li>
                <li><a href="configurator.php">Configurator Sisteme</a></li>
                <li><a href="contact.php">Contactează-ne</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Informații Clienți</h3>
            <ul class="footer-links">
                <li><a href="profile.php">Contul Meu & Comenzi</a></li>
                <li><a href="info.php?p=termeni">Termeni și Condiții</a></li>
                <li><a href="info.php?p=gdpr">Politica de Confidențialitate (GDPR)</a></li>
                <li><a href="info.php?p=retur">Politica de Retur și Garanții</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="footer-bottom-left">
            &copy; <?php echo date("Y"); ?> <strong>PC SHOP</strong>. Toate drepturile rezervate.
        </div>
        
        <div class="footer-bottom-right">
            <div class="payment-methods">
                <span class="pay-badge">RAMBURS</span>
                <span class="pay-badge">VISA</span>
                <span class="pay-badge">MASTERCARD</span>
            </div>
            
            <div class="anpc-links">
                <a href="https://anpc.ro/ce-este-sal/" target="_blank" class="anpc-btn">ANPC - SAL</a>
                <a href="https://ec.europa.eu/consumers/odr" target="_blank" class="anpc-btn">ANPC - SOL</a>
            </div>
        </div>
    </div>
</footer>