(function () {
  function openMenu() { document.body.classList.add('menu-open'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { document.body.classList.remove('menu-open'); document.body.style.overflow = ''; }

  function showCardForm(show) {
    const form = document.getElementById('cardForm');
    if (show) form.classList.add('open');
    else form.classList.remove('open');
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('cardForm').classList.add('open');
    document.querySelectorAll('.pay-radio').forEach(r => {
      r.addEventListener('change', () => showCardForm(r.id === 'payCard'));
    });
  });

  function formatCard(el) {
    let v = el.value.replace(/\D/g, '').slice(0, 16);
    el.value = v.replace(/(.{4})/g, '$1 ').trim();
  }

  function formatExpiry(el) {
    let v = el.value.replace(/\D/g, '').slice(0, 4);
    if (v.length >= 3) v = v.slice(0, 2) + ' / ' + v.slice(2);
    el.value = v;
  }

  function toggleBilling() {
    const chk = document.getElementById('billingChk');
    const icon = document.getElementById('billingIcon');
    const checked = icon.style.display !== 'none';
    icon.style.display = checked ? 'none' : 'block';
    chk.style.background = checked ? '' : '#171717';
    chk.style.borderColor = checked ? '#888' : '#171717';
    if (!checked) icon.style.display = 'block';
  }

  function toggleRemPal() {
    const dot = document.getElementById('remPalDot');
    const on = dot.dataset.on === '1';
    dot.dataset.on = on ? '0' : '1';
    dot.style.borderColor = on ? '#C0C0C0' : '#171717';
    dot.innerHTML = on ? '' : '<div style="width:10px;height:10px;border-radius:50%;background:#171717"></div>';
  }

  const BASE = [100, 200];
  function changeQty(btn, dir) {
    const numEl = btn.parentElement.querySelector('.qty-num');
    let q = parseInt(numEl.textContent) + dir;
    if (q < 1) q = 1;
    if (q > 99) q = 99;
    numEl.textContent = q;
    updateTotals();
  }

  function removeItem(btn) {
    const row = btn.closest('.flex.gap-3.py-4');
    row.style.opacity = '0';
    row.style.transition = 'opacity .3s';
    setTimeout(() => { row.remove(); updateTotals(); }, 300);
  }

  function updateTotals() {
    let sub = 0;
    document.querySelectorAll('.qty-num').forEach((el, i) => {
      sub += (BASE[i] || 100) * parseInt(el.textContent || 1);
    });
    const total = sub + 5;
    document.getElementById('subtotal').textContent = sub.toFixed(2);
    document.getElementById('totalDue').textContent = '$' + total.toFixed(2);
    document.getElementById('payBtn').textContent = 'Pay $' + total.toFixed(2);
  }

  function toggleAddressModal() {
    const m = document.getElementById('addrModal');
    const open = m.style.display === 'flex';
    m.style.display = open ? 'none' : 'flex';
    m.style.setProperty('display', open ? 'none' : 'flex', 'important');
    document.body.style.overflow = open ? '' : 'hidden';
  }

  function saveAddress() {
    toggleAddressModal();
    const banner = document.createElement('div');
    banner.className = 'fixed top-20 left-1/2 -translate-x-1/2 bg-[#2AAF2F] text-white px-6 py-3 rounded-full text-sm font-medium shadow-lg z-[800] transition-all';
    banner.textContent = '✓ Address saved successfully';
    document.body.appendChild(banner);
    setTimeout(() => { banner.style.opacity = '0'; setTimeout(() => banner.remove(), 400); }, 2500);
  }

  function handlePay() {
    const btn = document.getElementById('payBtn');
    const orig = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing…';
    btn.style.animation = 'none';
    btn.style.background = '#2AAF2F';
    setTimeout(() => {
      btn.textContent = '✓ Payment Successful!';
      setTimeout(() => {
        btn.disabled = false;
        btn.textContent = orig;
        btn.style.background = '';
        btn.style.animation = 'shine 3s linear infinite';
      }, 2500);
    }, 1800);
  }

  window.openMenu = openMenu;
  window.closeMenu = closeMenu;
  window.showCardForm = showCardForm;
  window.formatCard = formatCard;
  window.formatExpiry = formatExpiry;
  window.toggleBilling = toggleBilling;
  window.toggleRemPal = toggleRemPal;
  window.changeQty = changeQty;
  window.removeItem = removeItem;
  window.updateTotals = updateTotals;
  window.toggleAddressModal = toggleAddressModal;
  window.saveAddress = saveAddress;
  window.handlePay = handlePay;
})();
