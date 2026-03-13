<?php

require_once '../../db.php';
require_once '../classes/client.php';
require_once '../classes/projet.php';
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

$nom = trim($_POST["nom"]);
$adresse = trim($_POST["adresse"]);
$cp = isset($_POST["code_postal"]) ? trim($_POST["code_postal"]) : '';
$ville = trim($_POST["ville"]);
$pays = trim($_POST["pays"]);
$statut = isset($_POST["statut"]) ? trim($_POST["statut"]) : 'actif';
$contact_principal = trim($_POST["contact_principal"] ?? '');


try {
    $newClient = new Client(
        id: null,
        nom: $nom,
        adresse: $adresse,
        code_postal: $cp,
        ville: $ville,
        pays: $pays,
        statut: $statut,
    );

    $newClient->create($pdo);

    if (!empty($contact_principal)) {
        $newClient->setContactPrincipal($pdo, $contact_principal);
    }

} catch (Exception $e) {
    die("Erreur lors de l'ajout du client : " . $e->getMessage());
    header("Location: /dashboard/admin/dashboard.php?error=exception_ajout_client");
    exit;
}
header("Location: /dashboard/admin/dashboard.php?success=ajout_client");
exit;
