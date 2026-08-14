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
            initialCountry: "auto",
            initialCountryLookup: lookupCountry,
            separateDialCode: true,
            loadUtils: () => Promise.resolve(intlTelInputUtils),
        });

        instances.set(input, iti);

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
        });

        form?.addEventListener("submit", (event) => {
            if (!validate(input, iti)) {
                event.preventDefault();
                event.stopPropagation();
                input.focus();
                return;
            }

            syncFullNumber(input, iti);
        });
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
