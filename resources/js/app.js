import Chart from "chart.js/auto";
import Swal from "sweetalert2";
import Sortable from "sortablejs";
import { initPhoneInputs } from "./phone-input";
import "./bootstrap";

window.Sortable = Sortable;

let sbCollapsed = false;
let isDark = true;
let charts = {};
let eventsBound = false;
let adminUiBound = false;
let flashMessageShown = false;
let fieldObserverBound = false;
let livewireFieldHooksBound = false;
let enhancedSelectIds = 0;
const chartCanvasIds = [
    "revenueChart",
    "donutChart",
    "barChart",
    "lineChart",
    "radarChart",
];

const byId = (id) => document.getElementById(id);
const cssVar = (value) =>
    getComputedStyle(document.documentElement).getPropertyValue(value).trim();
const isMobile = () => window.innerWidth < 1024;
const hasDashboard = () =>
    Boolean(byId("sb") && byId("mn") && chartCanvasIds.some((id) => byId(id)));

function adminToast() {
    return Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        background: cssVar("--card"),
        color: cssVar("--t1"),
    });
}

function showToast(message, type = "success") {
    if (!message) {
        return;
    }

    adminToast().fire({
        icon: type,
        title: message,
    });
}

function showFlashToast() {
    if (flashMessageShown) {
        return;
    }

    const flash = byId("flash-status");

    if (!flash) {
        return;
    }

    flashMessageShown = true;
    showToast(flash.dataset.message, flash.dataset.type || "success");
}

function triggerNativeInput(element) {
    element.dispatchEvent(new Event("input", { bubbles: true }));
    element.dispatchEvent(new Event("change", { bubbles: true }));
}

function collectRoots(root, selector) {
    const roots = [];

    if (root instanceof Element && root.matches(selector)) {
        roots.push(root);
    }

    if ("querySelectorAll" in root) {
        roots.push(...root.querySelectorAll(selector));
    }

    return roots;
}

function resolveEnhancedFieldRoot(node) {
    if (!(node instanceof HTMLElement)) {
        return document;
    }

    return (
        node.closest("[data-enhanced-select], [data-dropzone], [wire\\:id]") ||
        node
    );
}

function ensureEnhancedSelectId(wrapper) {
    // Livewire Morph Fix: Recover stripped ID from the node property
    if (!wrapper.dataset.selectId) {
        if (wrapper._esId) {
            wrapper.dataset.selectId = wrapper._esId;
        } else {
            enhancedSelectIds += 1;
            wrapper._esId = `enhanced-select-${enhancedSelectIds}`;
            wrapper.dataset.selectId = wrapper._esId;
        }
    } else {
        wrapper._esId = wrapper.dataset.selectId; // Cache it on the node
    }

    return wrapper.dataset.selectId;
}

function getEnhancedSelectPanel(wrapper) {
    const selectId = wrapper.dataset.selectId;

    if (selectId) {
        const floatingPanel = document.querySelector(
            `.enhanced-select-panel[data-select-owner="${selectId}"]`,
        );

        if (floatingPanel) {
            return floatingPanel;
        }
    }

    return wrapper.querySelector(".enhanced-select-panel");
}

function closeEnhancedSelect(wrapper) {
    const trigger = wrapper.querySelector(".enhanced-select-trigger");
    const panel = getEnhancedSelectPanel(wrapper);

    wrapper.classList.remove("is-open");

    if (panel) {
        panel.hidden = true;
    }

    trigger?.setAttribute("aria-expanded", "false");
}

function positionEnhancedSelectPanel(wrapper) {
    const trigger = wrapper.querySelector(".enhanced-select-trigger");
    const panel = getEnhancedSelectPanel(wrapper);

    if (!trigger || !panel) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const panelHeight = Math.min(panel.scrollHeight || 0, 256);
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;
    const openAbove =
        spaceBelow < Math.min(panelHeight + 12, 180) && spaceAbove > spaceBelow;
    const top = openAbove
        ? Math.max(8, rect.top - panelHeight - 8)
        : Math.min(viewportHeight - panelHeight - 8, rect.bottom + 8);

    panel.classList.toggle("is-above", openAbove);
    panel.style.position = "fixed";
    panel.style.left = `${rect.left}px`;
    panel.style.top = `${top}px`;
    panel.style.width = `${rect.width}px`;
    panel.style.zIndex = "9999";
}

function openEnhancedSelect(wrapper) {
    const trigger = wrapper.querySelector(".enhanced-select-trigger");
    const panel = getEnhancedSelectPanel(wrapper);

    if (!trigger || !panel) {
        return;
    }

    ensureEnhancedSelectId(wrapper);

    if (panel.parentElement !== document.body) {
        panel.dataset.selectOwner = wrapper.dataset.selectId;
        document.body.appendChild(panel);
    }

    wrapper.classList.add("is-open");
    panel.hidden = false;
    trigger.setAttribute("aria-expanded", "true");
    positionEnhancedSelectPanel(wrapper);
}

function cleanupOrphanedEnhancedSelectPanels() {
    document
        .querySelectorAll(".enhanced-select-panel[data-select-owner]")
        .forEach((panel) => {
            const ownerId = panel.dataset.selectOwner;

            if (
                !ownerId ||
                !document.querySelector(
                    `[data-enhanced-select][data-select-id="${ownerId}"]`,
                )
            ) {
                panel.remove();
            }
        });
}

function syncOpenEnhancedSelects() {
    cleanupOrphanedEnhancedSelectPanels();

    document
        .querySelectorAll("[data-enhanced-select].is-open")
        .forEach((wrapper) => {
            positionEnhancedSelectPanel(wrapper);
        });
}

function syncEnhancedSelect(wrapper) {
    ensureEnhancedSelectId(wrapper);

    const select = wrapper.querySelector(".enhanced-select-native");
    const trigger = wrapper.querySelector(".enhanced-select-trigger");
    const value = wrapper.querySelector(".enhanced-select-value");
    const panel = getEnhancedSelectPanel(wrapper);
    const optionsHost = panel?.querySelector(".enhanced-select-options");

    if (!select || !trigger || !value || !panel || !optionsHost) {
        return;
    }

    const multiple = select.multiple;
    const placeholder = wrapper.dataset.placeholder || "Select an option";
    const searchable = wrapper.dataset.searchable === "true";
    const isTree = wrapper.dataset.tree === "true";
    const options = Array.from(select.options).map((option, index) => ({
        value: option.value,
        label: option.textContent?.trim() || option.value,
        selected: option.selected,
        disabled: option.disabled,
        depth: parseInt(option.dataset?.depth || "0", 10) || 0,
        index,
    }));

    optionsHost.innerHTML = "";

    options.forEach((option) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `enhanced-select-option${option.selected ? " is-selected" : ""}`;
        button.dataset.index = String(option.index);
        button.dataset.label = option.label.toLowerCase();
        button.disabled = option.disabled;

        const indent = isTree && option.depth > 0
            ? `<span class="enhanced-select-indent" style="display:inline-block;width:${option.depth * 14}px"></span>`
            : "";

        if (multiple) {
            button.innerHTML = `${indent}<span class="enhanced-select-check${option.selected ? " is-selected" : ""}"></span><span>${option.label}</span>`;
        } else {
            button.innerHTML = `${indent}<span>${option.label}</span>`;
        }

        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (option.disabled) {
                return;
            }

            if (multiple) {
                select.options[option.index].selected =
                    !select.options[option.index].selected;
            } else {
                Array.from(select.options).forEach((item, itemIndex) => {
                    item.selected = itemIndex === option.index;
                });

                closeEnhancedSelect(wrapper);
            }

            triggerNativeInput(select);
            syncEnhancedSelect(wrapper);
        });

        optionsHost.appendChild(button);
    });

    const selectedOptions = options.filter(
        (option) => option.selected && option.value !== "",
    );

    if (multiple) {
        if (selectedOptions.length === 0) {
            value.innerHTML = `<span class="enhanced-select-placeholder">${placeholder}</span>`;
        } else {
            value.innerHTML = selectedOptions
                .map(
                    (option) =>
                        `<span class="enhanced-select-chip" data-index="${option.index}">${option.label}<button type="button" class="es-chip-remove" data-index="${option.index}" tabindex="-1" title="Remove">&times;</button></span>`,
                )
                .join("");

            // Remove-button handlers (no event delegation — re-bound on every sync)
            value.querySelectorAll(".es-chip-remove").forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const idx = parseInt(btn.dataset.index, 10);
                    if (!isNaN(idx) && select.options[idx]) {
                        select.options[idx].selected = false;
                        triggerNativeInput(select);
                        syncEnhancedSelect(wrapper);
                    }
                });
            });
        }
    } else {
        value.textContent =
            selectedOptions[0]?.label ||
            options.find((option) => option.selected)?.label ||
            placeholder;
    }

    wrapper.classList.toggle("is-multiple", multiple);
    wrapper.classList.toggle("is-disabled", select.disabled);

    // Wire search input (debounced filter, idempotent)
    const searchInput = panel.querySelector(".enhanced-select-search");
    if (searchInput && !searchInput._esBound) {
        const applyFilter = () => {
            const query = (searchInput.value || "").trim().toLowerCase();
            optionsHost
                .querySelectorAll(".enhanced-select-option")
                .forEach((btn) => {
                    const label = btn.dataset.label || "";
                    btn.style.display =
                        !query || label.includes(query) ? "" : "none";
                });
        };
        searchInput.addEventListener("input", applyFilter);
        searchInput.addEventListener("click", (e) => e.stopPropagation());
        searchInput._esBound = true;
    }

    if (!wrapper.isConnected && panel?.parentElement === document.body) {
        panel.remove();
        return;
    }

    if (wrapper.classList.contains("is-open")) {
        openEnhancedSelect(wrapper);
    } else {
        closeEnhancedSelect(wrapper);
    }
}

function initEnhancedSelects(root = document) {
    collectRoots(root, "[data-enhanced-select]").forEach((wrapper) => {
        ensureEnhancedSelectId(wrapper);

        const select = wrapper.querySelector(".enhanced-select-native");
        const trigger = wrapper.querySelector(".enhanced-select-trigger");
        const panel = getEnhancedSelectPanel(wrapper);

        if (!select || !trigger || !panel) {
            return;
        }

        // FIX: Use JS node properties instead of dataset to prevent duplicate listeners
        if (!trigger._esBound) {
            trigger.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (select.disabled) {
                    return;
                }

                document
                    .querySelectorAll("[data-enhanced-select].is-open")
                    .forEach((item) => {
                        if (item !== wrapper) {
                            closeEnhancedSelect(item);
                        }
                    });

                if (wrapper.classList.contains("is-open")) {
                    closeEnhancedSelect(wrapper);
                } else {
                    openEnhancedSelect(wrapper);
                }
            });

            trigger._esBound = true;
        }

        // Ensure the change listener isn't duplicated either
        if (!select._esBound) {
            select.addEventListener("change", () =>
                syncEnhancedSelect(wrapper),
            );
            select._esBound = true;
        }

        syncEnhancedSelect(wrapper);
    });
}

// Reads the pixel dimensions of an image file without uploading it.
function readImageDimensions(file) {
    return new Promise((resolve) => {
        if (!file.type || !file.type.startsWith("image/")) {
            resolve(null);
            return;
        }
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            resolve({ width: img.naturalWidth, height: img.naturalHeight });
            URL.revokeObjectURL(url);
        };
        img.onerror = () => {
            resolve(null);
            URL.revokeObjectURL(url);
        };
        img.src = url;
    });
}

// Non-blocking dimension check: returns a warning string, or null if the image matches
// (or dimensions couldn't be determined).
async function checkImageDimensionWarning(file, expectedWidth, expectedHeight) {
    if (!expectedWidth || !expectedHeight) {
        return null;
    }
    const dims = await readImageDimensions(file);
    if (!dims) {
        return null;
    }
    if (dims.width === expectedWidth && dims.height === expectedHeight) {
        return null;
    }
    return `Image should be ${expectedWidth}×${expectedHeight}. Your image is ${dims.width}×${dims.height}.`;
}

// Plain (non-dropzone) file inputs: wrap markup in [data-dimension-check][data-expect-w][data-expect-h]
// with a `.dimension-warning` slot; this binds a non-blocking dimension check on file selection.
function initDimensionChecks(root = document) {
    collectRoots(root, "[data-dimension-check]").forEach((wrapper) => {
        const input = wrapper.querySelector('input[type="file"]');
        const warningEl = wrapper.querySelector(".dimension-warning");
        const previewImg = wrapper.querySelector(".dimension-preview-img");
        const expectedWidth = parseInt(wrapper.dataset.expectW, 10) || null;
        const expectedHeight = parseInt(wrapper.dataset.expectH, 10) || null;

        if (!input || !warningEl || wrapper.dataset.dimBound) {
            return;
        }
        wrapper.dataset.dimBound = "true";

        input.addEventListener("change", async () => {
            const file = input.files && input.files[0];
            warningEl.hidden = true;
            warningEl.textContent = "";

            if (!file) {
                return;
            }

            if (previewImg && file.type.startsWith("image/")) {
                if (previewImg.dataset.blobUrl) {
                    URL.revokeObjectURL(previewImg.dataset.blobUrl);
                }
                const url = URL.createObjectURL(file);
                previewImg.dataset.blobUrl = url;
                previewImg.src = url;
                previewImg.hidden = false;
            }

            const warning = await checkImageDimensionWarning(file, expectedWidth, expectedHeight);
            if (warning) {
                warningEl.textContent = warning;
                warningEl.hidden = false;
            }
        });
    });
}

function renderDropzoneFiles(wrapper) {
    const input = wrapper.querySelector(".dropzone-input");
    const list = wrapper.querySelector("[data-dropzone-files]");
    const model = wrapper.dataset.model;
    const removeAction = wrapper.dataset.removeAction;
    const expectedWidth = parseInt(wrapper.dataset.expectW, 10) || null;
    const expectedHeight = parseInt(wrapper.dataset.expectH, 10) || null;

    if (!input || !list) {
        return;
    }

    // Revoke any previously created blob URLs to free memory.
    (list._objectUrls || []).forEach((u) => URL.revokeObjectURL(u));
    list._objectUrls = [];
    list.innerHTML = "";

    Array.from(input.files || []).forEach((file, index) => {
        const item = document.createElement("div");
        item.className = "dropzone-file";

        const isImage = file.type.startsWith("image/");
        let thumbHtml = "";
        if (isImage) {
            const url = URL.createObjectURL(file);
            list._objectUrls.push(url);
            thumbHtml = `<img class="dropzone-file-thumb" src="${url}" alt="">`;
        }

        item.innerHTML = `
            <div class="dropzone-file-row">
                ${thumbHtml}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${file.name}</span>
                    <span class="dropzone-file-size">${Math.max(1, Math.round(file.size / 1024))} KB</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="Remove ${file.name}">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `;

        item.querySelector(".dropzone-file-remove")?.addEventListener(
            "click",
            () => {
                const dt = new DataTransfer();
                Array.from(input.files || []).forEach((f, i) => {
                    if (i !== index) dt.items.add(f);
                });
                input.files = dt.files;
                wrapper._filePool = Array.from(input.files);
                triggerNativeInput(input);

                if (removeAction && model) {
                    const componentId = wrapper
                        .closest("[wire\\:id]")
                        ?.getAttribute("wire:id");
                    window.Livewire?.find(componentId)?.call(
                        removeAction,
                        model,
                        index,
                    );
                }

                renderDropzoneFiles(wrapper);
            },
        );

        list.appendChild(item);

        if (isImage && expectedWidth && expectedHeight) {
            checkImageDimensionWarning(file, expectedWidth, expectedHeight).then((warning) => {
                if (!warning) {
                    return;
                }
                const warningEl = document.createElement("p");
                warningEl.className = "dropzone-file-warning";
                warningEl.textContent = warning;
                item.appendChild(warningEl);
            });
        }
    });
}

function initDropzones(root = document) {
    collectRoots(root, "[data-dropzone]").forEach((wrapper) => {
        const input = wrapper.querySelector(".dropzone-input");

        if (!input) {
            return;
        }

        if (!wrapper.dataset.bound) {
            input.addEventListener("change", () => {
                // For multi-file inputs, accumulate selections across multiple
                // picker opens rather than replacing the previous set.
                if (input.multiple) {
                    const pool = wrapper._filePool || [];
                    const existingKeys = new Set(
                        pool.map((f) => `${f.name}-${f.size}-${f.lastModified}`),
                    );
                    const dt = new DataTransfer();
                    pool.forEach((f) => dt.items.add(f));
                    Array.from(input.files).forEach((f) => {
                        const key = `${f.name}-${f.size}-${f.lastModified}`;
                        if (!existingKeys.has(key)) dt.items.add(f);
                    });
                    input.files = dt.files;
                    wrapper._filePool = Array.from(input.files);
                }
                renderDropzoneFiles(wrapper);
            });
            wrapper.dataset.bound = "true";
        }

        renderDropzoneFiles(wrapper);
    });
}

function initEnhancedFields(root = document) {
    cleanupOrphanedEnhancedSelectPanels();
    initEnhancedSelects(root);
    initDropzones(root);
    initDimensionChecks(root);
    initPhoneInputs(root);
}

function bindLivewireFieldHooks() {
    if (livewireFieldHooksBound || !window.Livewire?.hook) {
        return;
    }

    const refresh = (node) => {
        window.requestAnimationFrame(() =>
            initEnhancedFields(resolveEnhancedFieldRoot(node)),
        );
    };

    window.Livewire.hook("morph.updated", ({ el }) => refresh(el));
    window.Livewire.hook("morphed", ({ el }) => refresh(el));

    livewireFieldHooksBound = true;
}

function bindFieldObserver() {
    if (fieldObserverBound) {
        return;
    }

    const closeSelects = (event) => {
        document
            .querySelectorAll("[data-enhanced-select].is-open")
            .forEach((wrapper) => {
                const panel = getEnhancedSelectPanel(wrapper);

                if (
                    !wrapper.contains(event.target) &&
                    !panel?.contains(event.target)
                ) {
                    closeEnhancedSelect(wrapper);
                }
            });
    };

    document.addEventListener("click", closeSelects);
    window.addEventListener("resize", syncOpenEnhancedSelects);
    document.addEventListener("scroll", syncOpenEnhancedSelects, true);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof HTMLElement) {
                    initEnhancedFields(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
    fieldObserverBound = true;
}

function bindAdminUiEvents() {
    if (adminUiBound || !window.Livewire) {
        return;
    }

    window.Livewire.on("admin-toast", ({ message, type }) => {
        showToast(message, type);
    });

    window.Livewire.on(
        "admin-confirm",
        async ({
            componentId,
            actionMethod,
            actionParams,
            title,
            text,
            confirmButtonText,
            cancelButtonText,
            icon,
        }) => {
            const result = await Swal.fire({
                title,
                text,
                icon: icon || "warning",
                showCancelButton: true,
                confirmButtonText: confirmButtonText || "Confirm",
                cancelButtonText: cancelButtonText || "Cancel",
                background: cssVar("--card"),
                color: cssVar("--t1"),
                confirmButtonColor: cssVar("--cyan") || "#22d3ee",
            });

            if (result.isConfirmed) {
                window.Livewire.find(componentId)?.call(
                    actionMethod,
                    ...(actionParams || []),
                );
            }
        },
    );

    adminUiBound = true;
}

function getDashboardCharts() {
    const payload = byId("dashboard-chart-data");

    if (!payload) {
        return window.dashboardCharts ?? {};
    }

    try {
        return JSON.parse(payload.textContent || "{}");
    } catch {
        return {};
    }
}

function setDateLabel() {
    const dateLabel = byId("dt");

    if (!dateLabel) {
        return;
    }

    dateLabel.textContent = new Date().toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function applyTheme(theme) {
    isDark = theme !== "light";
    document.documentElement.setAttribute(
        "data-theme",
        isDark ? "dark" : "light",
    );

    byId("dOpt")?.classList.toggle("ta", isDark);
    byId("lOpt")?.classList.toggle("ta", !isDark);
}

function toggleTheme() {
    applyTheme(isDark ? "light" : "dark");
    window.localStorage.setItem("nexus-theme", isDark ? "dark" : "light");
    rebuildCharts();
}

function openMobileSidebar() {
    byId("sb")?.classList.add("sb-open");
    byId("ov")?.classList.add("on");
    document.body.style.overflow = "hidden";
}

function closeMobileSidebar() {
    byId("sb")?.classList.remove("sb-open");
    byId("ov")?.classList.remove("on");
    document.body.style.overflow = "";
}

function toggleDesktopSidebar() {
    sbCollapsed = !sbCollapsed;
    byId("sb")?.classList.toggle("sb-hide", sbCollapsed);
    byId("nav")?.classList.toggle("nav-full", sbCollapsed);
    byId("mn")?.classList.toggle("mn-full", sbCollapsed);
}

function handleHamburger() {
    if (isMobile()) {
        openMobileSidebar();
        return;
    }

    toggleDesktopSidebar();
}

function syncSidebarLayout() {
    const sidebar = byId("sb");
    const nav = byId("nav");
    const main = byId("mn");

    if (!sidebar || !nav || !main) {
        return;
    }

    if (isMobile()) {
        closeMobileSidebar();
        return;
    }

    sidebar.classList.toggle("sb-hide", sbCollapsed);
    nav.classList.toggle("nav-full", sbCollapsed);
    main.classList.toggle("mn-full", sbCollapsed);
}

function setActiveNav(target) {
    document.querySelectorAll("#sb .ni").forEach((item) => {
        item.classList.remove("act");
        item.querySelector(".act-bar")?.remove();
    });

    target.classList.add("act");
    const activeBar = document.createElement("div");
    activeBar.className = "act-bar";
    target.prepend(activeBar);

    if (isMobile()) {
        closeMobileSidebar();
    }
}

function setActiveSubNav(target) {
    document
        .querySelectorAll("#sb .nk")
        .forEach((item) => item.classList.remove("nk-act"));
    target.classList.add("nk-act");

    if (isMobile()) {
        closeMobileSidebar();
    }
}

function toggleGroup(trigger) {
    const wasOpen = trigger.classList.contains("op");

    document.querySelectorAll("#sb .ng-trigger").forEach((item) => {
        item.classList.remove("op");
        item.nextElementSibling?.classList.remove("op");
    });

    if (!wasOpen) {
        trigger.classList.add("op");
        trigger.nextElementSibling?.classList.add("op");
    }
}

function setPeriod(target) {
    document
        .querySelectorAll("#periodBtns .pb")
        .forEach((button) => button.classList.remove("p-act"));
    target.classList.add("p-act");
}

function tooltipOptions() {
    return {
        backgroundColor: cssVar("--card"),
        borderColor: cssVar("--border2"),
        borderWidth: 1,
        padding: 10,
        titleFont: { family: "Syne", weight: "700", size: 11 },
        bodyFont: { family: "DM Sans", size: 11.5 },
        titleColor: cssVar("--t1"),
        bodyColor: cssVar("--t2"),
    };
}

function gridColor() {
    return cssVar("--border");
}

function chartColor(value, index = 0) {
    const fallbackPalette = [
        cssVar("--cyan"),
        cssVar("--violet"),
        cssVar("--green"),
        cssVar("--amber"),
    ];
    const palette = {
        cyan: cssVar("--cyan"),
        violet: cssVar("--violet"),
        green: cssVar("--green"),
        amber: cssVar("--amber"),
        red: cssVar("--red"),
    };

    return (
        palette[value] ||
        value ||
        fallbackPalette[index % fallbackPalette.length]
    );
}

function formatChartValue(value, format = "number") {
    const numericValue = Number(value || 0);

    switch (format) {
        case "currency":
            return `$${numericValue.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        case "percent":
            return `${numericValue.toLocaleString("en-US", { minimumFractionDigits: 0, maximumFractionDigits: 2 })}%`;
        default:
            return numericValue.toLocaleString("en-US", {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
    }
}

function buildLineDatasets(definitions, context, defaultFill = false) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);
        const gradient = context.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, `${color}44`);
        gradient.addColorStop(1, `${color}00`);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            borderColor: color,
            backgroundColor:
                (definition.fill ?? defaultFill) ? gradient : "transparent",
            fill: definition.fill ?? defaultFill,
            tension: definition.tension ?? 0.43,
            pointBackgroundColor: color,
            pointRadius: definition.pointRadius ?? 3,
            pointHoverRadius: definition.pointHoverRadius ?? 5,
            borderWidth: definition.borderWidth ?? 2.4,
            borderDash: definition.dashed
                ? [5, 3]
                : definition.borderDash || [],
            format: definition.format || "number",
        };
    });
}

function buildBarDatasets(definitions) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            backgroundColor(context) {
                const gradient = context.chart.ctx.createLinearGradient(
                    0,
                    0,
                    0,
                    180,
                );
                gradient.addColorStop(0, `${color}cc`);
                gradient.addColorStop(1, `${color}28`);
                return gradient;
            },
            borderRadius: definition.borderRadius ?? 5,
            borderSkipped: false,
            format: definition.format || "number",
        };
    });
}

function buildRadarDatasets(definitions) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            borderColor: color,
            backgroundColor: `${color}${definition.alpha || "1a"}`,
            pointBackgroundColor: color,
            pointRadius: definition.pointRadius ?? 3,
            borderWidth: definition.borderWidth ?? 2,
            format: definition.format || "number",
        };
    });
}

function buildCharts() {
    const revenueCanvas = byId("revenueChart");
    const donutCanvas = byId("donutChart");
    const barCanvas = byId("barChart");
    const lineCanvas = byId("lineChart");
    const radarCanvas = byId("radarChart");

    if (
        ![revenueCanvas, donutCanvas, barCanvas, lineCanvas, radarCanvas].some(
            Boolean,
        )
    ) {
        return;
    }

    Chart.defaults.color = cssVar("--t2");
    Chart.defaults.font.family = "DM Sans";
    Chart.defaults.font.size = 11;

    const cyan = cssVar("--cyan");
    const violet = cssVar("--violet");
    const green = cssVar("--green");
    const amber = cssVar("--amber");
    const dashboardCharts = getDashboardCharts();
    const revenueLabels = dashboardCharts.revenueLabels ?? [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];
    const revenueData = dashboardCharts.revenueData ?? [
        42, 55, 48, 71, 62, 80, 74, 84, 78, 91, 86, 98,
    ];
    const ordersData = dashboardCharts.ordersData ?? [
        28, 35, 30, 42, 38, 50, 44, 52, 48, 58, 52, 62,
    ];
    const donutLabels = dashboardCharts.donutLabels ?? [
        "Organic",
        "Social",
        "Direct",
        "Referral",
    ];
    const donutData = dashboardCharts.donutData ?? [42, 28, 18, 12];
    const revenueDatasets = dashboardCharts.revenueDatasets ?? [
        {
            label: "Revenue",
            data: revenueData,
            color: "cyan",
            fill: true,
            format: "currency",
        },
        {
            label: "Orders",
            data: ordersData,
            color: "violet",
            fill: true,
            format: "number",
        },
    ];
    const barLabels = dashboardCharts.barLabels ?? revenueLabels;
    const barDatasets = dashboardCharts.barDatasets ?? [
        {
            label: dashboardCharts.barLabel ?? "Orders",
            data: dashboardCharts.barData ?? ordersData,
            color: "green",
            format: dashboardCharts.barFormat ?? "number",
        },
    ];
    const lineLabels = dashboardCharts.lineLabels ?? [
        "W1",
        "W2",
        "W3",
        "W4",
        "W5",
        "W6",
        "W7",
        "W8",
    ];
    const lineDatasets = dashboardCharts.lineDatasets ?? [
        {
            label: "New Users",
            data: [1200, 1900, 1500, 2200, 1800, 2600, 2400, 3100],
            color: "violet",
            format: "number",
        },
        {
            label: "Returning",
            data: [800, 1200, 950, 1400, 1100, 1600, 1900, 2200],
            color: "cyan",
            format: "number",
            dashed: true,
        },
    ];
    const radarLabels = dashboardCharts.radarLabels ?? [
        "Revenue",
        "Users",
        "Orders",
        "Conv.",
        "Retention",
        "NPS",
    ];
    const radarDatasets = dashboardCharts.radarDatasets ?? [
        { label: "Q1 2025", data: [85, 72, 90, 68, 78, 82], color: "cyan" },
        {
            label: "Q4 2024",
            data: [70, 62, 74, 58, 65, 70],
            color: "violet",
            alpha: "15",
        },
    ];

    if (revenueCanvas) {
        const revenueContext = revenueCanvas.getContext("2d");

        charts.revenue = new Chart(revenueContext, {
            type: "line",
            data: {
                labels: revenueLabels,
                datasets: buildLineDatasets(
                    revenueDatasets,
                    revenueContext,
                    true,
                ),
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: {
                        position: "top",
                        align: "end",
                        labels: {
                            boxWidth: 8,
                            padding: 14,
                            usePointStyle: true,
                            color: cssVar("--t2"),
                        },
                    },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatChartValue(context.parsed.y, context.dataset.format)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: gridColor() },
                        border: { display: false },
                        ticks: { color: cssVar("--t2") },
                    },
                    y: {
                        grid: { color: gridColor() },
                        border: { display: false },
                        ticks: {
                            color: cssVar("--t2"),
                            callback(value) {
                                return formatChartValue(
                                    value,
                                    revenueDatasets[0]?.format || "number",
                                );
                            },
                        },
                    },
                },
            },
        });
    }

    if (donutCanvas) {
        charts.donut = new Chart(donutCanvas.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: donutLabels,
                datasets: [
                    {
                        data: donutData,
                        backgroundColor: [cyan, violet, green, amber],
                        borderWidth: 0,
                        hoverOffset: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                cutout: "74%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                return `${context.label}: ${formatChartValue(context.parsed, dashboardCharts.donutFormat || "number")}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (barCanvas) {
        charts.bar = new Chart(barCanvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: barLabels,
                datasets: buildBarDatasets(barDatasets),
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: barDatasets.length > 1 },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatChartValue(context.parsed.y, context.dataset.format)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: cssVar("--t2"),
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0,
                            callback(value) {
                                const label = barLabels[value] ?? "";
                                return label.length > 18
                                    ? `${label.slice(0, 17)}…`
                                    : label;
                            },
                        },
                    },
                    y: {
                        grid: { color: gridColor() },
                        border: { display: false },
                        ticks: {
                            color: cssVar("--t2"),
                            callback(value) {
                                return formatChartValue(
                                    value,
                                    barDatasets[0]?.format || "number",
                                );
                            },
                        },
                    },
                },
            },
        });
    }

    if (lineCanvas) {
        const lineContext = lineCanvas.getContext("2d");

        charts.line = new Chart(lineContext, {
            type: "line",
            data: {
                labels: lineLabels,
                datasets: buildLineDatasets(lineDatasets, lineContext, false),
            },
            options: {
                responsive: true,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: {
                        position: "top",
                        align: "end",
                        labels: {
                            boxWidth: 8,
                            padding: 12,
                            usePointStyle: true,
                            color: cssVar("--t2"),
                        },
                    },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatChartValue(context.parsed.y, context.dataset.format)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: cssVar("--t2") },
                    },
                    y: {
                        grid: { color: gridColor() },
                        border: { display: false },
                        ticks: {
                            color: cssVar("--t2"),
                            callback(value) {
                                return formatChartValue(
                                    value,
                                    lineDatasets[0]?.format || "number",
                                );
                            },
                        },
                    },
                },
            },
        });
    }

    if (radarCanvas) {
        charts.radar = new Chart(radarCanvas.getContext("2d"), {
            type: "radar",
            data: {
                labels: radarLabels,
                datasets: buildRadarDatasets(radarDatasets),
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                        align: "end",
                        labels: {
                            boxWidth: 8,
                            padding: 12,
                            usePointStyle: true,
                            color: cssVar("--t2"),
                        },
                    },
                    tooltip: {
                        ...tooltipOptions(),
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${formatChartValue(context.parsed.r, context.dataset.format)}`;
                            },
                        },
                    },
                },
                scales: {
                    r: {
                        grid: { color: gridColor() },
                        angleLines: { color: gridColor() },
                        pointLabels: {
                            font: { size: 9.5, family: "Syne" },
                            color: cssVar("--t2"),
                        },
                        ticks: { display: false },
                        suggestedMin: 0,
                        suggestedMax: 100,
                    },
                },
            },
        });
    }
}

function destroyCharts() {
    Object.values(charts).forEach((chart) => chart.destroy());
    charts = {};
}

function rebuildCharts() {
    if (!hasDashboard()) {
        return;
    }

    destroyCharts();
    window.requestAnimationFrame(buildCharts);
}

function handleDashboardClick(event) {
    const actionTarget = event.target.closest("[data-action]");

    if (!actionTarget) {
        return;
    }

    switch (actionTarget.dataset.action) {
        case "close-mobile":
            closeMobileSidebar();
            break;
        case "handle-ham":
            handleHamburger();
            break;
        case "toggle-theme":
            toggleTheme();
            break;
        case "set-active":
            setActiveNav(actionTarget);
            break;
        case "set-sub-active":
            setActiveSubNav(actionTarget);
            break;
        case "toggle-group":
            toggleGroup(actionTarget);
            break;
        case "set-period":
            setPeriod(actionTarget);
            break;
        default:
            break;
    }
}

function bindEvents() {
    if (eventsBound) {
        return;
    }

    document.addEventListener("click", handleDashboardClick);
    window.addEventListener("resize", syncSidebarLayout);
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeMobileSidebar();
        }
    });
    eventsBound = true;
}

function initDashboard() {
    bindEvents();
    bindAdminUiEvents();
    bindFieldObserver();
    bindLivewireFieldHooks();
    flashMessageShown = false;

    const preferredTheme = window.localStorage.getItem("nexus-theme");
    applyTheme(
        preferredTheme === "light" || preferredTheme === "dark"
            ? preferredTheme
            : document.documentElement.dataset.theme || "dark",
    );
    setDateLabel();
    syncSidebarLayout();
    showFlashToast();
    initEnhancedFields();

    if (!hasDashboard()) {
        destroyCharts();
        return;
    }

    rebuildCharts();
}

document.addEventListener("DOMContentLoaded", initDashboard);
document.addEventListener("livewire:init", () => {
    bindAdminUiEvents();
    bindLivewireFieldHooks();
    initEnhancedFields();
});
document.addEventListener("livewire:navigated", initDashboard);

// ── Onboarding: re-trigger file input display text after Livewire re-render ──
document.addEventListener("livewire:navigated", () => {
    document.querySelectorAll(".ob-upload-input").forEach((input) => {
        input.addEventListener("change", () => {
            const btn = input
                .closest(".ob-upload-label")
                ?.querySelector(".ob-upload-btn");
            if (btn && input.files[0]) {
                btn.textContent = input.files[0].name;
            }
        });
    });
});
