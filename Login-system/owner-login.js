// Owner login with backend API

const $ = id => document.getElementById(id);
const loginForm = $("loginForm");
const emailIn = $("email");
const pwdIn = $("password");
const msg = $("loginMsg");
const remember = $("remember");

// API Base URL - use absolute path from root
const API_BASE_URL = '/Online-Shoes-Store/Backend/api';

function showMessage(text, type = "") {
  msg.textContent = text;
  msg.className = "message " + (type || "");
  if (type) msg.style.display = 'block';
}

loginForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  showMessage("", "");
  
  const email = emailIn.value.trim();
  const pwd = pwdIn.value;

  if (!email || !pwd) {
    return showMessage("Please fill in all fields.", "error");
  }

  try {
    showMessage("Logging in...", "");

    const response = await fetch(`${API_BASE_URL}/auth.php?request=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        email: email,
        password: pwd,
        remember_me: remember.checked
      })
    });

    const data = await response.json();

    if(data.success && data.data.role === 'owner'){
      // Store owner info
      const ownerInfo = {
        id: data.data.user_id,
        email: data.data.email,
        first_name: data.data.first_name,
        role: data.data.role
      };
      
      // Store in both locations for compatibility
      localStorage.setItem('user', JSON.stringify(ownerInfo));
      localStorage.setItem('currentOwner', JSON.stringify(ownerInfo));
      
      if(remember.checked){
        localStorage.setItem('owner_remember', email);
      } else {
        localStorage.removeItem('owner_remember');
      }

      showMessage(`Welcome back! Redirecting to owner dashboard...`, "success");
      
      setTimeout(() => {
        window.location.href = "/Online-Shoes-Store/Front-End/Owner/owner-dashboard.html";
      }, 800);
    } else if(data.success){
      showMessage('You do not have owner privileges. Please contact support.', 'error');
    } else {
      showMessage(data.message || 'Invalid credentials.', 'error');
    }
  } catch(error){
    console.error('Login error:', error);
    showMessage('Network error. Please try again: ' + error.message, 'error');
  }
});

// Populate remembered email
window.addEventListener("load", () => {
  const rem = localStorage.getItem("owner_remember");
  if (rem) {
    emailIn.value = rem;
    remember.checked = true;
  }
});


