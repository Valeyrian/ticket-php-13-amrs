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

$name = trim($_POST['name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$role = trim($_POST['role'] ?? 'client');
$state = trim($_POST['state'] ?? 'active');

// Déterminer l'entreprise (uniquement pour les clients)
$company = '';
if ($role === 'client') {
    $companyChoice = trim($_POST['company'] ?? '');
    if ($companyChoice === '__new__') {
        $company = trim($_POST['new_company'] ?? '');
    } else {
        $company = $companyChoice;
    }
    if (empty($company)) {
        header("Location: /utilisateurs/admin/utilisateur-manager.php?error=entreprise_requise");
        exit;
    }
}


// Vérifier que l'email n'existe pas déjà
if (User::checkEmailExists($pdo, $email)) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=email_existe");
    exit;
}


try {

    $newUser = new User(
        id: null,
        name: $name,
        surname: $surname,
        email: $email,
        password: password_hash($password, PASSWORD_BCRYPT),
        role: $role,
        creationDate: date('Y-m-d-H:i:s'),
        state: $state,
        company: $company
    );

    $newUser->registerNewUser($pdo);

    header("Location: /utilisateurs/admin/utilisateur-manager.php?success=user_added");
    exit;
} catch (Exception $e) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=serveur");
    exit;
}
