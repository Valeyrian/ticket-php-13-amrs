<?php

require_once '../../db.php';
require_once '../classes/projet.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$project_id = intval($_POST['project_id'] ?? 0);

if ($project_id <= 0) {
    die("ID projet invalide.");
}

try {
    Projet::deleteById($pdo, $project_id);
    header('Location: /projects/admin/project-manager.php?success=deleted');
    exit;
} catch (Exception $e) {
    die('Erreur lors de la suppression du projet : ' . $e->getMessage());
}
