<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/contrat.php';
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

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!$id) {
    header("Location: /contrats/admin/contrat-manager.php?error=id_invalide");
    exit;
}

try {
    Contrat::deleteById($pdo, $id);
    header("Location: /contrats/admin/contrat-manager.php?success=contrat_deleted");
    exit;
} catch (Exception $e) {
    header("Location: /contrats/admin/contrat-manager.php?error=serveur");
    exit;
}
