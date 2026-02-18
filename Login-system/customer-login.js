const qs = s => document.querySelector(s);
const loginForm = qs('#loginForm');
const msg = qs('#loginMsg');
const pwdInput = qs('#password');

// API Base URL - use absolute path from root
const API_BASE_URL = '/Online-Shoes-Store/Backend/api';

const show = (text, type = 'success') => {
  if(!msg) return;
  msg.textContent = text;
  msg.className = 'message ' + type;
  msg.style.display = 'block';
};

// Login handler with backend API
if(loginForm){
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = qs('#email').value.trim();
    const password = qs('#password').value;
    const rememberMe = qs('#remember').checked;
    
    if(!email || !password){
      show('Please enter email and password', 'error');
      return;
    }

    try {
      show('Logging in...', 'success');
      
      const response = await fetch(`${API_BASE_URL}/auth.php?request=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          email: email,
          password: password,
          remember_me: rememberMe
        })
      });

      const data = await response.json();

      if(data.success){
        // Clear any old owner data first
        localStorage.removeItem('user');
        localStorage.removeItem('currentOwner');
        
        // Store user info
        const userInfo = {
          id: data.data.user_id,
          email: data.data.email,
          first_name: data.data.first_name,
          last_name: data.data.last_name,
          role: data.data.role
        };
        
        if(rememberMe){
          localStorage.setItem('currentUser', JSON.stringify(userInfo));
        } else {
          sessionStorage.setItem('currentUser', JSON.stringify(userInfo));
        }

        show(`Welcome back, ${data.data.first_name}!`, 'success');
        
        setTimeout(() => {
          window.location.href = '/Online-Shoes-Store/Front-End/customer-dashboard.html';
        }, 800);
      } else {
        show(data.message || 'Login failed', 'error');
      }
    } catch(error){
      console.error('Login error:', error);
      show('Network error. Please try again: ' + error.message, 'error');
    }
  });
}