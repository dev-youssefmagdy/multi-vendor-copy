// Elora side-menu: unlimited nested category navigation
(function () {
    // rightStack: [{label: string, items: CatNode[]}]
    // Each "drill in" pushes a level; back button pops it.
    var rightStack = [];

    // ── Helpers ─────────────────────────────────────────────────────────

    function escHtml(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function findById(id, cats) {
        for (var i = 0; i < cats.length; i++) {
            if (cats[i].id == id) return cats[i];
            if (cats[i].children && cats[i].children.length) {
                var found = findById(id, cats[i].children);
                if (found) return found;
            }
        }
        return null;
    }

    // ── Render right column from current stack top ───────────────────────

    function renderRight() {
        var content = document.getElementById('eloraCatRightContent');
        if (!content) return;

        var level = rightStack.length > 0 ? rightStack[rightStack.length - 1] : null;

        if (!level || !level.items.length) {
            content.innerHTML = '';
            return;
        }

        var html = '';

        // Back button — shown when deeper than first level
        if (rightStack.length > 1) {
            html += '<button type="button" class="elora-right-back-btn" onclick="window.eloraRightBack()" ' +
                'style="display:flex;align-items:center;gap:6px;padding:8px;min-height:34px;width:100%;' +
                'font-family:\'Outfit\',sans-serif;font-weight:600;font-size:13px;color:#FF522C;' +
                'border:none;background:transparent;cursor:pointer;border-radius:4px;border-bottom:1px solid #e8e8e8;margin-bottom:6px;">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="elora-right-back-arrow" style="flex-shrink:0;">' +
                '<path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>' +
                '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(level
                    .label) + '</span>' +
                '</button>';
        }

        level.items.forEach(function (cat) {
            var hasChildren = cat.children && cat.children.length > 0;

            if (hasChildren) {
                // Show as a drill-in button (name + chevron)
                // Also expose a "navigate" link on the name itself
                html += '<div style="display:flex;align-items:center;border-radius:4px;min-height:34px;">' +
                    '<a href="' + escHtml(cat.url) + '" onclick="closeSideMenu()" ' +
                    'class="elora-sub-link" ' +
                    'style="flex:1;display:flex;align-items:center;padding:8px 4px 8px 8px;' +
                    'font-family:\'Outfit\',sans-serif;font-weight:400;font-size:14px;line-height:18px;' +
                    'letter-spacing:0.5px;color:#242424;text-decoration:none;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' +
                    escHtml(cat.name) +
                    '</a>' +
                    '<button type="button" onclick="window.eloraDrillRight(' + cat.id + ')" ' +
                    'style="display:flex;align-items:center;justify-content:center;flex-shrink:0;' +
                    'width:32px;height:34px;border:none;background:transparent;cursor:pointer;color:#9ca3af;border-radius:4px;"' +
                    'title="' + escHtml(window.trans('Show subcategories')) + '">' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="cat-menu-arrow">' +
                    '<path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>' +
                    '</button>' +
                    '</div>';
            } else {
                html += '<a href="' + escHtml(cat.url) + '" onclick="closeSideMenu()" ' +
                    'class="elora-sub-link" ' +
                    'style="display:flex;align-items:center;padding:8px;gap:8px;min-height:34px;' +
                    'font-family:\'Outfit\',sans-serif;font-weight:400;font-size:14px;line-height:18px;' +
                    'letter-spacing:0.5px;color:#242424;text-decoration:none;border-radius:4px;">' +
                    escHtml(cat.name) +
                    '</a>';
            }
        });

        content.innerHTML = html;
    }

    // ── Public API ───────────────────────────────────────────────────────

    function eloraActivateCat(catId) {
        var tree = window.__eloraCatTree || [];
        var cat = tree.find(function (c) {
            return c.id == catId;
        });

        // Highlight active left-column button
        var leftCol = document.getElementById('eloraCatLeft');
        if (leftCol) {
            leftCol.querySelectorAll('.cat-menu-btn').forEach(function (btn) {
                var isActive = btn.dataset.catId == catId;
                btn.style.background = isActive ? '#F4F4F4' : '';
                var label = btn.querySelector('.cat-menu-label');
                var arrow = btn.querySelector('.cat-menu-arrow');
                if (label) label.style.color = isActive ? '#FF522C' : '#242424';
                if (arrow) arrow.style.color = isActive ? '#FF522C' : '#242424';
            });
        }

        // Reset right stack to first level of this root cat
        if (cat && cat.children && cat.children.length) {
            rightStack = [{
                label: cat.name,
                items: cat.children
            }];
        } else {
            rightStack = [];
        }
        renderRight();
    }

    function eloraDrillRight(catId) {
        var tree = window.__eloraCatTree || [];
        var cat = findById(catId, tree);
        if (!cat || !cat.children || !cat.children.length) return;
        rightStack.push({
            label: cat.name,
            items: cat.children
        });
        renderRight();
    }

    function eloraRightBack() {
        if (rightStack.length > 1) {
            rightStack.pop();
            renderRight();
        }
    }

    function eloraHandleCatClick(btn) {
        var hasChildren = btn.dataset.hasChildren === '1';
        if (!hasChildren) {
            var href = btn.dataset.href;
            if (href) {
                if (typeof closeSideMenu === 'function') closeSideMenu();
                window.location.href = href;
            }
        }
        // Has children → hover already showed them; user drills via right col.
    }

    // Expose to global scope
    window.eloraActivateCat = eloraActivateCat;
    window.eloraHandleCatClick = eloraHandleCatClick;
    window.eloraDrillRight = eloraDrillRight;
    window.eloraRightBack = eloraRightBack;
})();
