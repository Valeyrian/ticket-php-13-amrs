document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // TICKETS TABLE: style facturation
  // ========================================

  function updateBillingVisibility() {
    document.querySelectorAll(".tickets-table tbody tr").forEach((row) => {
      const billingBadge = row.querySelector(".billing-badge");
      if (!billingBadge) return;

      const isInclus = billingBadge.classList.contains("inclus");
      const timeCell = row.querySelector("td:nth-child(7)");

      if (isInclus) {
        if (timeCell) {
          timeCell.style.color = "var(--text-gray)";
        }
      } else {
        if (timeCell) {
          timeCell.style.color = "#ffb822";
          timeCell.style.fontWeight = "bold";
        }
      }
    });
  }

  updateBillingVisibility();
});
