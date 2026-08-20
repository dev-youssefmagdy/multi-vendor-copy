(function () {
  function openMenu() { document.body.classList.add('menu-open'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { document.body.classList.remove('menu-open'); document.body.style.overflow = ''; }

  function showToast(msg, color = '#171717') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = color;
    t.classList.add('show');
    clearTimeout(t._tm);
    t._tm = setTimeout(() => t.classList.remove('show'), 2500);
  }

  function removeFavorite(btn) {
    const card = btn.closest('.fav-card');
    card.classList.add('removing');
    setTimeout(() => {
      card.remove();
      updateCount();
      checkEmpty();
      showToast('❌ Removed from favorites', '#555');
    }, 350);
  }

  function addToCart(btn) {
    const orig = btn.innerHTML;
    btn.style.background = '#2AAF2F';
    btn.innerHTML = '<svg style="width:18px;height:18px" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
    const badge = document.getElementById('cartBadge');
    if (badge) badge.textContent = parseInt(badge.textContent || 0) + 1;
    showToast('🛒 Added to cart!', '#2AAF2F');
    setTimeout(() => { btn.style.background = ''; btn.innerHTML = orig; }, 1500);
  }

  function addAllToCart() {
    const cards = document.querySelectorAll('.fav-card');
    const badge = document.getElementById('cartBadge');
    if (badge) badge.textContent = parseInt(badge.textContent || 0) + cards.length;
    showToast(`🛒 All ${cards.length} items added to cart!`, '#2AAF2F');
  }

  function updateCount() {
    const n = document.querySelectorAll('.fav-card').length;
    document.getElementById('favNum').textContent = n;
  }

  function checkEmpty() {
    const n = document.querySelectorAll('.fav-card').length;
    const grid = document.getElementById('favGrid');
    const empty = document.getElementById('emptyState');
    if (n === 0) {
      grid.style.display = 'none';
      empty.classList.add('show');
      document.getElementById('favCount').style.display = 'none';
    }
  }

  function filterFavorites() {
    const q = document.getElementById('favSearch').value.toLowerCase().trim();
    document.querySelectorAll('.fav-card').forEach(card => {
      const name = card.querySelector('.text-base.font-medium')?.textContent?.toLowerCase() || '';
      card.style.display = !q || name.includes(q) ? '' : 'none';
    });
  }

  function sortFavorites(type) {
    document.querySelectorAll('.sort-btn').forEach(b => {
      b.classList.remove('bg-main', 'text-white', 'border-main', 'font-medium');
      b.classList.add('bg-white', 'text-[#555]', 'border-[#E0E0E0]');
    });
    const active = document.getElementById('sort-' + type.replace('-', '_') || 'sort-' + type);
    if (active) {
      active.classList.add('bg-main', 'text-white', 'border-main', 'font-medium');
      active.classList.remove('bg-white', 'text-[#555]');
    }

    const grid = document.getElementById('favGrid');
    const cards = [...grid.querySelectorAll('.fav-card')];

    cards.sort((a, b) => {
      const priceA = parseFloat(a.querySelector('.text-lg.font-medium')?.textContent?.replace('$', '') || 0);
      const priceB = parseFloat(b.querySelector('.text-lg.font-medium')?.textContent?.replace('$', '') || 0);
      const nameA = a.querySelector('.text-base.font-medium')?.textContent?.trim() || '';
      const nameB = b.querySelector('.text-base.font-medium')?.textContent?.trim() || '';
      if (type === 'price-asc') return priceA - priceB;
      if (type === 'price-desc') return priceB - priceA;
      if (type === 'name') return nameA.localeCompare(nameB);
      return parseInt(a.dataset.index || 0) - parseInt(b.dataset.index || 0);
    });

    cards.forEach(c => grid.appendChild(c));
  }

  window.openMenu = openMenu;
  window.closeMenu = closeMenu;
  window.showToast = showToast;
  window.removeFavorite = removeFavorite;
  window.addToCart = addToCart;
  window.addAllToCart = addAllToCart;
  window.updateCount = updateCount;
  window.checkEmpty = checkEmpty;
  window.filterFavorites = filterFavorites;
  window.sortFavorites = sortFavorites;
})();
