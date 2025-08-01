import "./bootstrap";
import mask from "./mask";
import popupWindow from "./popupWindow";

import {
    Livewire,
    // Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";

// import mask from "@alpinejs/mask";
// import focus from "@alpinejs/focus";
// Alpine.plugin(mask);
// Alpine.plugin(focus);

Livewire.start();

window.mask = mask;
document.addEventListener("livewire:navigated", () => {
    setTimeout(function () {
        window.mask();
    }, 100);
});

window.popupWindow = popupWindow;
