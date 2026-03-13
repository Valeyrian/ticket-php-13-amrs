<?php

require_once '../../db.php';
require_once '../classes/user.php';

session_start();

// Vérifier que l'utilisateur est connecté et admin
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$id = (int) ($_POST['id'] ?? 0);

if (!$id) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=id_invalide");
    exit;
}

// Empêcher l'auto-suppression
if ($id === $_SESSION['user']->getId()) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=auto_suppression");
    exit;
}

try {
    $deleted = User::deleteById($pdo, $id);

    if ($deleted) {
        header("Location: /utilisateurs/admin/utilisateur-manager.php?success=user_deleted");
    } else {
        header("Location: /utilisateurs/admin/utilisateur-manager.php?error=introuvable");
    }
    exit;
} catch (PDOException $e) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=serveur");
    exit;
}
