import intlTelInput from "intl-tel-input";
import intlTelInputUtils from "intl-tel-input/utils";
import "intl-tel-input/styles";

const instances = new WeakMap();
const GEO_CACHE_KEY = "phone-input:country";

// Each provider resolves to an ISO2 code or throws; they're tried in order so
// one blocked / rate-limited service (ipapi.co's free tier often is) doesn't
// leave every "add" form defaulting to the US.
const GEO_PROVIDERS = [
    async () => (await (await fetch("https://ipapi.co/json/")).json()).country_code,
    async () => (await (await fetch("https://ipwho.is/")).json()).country_code,
    async () => {
        const text = await (await fetch("https://www.cloudflare.com/cdn-cgi/trace")).text();
        return /^loc=([A-Z]{2})$/m.exec(text)?.[1];
    },
];

function localeCountry() {
    const langs = navigator.languages?.length ? navigator.languages : [navigator.language];
    for (const lang of langs) {
        const region = /^[a-z]{2,3}[-_]([A-Za-z]{2})\b/.exec(lang || "")?.[1];
        if (region) {
            return region;
        }
    }
    return null;
}

async function lookupCountry() {
    try {
        const cached = sessionStorage.getItem(GEO_CACHE_KEY);
        if (cached) {
            return cached;
        }
    } catch {
        // storage unavailable — carry on without the cache
    }

    let code = null;
    for (const provider of GEO_PROVIDERS) {
        try {
            const result = await provider();
            if (typeof result === "string" && /^[A-Za-z]{2}$/.test(result)) {
                code = result;
                break;
            }
        } catch {
            // try the next provider
        }
    }

    const iso2 = (code || localeCountry() || "us").toLowerCase();

    if (code) {
        try {
            sessionStorage.setItem(GEO_CACHE_KEY, iso2);
        } catch {
            // ignore
        }
    }

    return iso2;
}

function ensureErrorSlot(input) {
    let slot = input.parentElement?.querySelector(".phone-input-error");

    if (!slot) {
        slot = document.createElement("p");
        slot.className = "phone-input-error text-red-500 text-[12px] mt-1 hidden";
        input.insertAdjacentElement("afterend", slot);
    }

    return slot;
}

function showError(input, message) {
    const slot = ensureErrorSlot(input);
    input.classList.add("border-red-500");
    input.setCustomValidity(message || "Invalid phone number");

    if (message) {
        slot.textContent = message;
        slot.classList.remove("hidden");
    } else {
        slot.textContent = "";
        slot.classList.add("hidden");
    }
}

function clearError(input) {
    input.classList.remove("border-red-500");
    input.setCustomValidity("");

    const slot = input.parentElement?.querySelector(".phone-input-error");
    if (slot) {
        slot.textContent = "";
        slot.classList.add("hidden");
    }
}

// `wire-event` is not a real Livewire binding directive (unlike wire:model),
// so Livewire never sees the DOM input/change events we dispatch below on
// its own. When present, push the value into that Livewire component
// property directly so fields using wire-event (instead of wire:model) stay
// in sync the same way wire:model-bound fields do.
function syncLivewireProperty(input, value) {
    const property = input.getAttribute("wire-event");
    if (!property || !window.Livewire) {
        return;
    }

    const el = input.closest("[wire\\:id]");
    if (!el) {
        return;
    }

    const component = window.Livewire.find(el.getAttribute("wire:id"));
    component?.set(property, value);
}

const syncing = new WeakSet();

function fullNumber(input, iti) {
    let full = iti.getNumber();

    // getNumber() returns "" until the utils bundle has loaded; build the
    // international number from the selected dial code so the country code
    // is never dropped.
    if (!full) {
        const dial = iti.getSelectedCountryData()?.dialCode;
        const digits = input.value.replace(/\D/g, "").replace(/^0+/, "");
        if (dial && digits) {
            full = input.value.trim().startsWith("+") ? `+${digits}` : `+${dial}${digits}`;
        }
    }

    return full || "";
}

// Plain (non-Livewire) forms submit the visible input's value, which
// intl-tel-input keeps as national digits when separateDialCode is on (it
// strips any dial code written back into the field). So those forms get a
// hidden input carrying the real field name and the full +<code><number>.
const hiddenInputs = new WeakMap();

function syncFullNumber(input, iti) {
    const hidden = hiddenInputs.get(input);

    if (hidden) {
        hidden.value = input.value.trim() ? fullNumber(input, iti) : "";
        return;
    }

    if (!input.value.trim()) {
        return;
    }

    const full = fullNumber(input, iti);

    if (full && full !== input.value) {
        syncing.add(input);
        input.value = full;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
        syncing.delete(input);
    }

    // Always use the E.164 value (with country code) for Livewire, not the
    // national-only digits ITI displays in the field.
    syncLivewireProperty(input, full || input.value);
}

// Fires before Livewire serialises ANY component's state, so wire:model.defer
// fields get the E.164 value even if the field never blurred.
let livewireHookBound = false;
function ensureLivewireHook() {
    if (livewireHookBound) {
        return;
    }
    livewireHookBound = true;

    document.addEventListener("livewire:before-send", () => {
        document.querySelectorAll("[data-phone-input]").forEach((input) => {
            const iti = instances.get(input);
            if (iti) {
                syncFullNumber(input, iti);
            }
        });
    });
}

function validate(input, iti) {
    if (!input.value.trim()) {
        clearError(input);
        return true;
    }

    if (iti.isValidNumber()) {
        clearError(input);
        return true;
    }

    showError(input, input.dataset.phoneInvalidMessage || "Please enter a valid phone number.");
    return false;
}

export function initPhoneInputs(root = document) {
    ensureLivewireHook();

    const inputs =
        root instanceof Element && root.matches("[data-phone-input]")
            ? [root]
            : Array.from(root.querySelectorAll("[data-phone-input]"));

    inputs.forEach((input) => {
        if (instances.has(input)) {
            return;
        }

        const form = input.closest("form");
        const iti = intlTelInput(input, {
            // v29's API dropped the "auto" literal for initialCountry — leave
            // it unset and resolve the country asynchronously via
            // initialCountryLookup instead.
            initialCountry: "",
            initialCountryLookup: lookupCountry,
            separateDialCode: true,
            dropdownParent: document.body,
            // POLITE/AGGRESSIVE ask the bundled utils for a per-country
            // example number to show as a placeholder; when that lookup
            // fails it falls back to stringifying its error object, which
            // renders as the literal text "[object Object]" in the field.
            // We always pass an explicit placeholder from the blade markup,
            // so auto-generating one here is unnecessary and unsafe.
            placeholderNumberPolicy: "OFF",
            loadUtils: () => Promise.resolve({ default: intlTelInputUtils }),
        });

        instances.set(input, iti);

        if (
            form &&
            input.name &&
            !input.hasAttribute("wire-event") &&
            !Array.from(input.attributes).some((a) => a.name.startsWith("wire:model"))
        ) {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = input.name;
            hidden.value = input.value.trim();
            input.removeAttribute("name");
            input.insertAdjacentElement("afterend", hidden);
            hiddenInputs.set(input, hidden);
        }

        if (input.value && input.value.trim() !== "") {
            const currentVal = input.value.trim();
            if (currentVal.includes("[object") || currentVal.includes("Object]")) {
                input.value = "";
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }

        if (input.placeholder && input.placeholder.includes("[object")) {
            input.placeholder = "";
        }

        input.addEventListener("countrychange", () => {
            if (validate(input, iti)) {
                syncFullNumber(input, iti);
            }
        });

        input.addEventListener("blur", () => {
            if (validate(input, iti)) {
                syncFullNumber(input, iti);
            }
        });

        input.addEventListener("input", () => {
            if (syncing.has(input)) {
                return;
            }

            if (input.classList.contains("border-red-500")) {
                validate(input, iti);
            }
            // Sync after the digit has been processed by ITI so wire:model
            // (live mode) always reads the full E.164 number, not just the
            // national portion typed so far.
            requestAnimationFrame(() => syncFullNumber(input, iti));
        });

        // Livewire's wire:submit handler is a bubble-phase listener (bound via
        // Alpine's x-on:submit.prevent) that reads component properties before
        // dispatching to the server. Registering our sync in the capture phase
        // guarantees it runs before Livewire's handler regardless of DOM
        // registration order, so wire:model.defer picks up the full E.164 number.
        form?.addEventListener(
            "submit",
            (event) => {
                if (!validate(input, iti)) {
                    event.preventDefault();
                    event.stopPropagation();
                    input.focus();
                    return;
                }

                syncFullNumber(input, iti);
            },
            { capture: true }
        );
    });
}

export function destroyPhoneInputs(root = document) {
    const inputs =
        root instanceof Element && root.matches("[data-phone-input]")
            ? [root]
            : Array.from(root.querySelectorAll("[data-phone-input]"));

    inputs.forEach((input) => {
        const iti = instances.get(input);
        if (iti) {
            iti.destroy();
            instances.delete(input);
        }
    });
}
