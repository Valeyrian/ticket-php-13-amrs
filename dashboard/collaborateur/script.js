const notePad = document.getElementById("note-pad");
const notePadSaveBtn = document.getElementById("save-btn");
// ========================================
// GESTION DU BLOC NOTES
// ========================================
window.addEventListener("load", () => {
  const savedNotes = localStorage.getItem("collaboratorNotes");
  if (savedNotes) {
    notePad.value = savedNotes;
  }
});

notePadSaveBtn.addEventListener("click", () => {
  localStorage.setItem("collaboratorNotes", notePad.value);
  console.log("Notes enregistrées !");
});
