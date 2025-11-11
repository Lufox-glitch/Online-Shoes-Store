// ...existing code...
// Cleaned search + menu handlers (single, non-duplicated implementation)

const closeBtn = document.querySelector(".close");
const openBtn = document.querySelector(".ham");
const menu = document.querySelector(".menu");

// Menu toggle (keeps previous behavior)
if (openBtn && menu) {
  openBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    menu.classList.add("open");
    menu.setAttribute("aria-hidden", "false");
  });
}
if (closeBtn && menu) {
  closeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    menu.classList.remove("open");
    menu.setAttribute("aria-hidden", "true");
  });
}
document.addEventListener("click", (e) => {
  if (!menu || !menu.classList.contains("open")) return;
  if (!menu.contains(e.target) && openBtn && !openBtn.contains(e.target)) {
    menu.classList.remove("open");
    menu.setAttribute("aria-hidden", "true");
  }
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && menu && menu.classList.contains("open")) {
    menu.classList.remove("open");
    menu.setAttribute("aria-hidden", "true");
  }
});

// --- Search implementation (single, reliable) ---
const searchInput = document.getElementById("input");
const searchButton = document.querySelector(".search-btn") || document.querySelector(".search .s");
const productItems = Array.from(document.querySelectorAll(".items"));
const productsContainer = document.querySelector(".container");

function debounce(fn, delay = 180) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function clearSearch() {
  productItems.forEach(it => {
    it.style.display = "";
    it.classList.remove("hidden-by-search");
  });
  const no = document.querySelector(".no-results");
  if (no) no.remove();
}

function showNoResults() {
  if (!productsContainer) return;
  if (document.querySelector(".no-results")) return;
  const msg = document.createElement("div");
  msg.className = "no-results";
  msg.textContent = "No products found.";
  msg.style.cssText = "padding:18px;text-align:center;color:#555;";
  productsContainer.appendChild(msg);
}

function performSearch(query) {
  if (!productItems.length) return;
  const q = (typeof query === "string" ? query : (searchInput?.value || "")).trim().toLowerCase();

  if (!q) {
    clearSearch();
    return;
  }

  let anyMatch = false;
  productItems.forEach(it => {
    const title = (it.querySelector(".name")?.textContent || "").toLowerCase();
    const info = (it.querySelector(".info")?.textContent || "").toLowerCase();
    const price = (it.querySelector(".price")?.textContent || "").toLowerCase();
    const match = title.includes(q) || info.includes(q) || price.includes(q);

    if (match) {
      it.style.display = "";
      it.classList.remove("hidden-by-search");
      anyMatch = true;
    } else {
      it.style.display = "none";
      it.classList.add("hidden-by-search");
    }
  });

  if (!anyMatch) showNoResults();
  else {
    const existing = document.querySelector(".no-results");
    if (existing) existing.remove();
  }
}

const debouncedPerformSearch = debounce(performSearch, 200);

// input interactions
if (searchInput) {
  searchInput.addEventListener("input", debouncedPerformSearch);
  searchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      performSearch();
    } else if (e.key === "Escape") {
      searchInput.value = "";
      clearSearch();
    }
  });
}

// button click
if (searchButton) {
  searchButton.addEventListener("click", (e) => {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    performSearch();
  });
}
// ...existing code...