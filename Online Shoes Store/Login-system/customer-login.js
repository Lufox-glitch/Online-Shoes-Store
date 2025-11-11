/* filepath: /Users/prashnawaibatamang/Online Shoes Store/Online-Shoes-Store-1/Online Shoes Store/Login-system/customer-login.js */
/* ...existing code... */
// Demo client-side auth (localStorage) — not for production

const qs = s => document.querySelector(s);
const loginForm = qs('#loginForm');
const msg = qs('#msg');
const demoBtn = qs('#createDemo');
const togglePwd = qs('#togglePwd');
const pwdInput = qs('#password');

const hash = s => btoa(s);

const getUsers = () => JSON.parse(localStorage.getItem('users_customer') || '[]');
const setUsers = u => localStorage.setItem('users_customer', JSON.stringify(u));
const show = (text, ok = true) => {
  msg.textContent = text;
  msg.className = 'message ' + (ok ? 'success' : 'error');
};

// Toggle password visibility
togglePwd.addEventListener('click', () => {
  const isPwd = pwdInput.getAttribute('type') === 'password';
  pwdInput.setAttribute('type', isPwd ? 'text' : 'password');
  togglePwd.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
});

// Create demo account quickly
demoBtn.addEventListener('click', () => {
  const users = getUsers();
  const demoEmail = 'customer@test.com';
  if (!users.find(u => u.email === demoEmail)) {
    users.push({ name: 'Demo Customer', email: demoEmail, pwd: hash('test123') });
    setUsers(users);
    show('Demo created: customer@test.com / test123');
    return;
  }
  show('Demo account already exists', true);
});

// Validate simple email
function validateEmail(e){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e) }

// Login
loginForm.addEventListener('submit', e => {
  e.preventDefault();
  const email = qs('#email').value.trim().toLowerCase();
  const password = pwdInput.value;
  if (!validateEmail(email) || password.length < 3) { show('Enter valid credentials', false); return; }

  const users = getUsers();
  const u = users.find(x => x.email === email && x.pwd === hash(password));
  if (!u) { show('Invalid credentials', false); return; }

  // Save current user and optionally persist if "remember" checked
  const remember = qs('#remember').checked;
  const current = { role: 'customer', email: u.email, name: u.name || 'Customer' };
  if (remember) {
    localStorage.setItem('currentUser', JSON.stringify(current));
  } else {
    // session-only storage
    sessionStorage.setItem('currentUser', JSON.stringify(current));
  }

  show(`Welcome back, ${current.name}!`);

  // Redirect to customer dashboard (index.html).
  // If your dashboard is in a different folder, change the path below.
  setTimeout(() => {
    location.href = './Online Shoes Store/Login-system/customer-dashboard.html';
  }, 700);
});
// ...existing code...