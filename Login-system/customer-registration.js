const qs = s => document.querySelector(s);
const registerForm = qs('#registerForm');
const regMsg = qs('#regMsg');

const hash = s => btoa(s);
const getUsers = () => JSON.parse(localStorage.getItem('users_customer') || '[]');
const setUsers = u => localStorage.setItem('users_customer', JSON.stringify(u));

function validateEmail(e){
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
}

function show(text, ok = true){
  if(!regMsg) return;
  regMsg.textContent = text;
  regMsg.className = 'message ' + (ok ? 'success' : 'error');
}

registerForm.addEventListener('submit', e => {
  e.preventDefault();
  const name = qs('#name').value.trim();
  const email = qs('#email').value.trim().toLowerCase();
  const phone = qs('#phone').value.trim();
  const password = qs('#password').value;
  const confirm = qs('#confirmPassword').value;

  if(!name || !email || !password || !confirm){
    show('Please fill all required fields', false);
    return;
  }
  if(!validateEmail(email)){
    show('Enter a valid email', false);
    return;
  }
  if(password.length < 6){
    show('Password must be at least 6 characters', false);
    return;
  }
  if(password !== confirm){
    show('Passwords do not match', false);
    return;
  }

  const users = getUsers();
  if(users.find(u => u.email === email)){
    show('An account with this email already exists', false);
    return;
  }

  const user = {
    id: Date.now(),
    name,
    email,
    phone,
    pwd: hash(password),
    role: 'customer',
    created: new Date().toISOString()
  };

  users.push(user);
  setUsers(users);

  show('Account created. Redirecting to login...', true);
  setTimeout(() => {
    location.href = '../index.html';
  }, 900);
});