<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /utilisateurs/admin/utilisateur-manager.php");
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if (!$id) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=id_invalide");
    exit;
}

$targetUser = User::getById($pdo, $id);
if (!$targetUser) {
    header("Location: /utilisateurs/admin/utilisateur-manager.php?error=utilisateur_introuvable");
    exit;
}

$redirect = "/utilisateurs/admin/generic-utilisateur.php?id=" . $id;

try {
    switch ($action) {

        case 'toggle_state':
            $newState = ($targetUser->getState() === 'active') ? 'inactive' : 'active';
            $targetUser->setState($newState);
            $targetUser->update($pdo);
            header("Location: " . $redirect . "&success=state_updated");
            exit;

        case 'update_info':
            $name = trim($_POST['name'] ?? '');
            $surname = trim($_POST['surname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $company = trim($_POST['company'] ?? '');

            if (empty($name) || empty($surname) || empty($email)) {
                header("Location: " . $redirect . "&error=champs_requis");
                exit;
            }

            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            if ($email !== $targetUser->getEmail() && User::checkEmailExists($pdo, $email)) {
                header("Location: " . $redirect . "&error=email_existe");
                exit;
            }

            $targetUser->setName($name);
            $targetUser->setSurname($surname);
            $targetUser->setEmail($email);
            if (in_array($role, ['admin', 'collaborateur', 'client'])) {
                // Utiliser directement la propriété car setRole attend 'collaborator' pas 'collaborateur'
                $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
                $stmt->execute([':role' => $role, ':id' => $id]);
            }
            $targetUser->setCompany($company);
            $targetUser->update($pdo);

            header("Location: " . $redirect . "&success=info_updated");
            exit;

        default:
            header("Location: " . $redirect . "&error=action_inconnue");
            exit;
    }
} catch (Exception $e) {
    header("Location: " . $redirect . "&error=serveur");
    exit;
}
