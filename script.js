// Récupérer les éléments du DOM
const modal = document.getElementById("login-modal");
const accountBtn = document.getElementById("account-btn");
const closeBtn = document.querySelector(".close");

// Ouvrir la modal au clic sur le bouton account
accountBtn.addEventListener("click", function(e) {
    e.preventDefault();
    modal.style.display = "block";
});

// Fermer la modal au clic sur X
closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
});

// Fermer la modal au clic en dehors
window.addEventListener("click", function(e) {
    if (e.target == modal) {
        modal.style.display = "none";
    }
});