/* filepath: /Users/prashnawaibatamang/Online Shoes Store/Online-Shoes-Store-1/Online Shoes Store/Login-system/customer-login.js */
/* ...existing code... */
const qs = s => document.querySelector(s);
const loginForm = qs('#loginForm');
const msg = qs('#msg');
const togglePwd = qs('#togglePwd');
const pwdInput = qs('#password');

const hash = s => btoa(s);

const getUsers = () => JSON.parse(localStorage.getItem('users_customer') || '[]');
const setUsers = u => localStorage.setItem('users_customer', JSON.stringify(u));
const show = (text, ok = true) => {
  msg.textContent = text;
  msg.className = 'message ' + (ok ? 'success' : 'error');
};

// Demo credentials (reference only — demo creation removed)
const DEMO_EMAIL = 'sandeshnapit123@gmail.com';
const DEMO_PWD = 'sandesh123';

// Toggle password visibility
togglePwd.addEventListener('click', () => {
  const isPwd = pwdInput.getAttribute('type') === 'password';
  pwdInput.setAttribute('type', isPwd ? 'text' : 'password');
  togglePwd.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
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

  // Redirect to customer dashboard
  setTimeout(() => {
    location.href = './Front-End/customer-dashboard.html';
  }, 700);
});