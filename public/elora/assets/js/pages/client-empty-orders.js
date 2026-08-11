(function () {
  function openMenu() { document.body.classList.add('menu-open'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { document.body.classList.remove('menu-open'); document.body.style.overflow = ''; }
  function showToast(msg, color = '#171717') {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.style.background = color;
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 2500);
  }

  window.openMenu = openMenu;
  window.closeMenu = closeMenu;
  window.showToast = showToast;
})();
