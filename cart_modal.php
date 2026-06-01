<style>
    /* --- STILURI MODAL COȘ --- */
    .cart-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 5000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
    .cart-modal-content { background-color: #1e1e1e; width: 95%; max-width: 550px; border-radius: 12px; border: 1px solid #8e44ad; box-shadow: 0 0 40px rgba(142, 68, 173, 0.4); display: flex; flex-direction: column; max-height: 85vh; animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
    .cart-modal-header { padding: 15px 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; background-color: #252525; border-radius: 12px 12px 0 0; }
    .cart-modal-header h2 { margin: 0; font-size: 18px; color: #fff; }
    .cart-close-btn { cursor: pointer; color: #aaa; font-size: 24px; transition: 0.2s; }
    .cart-close-btn:hover { color: #fff; transform: scale(1.1); }
    
    .cart-modal-body { padding: 20px; overflow-y: auto; flex: 1; }
    
    .cart-modal-footer { padding: 20px; border-top: 1px solid #333; background-color: #252525; border-radius: 0 0 12px 12px; }
    
    /* Zona Promo */
    .promo-box { display: flex; gap: 10px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #444; }
    .promo-box input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #444; background: #121212; color: #fff; font-weight: bold; text-transform: uppercase; outline: none; }
    .promo-box input:focus { border-color: #9b59b6; }
    .promo-box button { background: #8e44ad; color: #fff; border: none; padding: 0 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .promo-box button:hover { background: #9b59b6; }
    .active-promo { background: #27ae60; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: bold; display: flex; justify-content: space-between; margin-bottom: 20px; }
    .active-promo span { cursor: pointer; text-decoration: underline; }

    /* Zona Total */
    .cart-totals-container { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
    .cart-subtotal-row { display: flex; justify-content: space-between; font-size: 15px; color: #aaa; }
    .cart-discount-row { display: flex; justify-content: space-between; font-size: 15px; color: #e74c3c; font-weight: bold; }
    .cart-total-row { display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; color: #fff; border-top: 1px solid #444; padding-top: 10px; margin-top: 5px; }
    
    .checkout-btn { width: 100%; padding: 15px; background-color: #27ae60; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3); }
    .checkout-btn:hover { background-color: #2ecc71; transform: translateY(-2px); }
</style>

<div class="cart-modal-overlay" id="cartModal" onclick="closeModalOnOutsideClick(event)">
    <div class="cart-modal-content">
        <div class="cart-modal-header">
            <h2>Coșul tău de cumpărături</h2>
            <div class="cart-close-btn" onclick="toggleCartModal()">✖</div>
        </div>
        
        <div class="cart-modal-body" id="cartItemsContainer">
            <p style="text-align:center; color:#888;">Coșul este gol.</p>
        </div>

        <div class="cart-modal-footer" id="cartFooterSection" style="display: none;">
            
            <div id="promoInputArea" class="promo-box">
                <input type="text" id="promoCodeInput" placeholder="Ai un cod de reducere?">
                <button onclick="applyPromo()">Aplică</button>
            </div>

            <div id="promoActiveArea" class="active-promo" style="display: none;">
                <div>✔ Cod <strong id="activeCodeName"></strong> aplicat!</div>
                <span onclick="removePromo()">Șterge</span>
            </div>

            <div class="cart-totals-container">
                <div class="cart-subtotal-row" id="subtotalRow">
                    <span>Subtotal:</span>
                    <span id="cartSubtotalText">0 RON</span>
                </div>
                <div class="cart-discount-row" id="discountRow" style="display: none;">
                    <span>Reducere (10%):</span>
                    <span id="cartDiscountText">- 0 RON</span>
                </div>
                <div class="cart-total-row">
                    <span>Total de plată:</span>
                    <span id="cartFinalTotal" style="color: #9b59b6;">0 RON</span>
                </div>
            </div>

            <button class="checkout-btn" onclick="window.location.href='checkout.php'">Finalizează Comanda ➔</button>
        </div>
    </div>
</div>

<script>
    function toggleCartModal() {
        const modal = document.getElementById('cartModal');
        if (modal.style.display === 'flex') modal.style.display = 'none';
        else { modal.style.display = 'flex'; refreshCart(); }
    }
    function closeModalOnOutsideClick(e) { if (e.target.id === 'cartModal') toggleCartModal(); }

    async function addToCart(id) {
        try {
            const res = await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'add', id: id }) });
            const data = await res.json();
            if (data.status === 'success') {
                refreshCart(); 
                document.getElementById('cartModal').style.display = 'flex';
            } else if (data.status === 'error') alert(data.message);
        } catch (e) {}
    }
    async function decreaseQty(id) {
        await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'decrease', id: id }) }); refreshCart();
    }
    async function removeFromCart(id) {
        await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'remove', id: id }) }); refreshCart();
    }

    // --- FUNCTII PROMO CODE ---
    async function applyPromo() {
        const code = document.getElementById('promoCodeInput').value;
        if(!code) return;
        const res = await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'apply_promo', code: code }) });
        const data = await res.json();
        if(data.status === 'success') { refreshCart(); } 
        else { alert(data.message); }
    }
    async function removePromo() {
        await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'remove_promo' }) }); refreshCart();
    }

    async function refreshCart() {
        try {
            const res = await fetch('cart_actions.php', { method: 'POST', body: JSON.stringify({ action: 'get' }) });
            const data = await res.json();
            
            // Header
            if(document.getElementById('cartCount')) document.getElementById('cartCount').textContent = data.count;
            if(document.getElementById('cartTotal')) document.getElementById('cartTotal').textContent = data.final_total + ' RON';

            const container = document.getElementById('cartItemsContainer');
            const footer = document.getElementById('cartFooterSection');
            if(!container) return;
            container.innerHTML = '';

            // Daca e gol cosul
            if (data.products.length === 0) {
                container.innerHTML = '<p style="text-align:center; color:#888; margin-top:20px; font-size:18px;">Coșul tău este gol 🛒</p>';
                footer.style.display = 'none'; // Ascundem footer-ul cand e gol
                return;
            }

            footer.style.display = 'block';

            // Desenam produsele
            data.products.forEach(prod => {
                container.innerHTML += `
                    <div class="cart-item" style="display:flex; align-items:center; gap:15px; border-bottom:1px solid #333; padding-bottom:20px; margin-bottom:20px;">
                        <img src="${prod.imagine_url}" style="width:70px; height:70px; object-fit:cover; border-radius:8px; border:1px solid #444;">
                        <div class="cart-item-details" style="flex:1;">
                            <div class="cart-item-title" style="font-weight:bold; font-size:15px; margin-bottom:10px; color:#fff; line-height: 1.3;">${prod.nume}</div>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <div style="display:flex; align-items:center; background:#252525; border:1px solid #444; border-radius:6px; overflow:hidden;">
                                    <button onclick="decreaseQty(${prod.id})" style="background:transparent; color:#ccc; border:none; border-right:1px solid #444; padding:5px 12px; cursor:pointer; font-size:16px;">-</button>
                                    <span style="color:#fff; font-weight:bold; padding:0 12px; width:20px; text-align:center;">${prod.qty}</span>
                                    <button onclick="addToCart(${prod.id})" style="background:transparent; color:#ccc; border:none; border-left:1px solid #444; padding:5px 12px; cursor:pointer; font-size:16px;">+</button>
                                </div>
                                <div class="cart-item-price" style="font-size:13px; color:#aaa;">x ${prod.pret_format} RON</div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:10px;">
                            <div class="cart-item-total" style="font-weight:bold; color:#9b59b6; font-size:16px;">${prod.subtotal} RON</div>
                            <button onclick="removeFromCart(${prod.id})" style="background:none; border:none; color:#e74c3c; cursor:pointer; text-decoration:underline; font-size:12px; padding:0; margin-top:5px;">Șterge</button>
                        </div>
                    </div>`;
            });

            // LOGICA AFISARE TOTALURI SI REDUCERI
            document.getElementById('cartSubtotalText').textContent = data.total + ' RON';
            document.getElementById('cartFinalTotal').textContent = data.final_total + ' RON';

            if (data.promo_code !== '') {
                // Avem cod activ
                document.getElementById('promoInputArea').style.display = 'none';
                document.getElementById('promoActiveArea').style.display = 'flex';
                document.getElementById('activeCodeName').textContent = data.promo_code;
                
                document.getElementById('discountRow').style.display = 'flex';
                document.getElementById('cartDiscountText').textContent = '- ' + data.discount + ' RON';
                document.getElementById('subtotalRow').style.textDecoration = 'line-through';
            } else {
                // Nu avem cod activ
                document.getElementById('promoInputArea').style.display = 'flex';
                document.getElementById('promoActiveArea').style.display = 'none';
                document.getElementById('promoCodeInput').value = '';
                
                document.getElementById('discountRow').style.display = 'none';
                document.getElementById('subtotalRow').style.textDecoration = 'none';
            }

        } catch (error) { console.error('Eroare refresh:', error); }
    }

    document.addEventListener('DOMContentLoaded', refreshCart);
</script>