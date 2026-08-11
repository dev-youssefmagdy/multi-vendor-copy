(function () {
  function openMenu() { document.body.classList.add('menu-open'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { document.body.classList.remove('menu-open'); document.body.style.overflow = ''; }

  function toggleItem(btn) {
    btn.classList.toggle('checked');
    const isChecked = btn.classList.contains('checked');
    btn.style.background = isChecked ? '#171717' : '';
    btn.style.borderColor = isChecked ? '#171717' : '';
    const svg = btn.querySelector('svg');
    if (svg) svg.innerHTML = isChecked ? '<path d="m1 4 4 4 7-7" stroke="white" stroke-width="2" stroke-linecap="round"/>' : '';
    updateTotals();
  }

  let allSelected = true;
  function toggleSelectAll() {
    allSelected = !allSelected;
    const box = document.getElementById('selectAllBox');
    document.querySelectorAll('.item-check').forEach(btn => {
      if (allSelected) {
        btn.classList.add('checked');
        btn.style.background = '#171717';
        btn.style.borderColor = '#171717';
        const svg = btn.querySelector('svg');
        if (svg) svg.innerHTML = '<path d="m1 4 4 4 7-7" stroke="white" stroke-width="2" stroke-linecap="round"/>';
      } else {
        btn.classList.remove('checked');
        btn.style.background = '';
        btn.style.borderColor = '';
        const svg = btn.querySelector('svg');
        if (svg) svg.innerHTML = '';
      }
    });
    box.style.background = allSelected ? '#171717' : 'white';
    updateTotals();
  }

  function removeItem(btn) {
    const row = btn.closest('.cart-item');
    row.style.opacity = '0';
    row.style.transform = 'translateX(40px)';
    row.style.transition = 'all .3s ease';
    setTimeout(() => { row.remove(); updateTotals(); }, 300);
  }

  function changeQty(btn, dir) {
    const bubble = btn.parentElement.querySelector('.qty-num');
    let val = parseInt(bubble.textContent) + dir;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    bubble.textContent = val;
    updateTotals();
  }

  const BASE_PRICES = [100, 200];
  function updateTotals() {
    let total = 0;
    document.querySelectorAll('.cart-item').forEach((item, i) => {
      const checked = item.querySelector('.item-check')?.classList.contains('checked');
      const qty = parseInt(item.querySelector('.qty-num')?.textContent || 1);
      if (checked) total += (BASE_PRICES[i] || 89) * qty;
    });
    document.getElementById('subtotalVal').textContent = '$' + (total + 150.50).toFixed(2);
    document.getElementById('totalVal').textContent = '$' + total.toFixed(2);
    const btn = document.getElementById('checkoutBtn');
    if (btn) btn.textContent = '$' + total.toFixed(2) + ' to checkout';
  }

  function checkout() {
    const btn = document.getElementById('checkoutBtn');
    const orig = btn.textContent;
    btn.style.background = '#2AAF2F';
    btn.textContent = '✓ Proceeding to checkout…';
    setTimeout(() => { btn.style.background = ''; btn.textContent = orig; }, 2500);
  }

  document.querySelectorAll('.heart-btn').forEach(b => b.addEventListener('click', function () { this.classList.toggle('liked'); }));

  document.querySelectorAll('.mini-cart-btn').forEach(b => {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      const orig = this.innerHTML;
      this.classList.add('bg-main');
      this.innerHTML = '<svg style="width:16px;height:16px" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
      const badge = document.getElementById('cartBadge');
      if (badge) badge.textContent = parseInt(badge.textContent || 0) + 1;
      setTimeout(() => { this.classList.remove('bg-main'); this.innerHTML = orig; }, 1500);
    });
  });

  updateTotals();

  window.openMenu = openMenu;
  window.closeMenu = closeMenu;
  window.toggleItem = toggleItem;
  window.toggleSelectAll = toggleSelectAll;
  window.removeItem = removeItem;
  window.changeQty = changeQty;
  window.updateTotals = updateTotals;
  window.checkout = checkout;
})();
