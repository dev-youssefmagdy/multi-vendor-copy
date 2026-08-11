(function () {
  function openMenu() { document.body.classList.add('menu-open'); document.body.style.overflow = 'hidden'; }
  function closeMenu() { document.body.classList.remove('menu-open'); document.body.style.overflow = ''; }

  function showToast(msg, color = '#171717') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = color;
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 2500);
  }

  function filterOrders(status, el) {
    document.querySelectorAll('.sidebar-nav-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.filter-tab').forEach(i => i.classList.remove('active'));
    if (el) {
      el.classList.add('active');
      document.querySelectorAll('.sidebar-nav-item, .filter-tab').forEach(i => {
        if (i !== el && i.textContent.trim().toLowerCase() === el.textContent.trim().toLowerCase()) {
          i.classList.add('active');
        }
      });
    }
    const cards = document.querySelectorAll('.order-card');
    let visible = 0;
    cards.forEach(card => {
      const s = card.dataset.status;
      const show = status === 'all' || s === status;
      card.classList.toggle('hidden-card', !show);
      if (show) visible++;
    });
    document.getElementById('emptyOrders').classList.toggle('show', visible === 0);
  }

  function searchOrders() {
    const q = document.getElementById('orderSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.order-card');
    let visible = 0;
    cards.forEach(card => {
      const id = card.dataset.id.toLowerCase();
      const name = card.dataset.name.toLowerCase();
      const matches = !q || id.includes(q) || name.includes(q);
      card.classList.toggle('search-hidden', !matches);
      if (matches && !card.classList.contains('hidden-card')) visible++;
    });
    document.getElementById('emptyOrders').classList.toggle('show', visible === 0);
  }

  function viewDetails(id) {
    showToast('📋 Loading order details for ' + id + '…');
  }

  function trackOrder(btn) {
    const card = btn.closest('.order-card');
    document.getElementById('trackOrderId').textContent = card.dataset.id;
    document.getElementById('trackModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeTrackModal() {
    document.getElementById('trackModal').classList.remove('open');
    document.body.style.overflow = '';
  }

  let cancelCard = null;
  function cancelOrder(btn, id) {
    cancelCard = btn.closest('.order-card');
    document.getElementById('cancelOrderId').textContent = id;
    document.getElementById('cancelModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('open');
    document.body.style.overflow = '';
    cancelCard = null;
  }

  document.getElementById('confirmCancelBtn').addEventListener('click', () => {
    if (cancelCard) {
      cancelCard.style.opacity = '0';
      cancelCard.style.transform = 'translateY(-8px)';
      cancelCard.style.transition = 'all .35s ease';
      setTimeout(() => {
        cancelCard.remove();
        showToast('✅ Order cancelled successfully', '#2AAF2F');
      }, 350);
    }
    closeCancelModal();
  });

  function reorder(btn) {
    const name = btn.closest('.order-card').dataset.name;
    showToast('🛒 ' + name + ' added to cart!', '#2AAF2F');
  }

  function leaveReview() {
    showToast('⭐ Opening review for this order…');
  }

  function editProfile() {
    showToast('✏️ Opening profile editor…');
  }

  document.getElementById('trackModal').addEventListener('click', function (e) {
    if (e.target === this) closeTrackModal();
  });

  document.getElementById('cancelModal').addEventListener('click', function (e) {
    if (e.target === this) closeCancelModal();
  });

  window.openMenu = openMenu;
  window.closeMenu = closeMenu;
  window.showToast = showToast;
  window.filterOrders = filterOrders;
  window.searchOrders = searchOrders;
  window.viewDetails = viewDetails;
  window.trackOrder = trackOrder;
  window.closeTrackModal = closeTrackModal;
  window.cancelOrder = cancelOrder;
  window.closeCancelModal = closeCancelModal;
  window.reorder = reorder;
  window.leaveReview = leaveReview;
  window.editProfile = editProfile;
})();
