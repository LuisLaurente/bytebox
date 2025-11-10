document.addEventListener("DOMContentLoaded", function () {
    const toast = document.getElementById("toast");

    if (toast && toast.textContent.trim() !== "") {
        toast.style.display = "block";

        setTimeout(() => {
            toast.style.display = "none";
        }, 3000);
    }
});