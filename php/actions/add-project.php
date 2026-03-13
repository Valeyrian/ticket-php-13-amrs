<?php

require_once '../../db.php';
require_once '../classes/projet.php';
require_once '../classes/client.php';
require_once '../classes/user.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier que l'utilisateur est connecté et admin ou collaborateur
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$client_id = trim($_POST['client_id'] ?? '');
$statut = trim($_POST['statut'] ?? 'actif');
$date_debut = trim($_POST['date_debut'] ?? '');
$date_fin_prevue = trim($_POST['date_fin_prevue'] ?? '');

try {
    $client = $client_id ? Client::getById($pdo, $client_id) : null;
    $projet = new Projet(
        id: null,
        nom: $nom,
        description: $description,
        statut: $statut,
        date_debut: $date_debut,
        date_fin_prevue: $date_fin_prevue
    );
    $projet->create($pdo);

    // Lier au client
    if ($client) {
        $projet->addClient($pdo, $client->getId());
    }

    // Lier les collaborateurs
    $collaborators = $_POST['collaborators'] ?? [];
    if (is_array($collaborators)) {
        foreach ($collaborators as $collabId) {
            $collabId = intval($collabId);
            if ($collabId > 0) {
                $projet->addCollaborateur($pdo, $collabId);
            }
        }
    }

    // Lier au contrat si fourni
    $contrat_id = intval($_POST['contrat_id'] ?? 0);
    if ($contrat_id > 0) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO contrat_projet (contrat_id, projet_id) VALUES (?, ?)");
        $stmt->execute([$contrat_id, $projet->getId()]);
    }

    header('Location: /projects/admin/project-manager.php?success=projet');
    exit;
} catch (Exception $e) {
    die('Erreur lors de l\'ajout du projet : ' . $e->getMessage());
}
