<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/contrat.php';
require_once __DIR__ . '/../classes/client.php';
require_once __DIR__ . '/../classes/projet.php';
require_once __DIR__ . '/../classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /contrats/admin/contrat-manager.php");
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$type = trim($_POST['type'] ?? 'Inclus');
$heures_totales = (float) ($_POST['heures_totales'] ?? 0);
$heures_consommees = (float) ($_POST['heures_consommees'] ?? 0);
$taux_horaire = (float) ($_POST['taux_horaire'] ?? 0);
$montant_total = (float) ($_POST['montant_total'] ?? 0);
$date_debut = trim($_POST['date_debut'] ?? '');
$date_fin = trim($_POST['date_fin'] ?? '');
$conditions = trim($_POST['conditions'] ?? '');
$statut = trim($_POST['statut'] ?? 'actif');
$client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
$projet_id = isset($_POST['projet_id']) ? (int) $_POST['projet_id'] : 0;

// Calcul automatique du montant si pas renseigné
if ($montant_total <= 0 && $heures_totales > 0 && $taux_horaire > 0) {
    $montant_total = $heures_totales * $taux_horaire;
}

if (empty($nom) || !$client_id || $heures_totales <= 0 || empty($date_debut) || empty($date_fin)) {
    header("Location: /contrats/admin/contrat-manager.php?error=champs_requis");
    exit;
}

try {
    $contrat = new Contrat(
        id: null,
        nom: $nom,
        type: $type,
        heures_totales: $heures_totales,
        heures_consommees: $heures_consommees,
        taux_horaire: $taux_horaire,
        montant_total: $montant_total,
        date_debut: $date_debut,
        date_fin: $date_fin,
        conditions: $conditions,
        statut: $statut
    );
    $contrat->create($pdo);

    // Lier au client
    $contrat->addClient($pdo, $client_id);

    // Lier au projet si spécifié
    if ($projet_id > 0) {
        $contrat->addProjet($pdo, $projet_id);
    }

    header('Location: /contrats/admin/contrat-manager.php?success=contrat_added');
    exit;
} catch (Exception $e) {
    header("Location: /contrats/admin/contrat-manager.php?error=" . urlencode($e->getMessage()));
    exit;
}
