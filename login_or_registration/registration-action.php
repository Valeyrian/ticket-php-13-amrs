<?php 

require_once '../db.php';
require_once '../php/classes/user.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $company = $_POST['company'] ?? '';


    // Vérifier si l'email existe déjà
    if (User::checkEmailExists($pdo, $email)) {
        die("Cette adresse email est déjà utilisée.");
    }

    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Créer un nouvel utilisateur
    $user = new User(
        null,
        $firstName,
        $lastName,
        $email,
        $hashedPassword,
        'client',
        date('Y-m-d H:i:s'),
        'active',
        $company
    );

    // Insérer l'utilisateur dans la base de données
    
    try {
        $user->registerNewUser($pdo);
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        die("Erreur lors de l'inscription : " . $e->getMessage());
    }

} 
else {
    die("Méthode non autorisée.");
}