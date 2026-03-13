<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/projet.php';
require_once __DIR__ . '/../classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];
$projectId = isset($_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$action = $_POST['action'] ?? '';
$redirect = "/projects/admin/generic-project.php?id=" . $projectId;

if (!$projectId || !$action) {
    header("Location: /projects/admin/project-manager.php");
    exit;
}

$projet = Projet::getById($pdo, $projectId);
if (!$projet) {
    header("Location: /projects/admin/project-manager.php?error=projet_introuvable");
    exit;
}

// ====== Mise à jour des infos générales ======
if ($action === 'update_info') {
    $nom = trim($_POST['nom'] ?? $projet->getNom());
    $description = trim($_POST['description'] ?? $projet->getDescription());
    $statut = trim($_POST['statut'] ?? $projet->getStatut());
    $dateDebut = trim($_POST['date_debut'] ?? $projet->getDateDebut());
    $dateFinPrevue = trim($_POST['date_fin_prevue'] ?? $projet->getDateFinPrevue());

    $projet->setNom($nom);
    $projet->setDescription($description);
    $projet->setStatut($statut);
    $projet->setDateDebut($dateDebut);
    $projet->setDateFinPrevue($dateFinPrevue);
    $projet->update($pdo);
}

// ====== Mise à jour de la description seule ======
if ($action === 'update_description') {
    $description = trim($_POST['description'] ?? '');
    $projet->setDescription($description);
    $projet->update($pdo);
}

// ====== Ajouter un collaborateur ======
if ($action === 'add_collaborateur') {
    $collabId = (int) ($_POST['collaborateur_id'] ?? 0);
    if ($collabId > 0) {
        $projet->addCollaborateur($pdo, $collabId);
    }
}

// ====== Retirer un collaborateur ======
if ($action === 'remove_collaborateur') {
    $collabId = (int) ($_POST['collaborateur_id'] ?? 0);
    if ($collabId > 0) {
        $projet->removeCollaborateur($pdo, $collabId);
    }
}

// ====== Archiver le projet ======
if ($action === 'archive') {
    $projet->setStatut('archive');
    $projet->update($pdo);
}

header("Location: $redirect");
exit;
