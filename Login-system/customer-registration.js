const qs = s => document.querySelector(s);
const registerForm = qs('#registerForm');
const regMsg = qs('#regMsg');

// API Base URL - use absolute path from root
const API_BASE_URL = '/Online-Shoes-Store/Backend/api';

function validateEmail(e){
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
}

function show(text, ok = true){
  if(!regMsg) return;
  regMsg.textContent = text;
  regMsg.className = 'message ' + (ok ? 'success' : 'error');
}

// Register handler with backend API
if(registerForm){
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const nameInput = qs('#name');
    const emailInput = qs('#email');
    const phoneInput = qs('#phone');
    const passwordInput = qs('#password');
    const confirmInput = qs('#confirmPassword');

    const fullName = (nameInput?.value || '').trim();
    const email = (emailInput?.value || '').trim();
    const phone = (phoneInput?.value || '').trim();
    const password = passwordInput?.value || '';
    const confirmPassword = confirmInput?.value || '';

    // Validation
    if(!fullName || !email || !password || !confirmPassword){
      show('Please fill all required fields', false);
      return;
    }

    if(!validateEmail(email)){
      show('Enter a valid email', false);
      return;
    }

    if(password.length < 8){
      show('Password must be at least 8 characters', false);
      return;
    }

    if(password !== confirmPassword){
      show('Passwords do not match', false);
      return;
    }

    try {
      show('Creating account...', true);

      // Split full name into first and last name
      const nameParts = fullName.split(' ');
      const firstName = nameParts[0];
      const lastName = nameParts.slice(1).join(' ') || 'User';

      const response = await fetch(`${API_BASE_URL}/auth.php?request=register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          email: email,
          first_name: firstName,
          last_name: lastName,
          phone: phone || '',
          password: password,
          password_confirm: confirmPassword,
          role: 'customer'
        })
      });

      const data = await response.json();

      if(data.success){
        show('Account created successfully! Redirecting to login...', true);
        setTimeout(() => {
          window.location.href = '../index.html';
        }, 1500);
      } else if(data.errors){
        // Show validation errors
        const errorMessages = Object.values(data.errors).join(', ');
        show(errorMessages, false);
      } else {
        show(data.message || 'Registration failed', false);
      }
    } catch(error){
      console.error('Registration error:', error);
      show('Network error. Please try again: ' + error.message, false);
    }
  });
}