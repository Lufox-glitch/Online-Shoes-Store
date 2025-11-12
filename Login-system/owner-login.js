// Simple owner login

const demo = { email: "prashantwaiba123@gmail.com", password: "prashant123" };

const $ = id => document.getElementById(id);
const loginForm = $("loginForm");
const emailIn = $("email");
const pwdIn = $("password");
const togglePwd = $("togglePwd");
const msg = $("message");
const remember = $("remember");
const forgotLink = $("forgotLink");

function showMessage(text, type = "") {
  msg.textContent = text;
  msg.className = "message " + (type || "");
}

togglePwd.addEventListener("click", () => {
  const t = pwdIn.type === "password" ? "text" : "password";
  pwdIn.type = t;
  togglePwd.textContent = t === "text" ? "Hide" : "Show";
});

loginForm.addEventListener("submit", (e) => {
  e.preventDefault();
  showMessage("", "");
  const email = emailIn.value.trim();
  const pwd = pwdIn.value;

  if (!email || !pwd) return showMessage("Please fill in all fields.", "error");

  // fake auth
  if (email.toLowerCase() === demo.email && pwd === demo.password) {
    showMessage("Welcome back to P&S Online Shoes Store — redirecting to owner dashboard...", "success");
    if (remember.checked) localStorage.setItem("owner_remember", email);
    else localStorage.removeItem("owner_remember");

    // redirect to owner dashboard
    setTimeout(() => {
      window.location.href = "../Front-End/Owner/owner-dashboard.html";
    }, 700);
  } else {
    showMessage("Invalid credentials.", "error");
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

// Forgot link convenience
forgotLink.addEventListener("click", (e) => {
  e.preventDefault();
  alert("Demo credentials:\nprashantwaiba123@gmail.com\nprashant123");
});

