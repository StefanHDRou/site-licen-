const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');

const app = express();
app.use(cors()); // Permite accesul din browser
app.use(express.json());

// 1. Conexiune WampServer
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '', 
    database: 'firma_db' 
});

db.connect(err => {
    if (err) {
        console.error('❌ Eroare conexiune MySQL:', err.message);
        // Nu oprim serverul, ca sa vedem eroarea in consola
        return; 
    }
    console.log('✅ Conectat la baza de date MySQL!');
});

// 2. Ruta pentru PRODUSE (Asta lipsea sau era gresita)
app.get('/api/produse', (req, res) => {
    // Selectăm toate produsele
    const sql = "SELECT * FROM produse";
    
    db.query(sql, (err, results) => {
        if (err) {
            console.error("Eroare interogare:", err);
            return res.status(500).json({ error: "Eroare la baza de date" });
        }
        res.json(results);
    });
});

// 3. Pornire Server
app.listen(3006, () => {
    console.log("🚀 Serverul Backend rulează pe http://localhost:3000");
});