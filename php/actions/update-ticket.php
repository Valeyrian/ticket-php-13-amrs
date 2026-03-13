<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/ticket.php';
require_once __DIR__ . '/../classes/projet.php';
require_once __DIR__ . '/../classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];
$ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
$action = $_POST['action'] ?? '';
$redirect = "/tickets/admin/generic-ticket.php?id=" . $ticketId;

if (!$ticketId || !$action) {
    header("Location: /tickets/admin/ticket-manager.php");
    exit;
}

$ticket = Ticket::getById($pdo, $ticketId);
if (!$ticket) {
    header("Location: /tickets/admin/ticket-manager.php?error=ticket_introuvable");
    exit;
}

// ====== Mise à jour des métadonnées ======
if ($action === 'update_metadata') {
    $statut = $_POST['statut'] ?? $ticket->getStatut();
    $priorite = $_POST['priorite'] ?? $ticket->getPriorite();
    $type = $_POST['type'] ?? $ticket->getType();
    $tempsEstime = isset($_POST['temps_estime']) ? (float) $_POST['temps_estime'] : $ticket->getTempsEstime();
    $projetId = isset($_POST['projet_id']) ? (int) $_POST['projet_id'] : $ticket->getProjetId();

    $ticket->setStatut($statut);
    $ticket->setPriorite($priorite);
    $ticket->setType($type);
    $ticket->setTempsEstime($tempsEstime);
    if ($projetId) {
        $ticket->setProjetId($projetId);
    }
    $ticket->update($pdo);
}

// ====== Mise à jour de la description ======
elseif ($action === 'update_description') {
    $description = $_POST['description'] ?? '';
    $ticket->setDescription($description);
    $ticket->update($pdo);
}

// ====== Ajout collaborateur ======
elseif ($action === 'add_collaborateur') {
    $collabId = isset($_POST['collaborateur_id']) ? (int) $_POST['collaborateur_id'] : 0;
    if ($collabId) {
        $ticket->addCollaborateur($pdo, $collabId);

        // Ajouter aussi au projet parent si pas déjà dedans
        $projetId = $ticket->getProjetId();
        if ($projetId) {
            $projet = Projet::getById($pdo, $projetId);
            if ($projet) {
                $projet->addCollaborateur($pdo, $collabId);
            }
        }
    }
}

// ====== Retirer collaborateur ======
elseif ($action === 'remove_collaborateur') {
    $collabId = isset($_POST['collaborateur_id']) ? (int) $_POST['collaborateur_id'] : 0;
    if ($collabId) {
        $ticket->removeCollaborateur($pdo, $collabId);
    }
}

// ====== Ajouter une entrée de temps ======
elseif ($action === 'add_temps') {
    $collabId = isset($_POST['collaborateur_id']) ? (int) $_POST['collaborateur_id'] : 0;
    $dateTravail = $_POST['date_travail'] ?? date('Y-m-d');
    $duree = isset($_POST['duree']) ? (float) $_POST['duree'] : 0;
    $commentaire = $_POST['commentaire'] ?? null;

    if ($collabId && $duree > 0) {
        $ticket->addTemps($pdo, $collabId, $dateTravail, $duree, $commentaire ?: null);
        $ticket->recalculerTempsPasse($pdo);
    }
}

// ====== Supprimer une entrée de temps ======
elseif ($action === 'delete_temps') {
    $tempsId = isset($_POST['temps_id']) ? (int) $_POST['temps_id'] : 0;
    if ($tempsId) {
        Ticket::deleteTemps($pdo, $tempsId);
        $ticket->recalculerTempsPasse($pdo);
    }
}

// ====== Ajouter un commentaire ======
elseif ($action === 'add_commentaire') {
    $contenu = trim($_POST['contenu'] ?? '');
    if (strlen($contenu) >= 3) {
        $ticket->addCommentaire($pdo, $user->getId(), $contenu);
    }
}

// ====== Supprimer un commentaire ======
elseif ($action === 'delete_commentaire') {
    $commentaireId = isset($_POST['commentaire_id']) ? (int) $_POST['commentaire_id'] : 0;
    if ($commentaireId) {
        Ticket::deleteCommentaire($pdo, $commentaireId);
    }
}

// ====== Ajuster le temps passé (admin) ======
elseif ($action === 'adjust_temps') {
    $tempsPasse = isset($_POST['temps_passe']) ? (float) $_POST['temps_passe'] : 0;
    $ticket->setTempsPasse($tempsPasse);
    $ticket->update($pdo);
}

// ====== Forcer type facturation (admin) ======
elseif ($action === 'force_type') {
    $type = $_POST['type'] ?? $ticket->getType();
    $ticket->setType($type);
    $ticket->update($pdo);
}

// ====== Forcer validation ======
elseif ($action === 'force_validate') {
    $ticket->setValidationStatus('valide');
    $ticket->update($pdo);
}

// ====== Forcer refus ======
elseif ($action === 'force_reject') {
    $ticket->setValidationStatus('refuse');
    $ticket->update($pdo);
}

header("Location: " . $redirect);
exit;
