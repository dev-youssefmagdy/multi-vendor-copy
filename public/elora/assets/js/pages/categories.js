(function () {
  function openSideMenu() {
    const m = document.getElementById('sideMenu');
    m.classList.remove('hidden');
    requestAnimationFrame(() => m.classList.add('open'));
    document.body.style.overflow = 'hidden';
  }

  function closeSideMenu() {
    const m = document.getElementById('sideMenu');
    m.classList.remove('open');
    setTimeout(() => { m.classList.add('hidden'); document.body.style.overflow = ''; }, 300);
  }

  document.getElementById('menuBtn').addEventListener('click', openSideMenu);

  function openFilterDrawer() {
    const d = document.getElementById('filterDrawer');
    d.classList.remove('hidden');
    requestAnimationFrame(() => d.classList.add('open'));
    document.body.style.overflow = 'hidden';
    const sidebar = document.querySelector('.desktop-sidebar');
    const drawerContent = document.getElementById('drawerFiltersContent');
    if (sidebar && !drawerContent.hasChildNodes()) {
      drawerContent.innerHTML = sidebar.innerHTML;
    }
  }

  function closeFilterDrawer() {
    const d = document.getElementById('filterDrawer');
    d.classList.remove('open');
    setTimeout(() => { d.classList.add('hidden'); document.body.style.overflow = ''; }, 300);
  }

  function toggleGroup(el) {
    el.classList.toggle('collapsed');
  }

  function switchPill(el) {
    document.querySelectorAll('.cat-pill').forEach(p => {
      p.classList.remove('active');
      p.style.background = '';
      p.style.color = '';
      p.style.borderColor = '';
    });
    el.classList.add('active');
  }

  function toggleSize(btn) {
    const active = btn.classList.contains('active');
    if (active) {
      btn.classList.remove('active', 'border-main', 'text-main', 'bg-main/10');
      btn.classList.add('border-gray-200');
    } else {
      btn.classList.add('active', 'border-main', 'text-main', 'bg-orange-50');
      btn.classList.remove('border-gray-200');
    }
  }

  const gridEl = document.getElementById('productsGrid');
  function setGrid(cols, btn) {
    document.querySelectorAll('.grid-btn').forEach(b => {
      b.classList.remove('border-main', 'bg-main/10', 'text-main');
      b.classList.add('border-gray-200', 'text-gray3');
    });
    btn.classList.add('border-main', 'bg-main/10', 'text-main');
    btn.classList.remove('border-gray-200', 'text-gray3');
    const colsMap = { 5: 'repeat(5,1fr)', 4: 'repeat(4,1fr)', 2: 'repeat(2,1fr)' };
    if (window.innerWidth >= 1200) gridEl.style.gridTemplateColumns = colsMap[cols];
  }

  let currentPage = 1;
  const totalPages = 83;
  function goPage(n, btn) {
    currentPage = n;
    document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('prevBtn').classList.toggle('disabled', n <= 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function changePage(dir) {
    currentPage = Math.max(1, Math.min(totalPages, currentPage + dir));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.querySelectorAll('.heart-btn').forEach(b => b.addEventListener('click', function () { this.classList.toggle('active'); }));

  document.querySelectorAll('.card-cart-btn').forEach(b => {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      const orig = this.innerHTML;
      this.style.background = '#FF4D00';
      this.innerHTML = '<svg style="width:16px;height:16px" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
      setTimeout(() => { this.style.background = ''; this.innerHTML = orig; }, 1500);
    });
  });

  window.openSideMenu = openSideMenu;
  window.closeSideMenu = closeSideMenu;
  window.openFilterDrawer = openFilterDrawer;
  window.closeFilterDrawer = closeFilterDrawer;
  window.toggleGroup = toggleGroup;
  window.switchPill = switchPill;
  window.toggleSize = toggleSize;
  window.setGrid = setGrid;
  window.goPage = goPage;
  window.changePage = changePage;
})();
