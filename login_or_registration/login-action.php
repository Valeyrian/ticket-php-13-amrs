<?php

require_once '../db.php';
require_once '../php/classes/user.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérifier si l'utilisateur existe et authentifier
    $user = User::login($pdo, $email, $password);


    if ($user) {
        session_start();
        $_SESSION['user'] = $user;

        if ($user->isAdmin()) {
            header("Location: ../dashboard/admin/dashboard.php");
        } elseif ($user->isCollaborateur()) {
            header("Location: ../dashboard/collaborateur/dashboard.php");
        } elseif ($user->isClient()) {
            header("Location: ../dashboard/client/dashboard.php");
        } else {
            die("Rôle utilisateur inconnu.");
        }

        exit;
    } else {
        // die($email . " " . $password);
        header("Location: ./login.php?error=1");
        exit;
    }
} else {
    die("Méthode non autorisée.");
}



