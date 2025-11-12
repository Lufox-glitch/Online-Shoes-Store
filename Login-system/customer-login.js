/* filepath: /Users/prashnawaibatamang/Online Shoes Store/Online-Shoes-Store-1/Online Shoes Store/Login-system/customer-login.js */
const qs = s => document.querySelector(s);
const loginForm = qs('#loginForm');
const msg = qs('#msg');
const togglePwd = qs('#togglePwd');
const pwdInput = qs('#password');

const hash = s => btoa(s);

const getUsers = () => JSON.parse(localStorage.getItem('users_customer') || '[]');
const setUsers = u => localStorage.setItem('users_customer', JSON.stringify(u));
const show = (text, ok = true) => {
  if(!msg) return;
  msg.textContent = text;
  msg.className = 'message ' + (ok ? 'success' : 'error');
};

const DEMO_EMAIL = 'sandeshnapit123@gmail.com';
const DEMO_PWD = 'sandesh123'; 

// Create demo account on page load
function initDemoAccount(){
  const users = getUsers();
  const demoExists = users.find(u => u.email === DEMO_EMAIL.toLowerCase());
  
  if(!demoExists){
    const demoUser = {
      id: Date.now(),
      name: 'Sandesh Napit', // CHANGED
      email: DEMO_EMAIL.toLowerCase(),
      pwd: hash(DEMO_PWD),
      role: 'customer',
      created: new Date().toISOString()
    };
    users.push(demoUser);
    setUsers(users);
    console.log('✓ Demo account created');
  } else {
    console.log('✓ Demo account already exists');
  }
}

// Toggle password visibility
if(togglePwd && pwdInput){
  togglePwd.addEventListener('click', () => {
    const isPwd = pwdInput.getAttribute('type') === 'password';
    pwdInput.setAttribute('type', isPwd ? 'text' : 'password');
    togglePwd.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
  });
}

function validateEmail(e){ 
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); 
}

// Login handler
if(loginForm){
  loginForm.addEventListener('submit', e => {
    e.preventDefault();
    const email = qs('#email').value.trim().toLowerCase();
    const password = qs('#password').value;
    
    if(!validateEmail(email) || password.length < 1){
      show('Enter valid credentials', false);
      return;
    }

    // Check if demo account
    if(email === DEMO_EMAIL.toLowerCase() && password === DEMO_PWD){
      const remember = qs('#remember').checked;
      const current = { role: 'customer', email: DEMO_EMAIL, name: 'Sandesh Napit' }; 
      
      if(remember){
        localStorage.setItem('currentUser', JSON.stringify(current));
      } else {
        sessionStorage.setItem('currentUser', JSON.stringify(current));
      }

      show(`Welcome back, Sandesh Napit!`); 
      
      setTimeout(() => {
        location.href = './Front-End/customer-dashboard.html';
      }, 700);
      return;
    }

    // Check regular users
    const users = getUsers();
    const user = users.find(x => x.email === email && x.pwd === hash(password));
    
    if(!user){
      show('Invalid credentials', false);
      return;
    }

    // Success - save user
    const remember = qs('#remember').checked;
    const current = { role: 'customer', email: user.email, name: user.name || 'Customer' };
    
    if(remember){
      localStorage.setItem('currentUser', JSON.stringify(current));
    } else {
      sessionStorage.setItem('currentUser', JSON.stringify(current));
    }

    show(`Welcome back, ${current.name}!`);
    
    setTimeout(() => {
      location.href = 'd./Front-End/customer-dashboard.html';
    }, 700);
  });
}

// Initialize demo account when page loads
document.addEventListener('DOMContentLoaded', initDemoAccount);
// Also try immediately
initDemoAccount();