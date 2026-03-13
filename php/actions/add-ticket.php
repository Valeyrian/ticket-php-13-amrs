<?php
require_once '../../db.php';
require_once '../classes/ticket.php';
require_once '../classes/projet.php';
require_once '../classes/user.php';


ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier que l'utilisateur est connecté et admin ou collaborateur
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin() && !$_SESSION['user']->isCollaborateur()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$name = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'moyenne');
$status = trim($_POST['status'] ?? 'nouveau');
$projectId = trim($_POST['project_id'] ?? '');
$billingType = trim($_POST['billing_type'] ?? 'inclus');
$estimatedTime = trim($_POST['ticket-time'] ?? '0');
$collaborators = $_POST['collaborators'] ?? [];

try {
    $newTicket = new Ticket(
        id: null,
        titre: $name,
        description: $description,
        priorite: $priority,
        statut: $status,
        projet_id: $projectId,
        type: $billingType,
        temps_estime: $estimatedTime,
    );

    $newTicket->create($pdo);

    foreach ($collaborators as $collabId) {

        if ($newTicket->hasCollaborateur($pdo, $collabId)) {
            continue;
        }
        if (!User::isAtLeastCollaboratorById($pdo, $collabId)) {
            throw new Exception("L'utilisateur avec ID $collabId n'est pas un collaborateur.");
        }

        $newTicket->addCollaborateur($pdo, $collabId);

        // Ajouter aussi au projet parent
        if ($projectId) {
            $projet = Projet::getById($pdo, (int) $projectId);
            if ($projet) {
                $projet->addCollaborateur($pdo, (int) $collabId);
            }
        }
    }


} catch (Exception $e) {
    header("Location: /tickets/admin/ticket-manager.php?error=exception_ajout_ticket");
    exit;
}

header("Location: /tickets/admin/ticket-manager.php?success=ajout_ticket");
exit;