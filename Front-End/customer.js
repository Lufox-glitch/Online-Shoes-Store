<script>
  // Cart system
  const cart = {
    items: [],
    load(){
      try { this.items = JSON.parse(localStorage.getItem('cart')||'[]'); } catch { this.items = []; }
    },
    save(){ localStorage.setItem('cart', JSON.stringify(this.items)); },
    add(product){
      const exist = this.items.find(x => x.id === product.id);
      if(exist) { exist.qty++; }
      else { this.items.push({...product, qty: 1}); }
      this.save();
      this.render();
    },
    remove(id){ this.items = this.items.filter(x => x.id !== id); this.save(); this.render(); },
    updateQty(id, qty){ 
      const item = this.items.find(x => x.id === id); 
      if(item) { 
        item.qty = Math.max(1, parseInt(qty) || 1); 
        this.save(); 
        this.render(); 
      } 
    },
    getTotal(){ return this.items.reduce((sum, item) => sum + (item.price * item.qty), 0); },
    render(){
      const cartItemsEl = document.getElementById('cartItems');
      const cartCount = document.getElementById('cartCount');
      const cartTotal = document.getElementById('cartTotal');
      
      if(this.items.length === 0){
        cartItemsEl.innerHTML = '<div class="cart-empty">Your cart is empty</div>';
        cartCount.style.display = 'none';
      } else {
        cartItemsEl.innerHTML = this.items.map(item => `
          <div class="cart-item">
            <div class="cart-item-info">
              <div class="cart-item-name">${item.name}</div>
              <div class="cart-item-price">रू ${item.price.toLocaleString()}</div>
              <div class="cart-item-qty">
                <button onclick="cart.updateQty('${item.id}', ${item.qty - 1})">−</button>
                <span>${item.qty}</span>
                <button onclick="cart.updateQty('${item.id}', ${item.qty + 1})">+</button>
              </div>
            </div>
            <button class="cart-item-remove" onclick="cart.remove('${item.id}')">Remove</button>
          </div>
        `).join('');
        cartCount.textContent = this.items.length;
        cartCount.style.display = 'flex';
      }
      
      const total = this.getTotal();
      cartTotal.textContent = `रू ${total.toLocaleString()}`;
      updatePaymentDetails(total);
    }
  };

  let selectedPaymentMethod = null;
  let screenshotFile = null;

  // DOM elements - with null checks
  const cartBtn = document.getElementById('cartBtn');
  const cartSidebar = document.getElementById('cartSidebar');
  const cartClose = document.getElementById('cartClose');
  const checkoutBtn = document.getElementById('checkoutBtn');
  const paymentModal = document.getElementById('paymentModal');
  const deliveryModal = document.getElementById('deliveryModal');
  const cancelPayBtn = document.getElementById('cancelPayBtn');
  const confirmPayBtn = document.getElementById('confirmPayBtn');
  const cancelDeliveryBtn = document.getElementById('cancelDeliveryBtn');
  const submitOrderBtn = document.getElementById('submitOrderBtn');
  const screenshotInput = document.getElementById('screenshotInput');
  const screenshotSection = document.getElementById('screenshotSection');
  const successMessage = document.getElementById('successMessage');

  // Safe event listeners with null checks
  if(cartBtn) cartBtn.addEventListener('click', () => {
    if(cartSidebar) cartSidebar.classList.add('open');
  });
  
  if(cartClose) cartClose.addEventListener('click', () => {
    if(cartSidebar) cartSidebar.classList.remove('open');
  });

  if(checkoutBtn) checkoutBtn.addEventListener('click', () => {
    if(cart.items.length === 0) { showMessage('Cart is empty', 'error'); return; }
    if(paymentModal) paymentModal.classList.add('open');
    if(cartSidebar) cartSidebar.classList.remove('open');
  });

  // Payment method selection
  document.querySelectorAll('.payment-method').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('selected'));
      const codOption = document.querySelector('.cod-option');
      if(codOption) codOption.classList.remove('selected');
      btn.classList.add('selected');
      selectedPaymentMethod = btn.dataset.method;
      if(screenshotSection) screenshotSection.style.display = 'block';
    });
  });

  function selectPaymentMethod(el, method){
    document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('selected'));
    const codOption = document.querySelector('.cod-option');
    if(codOption) codOption.classList.remove('selected');
    el.classList.add('selected');
    selectedPaymentMethod = method;
    if(screenshotSection) screenshotSection.style.display = method === 'cod' ? 'none' : 'block';
  }

  // Screenshot upload
  if(screenshotInput) {
    screenshotInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if(!file) return;
      screenshotFile = file;
      const reader = new FileReader();
      reader.onload = (evt) => {
        const preview = document.getElementById('screenshotPreview');
        const filename = document.getElementById('screenshotFilename');
        if(preview) {
          preview.src = evt.target.result;
          preview.style.display = 'block';
        }
        if(filename) filename.textContent = file.name;
      };
      reader.readAsDataURL(file);
    });
  }

  // Confirm payment
  if(confirmPayBtn) {
    confirmPayBtn.addEventListener('click', () => {
      if(!selectedPaymentMethod) { showMessage('Select a payment method', 'error'); return; }
      if(selectedPaymentMethod !== 'cod' && !screenshotFile) { showMessage('Please upload payment screenshot', 'error'); return; }
      
      if(paymentModal) paymentModal.classList.remove('open');
      if(deliveryModal) deliveryModal.classList.add('open');
    });
  }

  if(cancelPayBtn) {
    cancelPayBtn.addEventListener('click', () => {
      if(paymentModal) paymentModal.classList.remove('open');
      selectedPaymentMethod = null;
      screenshotFile = null;
      document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('selected'));
      const codOption = document.querySelector('.cod-option');
      if(codOption) codOption.classList.remove('selected');
    });
  }

  if(cancelDeliveryBtn) {
    cancelDeliveryBtn.addEventListener('click', () => {
      if(deliveryModal) deliveryModal.classList.remove('open');
    });
  }

  // Submit order
  if(submitOrderBtn) {
    submitOrderBtn.addEventListener('click', () => {
      const name = document.getElementById('deliveryName')?.value.trim() || '';
      const phone = document.getElementById('deliveryPhone')?.value.trim() || '';
      const email = document.getElementById('deliveryEmail')?.value.trim() || '';
      const district = document.getElementById('deliveryDistrict')?.value.trim() || '';
      const city = document.getElementById('deliveryCity')?.value.trim() || '';
      const address = document.getElementById('deliveryAddress')?.value.trim() || '';
      const postal = document.getElementById('deliveryPostal')?.value.trim() || '';

      if(!name || !phone || !district || !city || !address) { 
        showMessage('Please fill all required fields', 'error'); 
        return; 
      }

      const cleanPhone = phone.replace(/\D/g, '');
      if(!/^\d{10}$/.test(cleanPhone)) { 
        showMessage('Invalid phone number (must be 10 digits)', 'error'); 
        return; 
      }

      const order = {
        id: Date.now(),
        items: JSON.parse(JSON.stringify(cart.items)),
        total: cart.getTotal(),
        paymentMethod: selectedPaymentMethod,
        screenshot: screenshotFile ? screenshotFile.name : null,
        delivery: { name, phone, email, district, city, address, postal },
        date: new Date().toLocaleString(),
        status: 'Pending'
      };

      let orders = [];
      try { orders = JSON.parse(localStorage.getItem('orders')||'[]'); } catch {}
      orders.push(order);
      localStorage.setItem('orders', JSON.stringify(orders));

      cart.items = [];
      cart.save();
      cart.render();

      if(deliveryModal) deliveryModal.classList.remove('open');
      if(paymentModal) paymentModal.classList.remove('open');

      const method = selectedPaymentMethod === 'cod' ? 'Cash on Delivery' : (selectedPaymentMethod === 'esewa' ? 'Esewa' : 'Khalti');
      showMessage(`✓ Order placed!\nPayment: ${method}\nOrder #${order.id}\nDelivery to: ${city}, ${district}`, 'success');

      document.getElementById('deliveryName').value = '';
      document.getElementById('deliveryPhone').value = '';
      document.getElementById('deliveryEmail').value = '';
      document.getElementById('deliveryDistrict').value = '';
      document.getElementById('deliveryCity').value = '';
      document.getElementById('deliveryAddress').value = '';
      document.getElementById('deliveryPostal').value = '';
      selectedPaymentMethod = null;
      screenshotFile = null;
    });
  }

  function updatePaymentDetails(subtotal){
    const tax = subtotal * 0.13;
    const total = subtotal + tax;
    const paySubtotal = document.getElementById('paySubtotal');
    const payTax = document.getElementById('payTax');
    const payTotal = document.getElementById('payTotal');
    
    if(paySubtotal) paySubtotal.textContent = `रू ${subtotal.toLocaleString()}`;
    if(payTax) payTax.textContent = `रू ${Math.round(tax).toLocaleString()}`;
    if(payTotal) payTotal.textContent = `रू ${Math.round(total).toLocaleString()}`;
  }

  function showMessage(msg, type = 'success'){
    if(!successMessage) return;
    successMessage.textContent = msg;
    successMessage.className = `success-message show ${type === 'error' ? 'error' : ''}`;
    setTimeout(() => successMessage.classList.remove('show'), 4000);
  }

  // Init
  cart.load();
  cart.render();
</script>