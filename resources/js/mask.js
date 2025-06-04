export default function mask() {
    let el = document.querySelectorAll(".money");

    for (let i = 0; i < el.length; i++) {
        maskInit(el[i]);
        el[i].addEventListener("focus", clearMask);
        el[i].addEventListener("input", clearMask);
        el[i].addEventListener("blur", applyMask);
    }
}

function maskInit(el) {
    let value = "";
    if (el.value) {
        value = el.value;
        value = parseFloat(value.replace(/,/g, ""));
        if (!isNaN(value)) {
            const formattedValue = new Intl.NumberFormat("en-US").format(value);
            el.value = formattedValue;
        } else {
            el.value = 0;
        }
    }
}

function clearMask(event) {
    let value = event.target.value;
    value = value.replace(/[^0-9.]/g, ""); // Clean non-numeric chars
    event.target.value = value;
}

function applyMask(event) {
    let value = event.target.value;
    value = parseFloat(value.replace(/,/g, "")); // Remove commas for parsing
    if (!isNaN(value)) {
        const formattedValue = new Intl.NumberFormat("en-US").format(value);
        event.target.value = formattedValue;
    } else {
        event.target.value = 0;
    }
}
