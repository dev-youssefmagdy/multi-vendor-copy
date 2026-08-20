import intlTelInput from "intl-tel-input";
import intlTelInputUtils from "intl-tel-input/utils";
import "intl-tel-input/styles";

const instances = new WeakMap();
const GEO_IP_ENDPOINT = "https://ipapi.co/json/";

async function lookupCountry() {
    try {
        const response = await fetch(GEO_IP_ENDPOINT);
        const data = await response.json();
        return (data && data.country_code ? data.country_code : "us").toLowerCase();
    } catch {
        return "us";
    }
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

function syncFullNumber(input, iti) {
    if (!input.value.trim()) {
        return;
    }

    const full = iti.getNumber();

    if (full && full !== input.value) {
        input.value = full;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    syncLivewireProperty(input, input.value);
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
