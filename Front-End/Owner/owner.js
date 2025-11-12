// Owner product & site controls manager (localStorage + storage events)

const $ = id => document.getElementById(id);

const maintenance = $("maintenance");
const bannerText = $("bannerText");
const setBanner = $("setBanner");
const broadcastText = $("broadcastText");
const sendBroadcast = $("sendBroadcast");
const clearControls = $("clearControls");
const openCustomer = $("openCustomer");
const status = $("status");

const productListEl = $("productList");
const addProductBtn = $("addProductBtn");
const importDemo = $("importDemo");
const modal = $("modal");
const modalTitle = $("modalTitle");
const p_name = $("p_name");
const p_price = $("p_price");
const p_image = $("p_image");
const p_imageUrl = $("p_imageUrl");
const p_desc = $("p_desc");
const saveProduct = $("saveProduct");
const cancel = $("cancel");
const imagePreview = $("imagePreview");

let editingId = null;
let currentImageData = null;

function readControls(){ try { return JSON.parse(localStorage.getItem('owner_controls')||'{}'); } catch { return {}; } }
function saveControls(obj){
  const next = Object.assign(readControls(), obj);
  localStorage.setItem('owner_controls', JSON.stringify(next));
  flashStatus('Controls updated');
}
function readProducts(){ try { return JSON.parse(localStorage.getItem('products')||'[]'); } catch { return []; } }
function saveProducts(arr){ 
  localStorage.setItem('products', JSON.stringify(arr)); 
  flashStatus('Products updated');
  renderProducts();
}

function flashStatus(t){
  status.textContent = t;
  setTimeout(()=> status.textContent = 'Changes propagate to customers in other tabs.', 1500);
}

/* Controls */
maintenance.addEventListener('change', ()=> saveControls({ maintenance: maintenance.checked }));
setBanner.addEventListener('click', ()=> { saveControls({ banner: bannerText.value||'' }); bannerText.value=''; });
sendBroadcast.addEventListener('click', ()=> {
  const txt = broadcastText.value.trim();
  if(!txt) return;
  saveControls({ broadcast: { text: txt, ts: Date.now() } });
  broadcastText.value='';
});
clearControls.addEventListener('click', ()=> { localStorage.removeItem('owner_controls'); flashStatus('Controls cleared'); });

openCustomer.addEventListener('click', ()=> window.open('../../Front-End/customer-dashboard.html','_blank'));

/* Image upload handling */
p_image.addEventListener('change', (e)=>{
  const file = e.target.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = (evt)=> {
    currentImageData = evt.target.result;
    updateImagePreview(currentImageData);
  };
  reader.readAsDataURL(file);
});

p_imageUrl.addEventListener('change', ()=>{
  const url = p_imageUrl.value.trim();
  if(url) currentImageData = url;
  updateImagePreview(url);
});

function updateImagePreview(src){
  if(!src) {
    imagePreview.innerHTML = '<div style="color:#9aa7bf;text-align:center">No image selected</div>';
    return;
  }
  imagePreview.innerHTML = `<img src="${src}" alt="preview" onerror="this.parentElement.innerHTML='<div style=color:#9aa7bf>Invalid image</div>'">`;
}

/* Products rendering */
function renderProducts(){
  const products = readProducts();
  productListEl.innerHTML = products.map(p => `
    <div class="card" data-id="${p.id}">
      <div class="thumb">${p.image ? `<img src="${p.image}" alt="${escapeHtml(p.name)}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22%3E%3Crect fill=%22%23081826%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">` : '<div style="padding:8px;color:#9aa7bf">No image</div>'}</div>
      <div class="title">${escapeHtml(p.name)}</div>
      <div class="meta"><div class="price">रू ${p.price}</div><div class="actions">
        <button class="btn edit-btn" data-action="edit" data-id="${p.id}">Edit</button>
        <button class="btn danger remove-btn" data-action="remove" data-id="${p.id}">Remove</button>
      </div></div>
      <div class="desc">${escapeHtml(p.desc||'')}</div>
    </div>
  `).join('') || '<div class="small muted">No products. Add one.</div>';
  
  // rebind event listeners after rendering
  attachProductListeners();
}

function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

function attachProductListeners(){
  // Edit buttons
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', (e)=> {
      e.preventDefault();
      const id = btn.dataset.id;
      const p = readProducts().find(x=>x.id===id);
      if(!p) return;
      editingId = p.id;
      modalTitle.textContent = 'Edit product';
      p_name.value = p.name;
      p_price.value = p.price;
      p_imageUrl.value = '';
      p_desc.value = p.desc||'';
      currentImageData = p.image;
      updateImagePreview(p.image);
      showModal();
    });
  });

  // Remove buttons
  document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', (e)=> {
      e.preventDefault();
      const id = btn.dataset.id;
      const ok = confirm('Remove this product from customer dashboard?');
      if(!ok) return;
      const next = readProducts().filter(p=>p.id!==id);
      saveProducts(next);
    });
  });
}

/* Modal */
addProductBtn.addEventListener('click', ()=> {
  editingId = null;
  modalTitle.textContent='Add product';
  p_name.value='';
  p_price.value='';
  p_imageUrl.value='';
  p_desc.value='';
  p_image.value='';
  currentImageData = null;
  updateImagePreview(null);
  showModal();
});
cancel.addEventListener('click', hideModal);
function showModal(){ modal.classList.remove('hidden'); modal.setAttribute('aria-hidden','false'); }
function hideModal(){ modal.classList.add('hidden'); modal.setAttribute('aria-hidden','true'); }

/* Save product */
saveProduct.addEventListener('click', ()=>{
  const name = p_name.value.trim();
  const price = parseFloat(p_price.value) || 0;
  const desc = p_desc.value.trim();
  if(!name) { alert('Name required'); return; }
  if(!currentImageData) { alert('Image required'); return; }
  const products = readProducts();
  if(editingId){
    const idx = products.findIndex(p=>p.id===editingId);
    if(idx>=0){ products[idx] = Object.assign(products[idx], { name, price, image: currentImageData, desc }); }
  } else {
    products.unshift({ id: Date.now().toString(36), name, price, image: currentImageData, desc });
  }
  saveProducts(products);
  hideModal();
});

/* Demo import */
importDemo.addEventListener('click', ()=>{
  const demo = [
    { id: 'd1', name:'NIKE AIR 1', price:4999, image:'./Images/Nike Air 1.jpeg', desc:'Lightweight running shoe.' },
    { id: 'd2', name:'PUMA RUN 3', price:3899, image:'./Images/Puma run 3.jpeg', desc:'Everyday trainer.' }
  ];
  const merged = demo.concat(readProducts());
  saveProducts(merged);
});

/* init UI from storage */
(function init(){
  const c = readControls();
  maintenance.checked = !!c.maintenance;
  renderProducts();
})();

/* listen to external changes */
window.addEventListener('storage', (e)=>{
  if(e.key === 'products' || e.key === 'owner_controls') renderProducts();
});