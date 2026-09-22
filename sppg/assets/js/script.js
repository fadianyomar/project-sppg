function toggleForm(formId, button, showText, hideText) {
    const form = document.getElementById(formId);
    if (form.style.display === "none" || form.style.display === "") {
        form.style.display = "block";
        button.textContent = hideText;
    } else {
        form.style.display = "none";
        button.textContent = showText;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const dropdownButtons = document.querySelectorAll(".dropdown-toggle");
    dropdownButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.stopPropagation();
            const dropdown = this.parentElement;
            document.querySelectorAll(".nav-dropdown").forEach(function (item) {
                if (item !== dropdown) {
                    item.classList.remove("show");
                }
            });
            dropdown.classList.toggle("show");
        });
    });

    document.addEventListener("click", function () {
        document.querySelectorAll(".nav-dropdown").forEach(function (item) {
            item.classList.remove("show");
        });
    });
});