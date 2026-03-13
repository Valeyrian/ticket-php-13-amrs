<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../classes/contrat.php';
require_once __DIR__ . '/../classes/ticket.php';
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
$action = $_POST['action'] ?? '';

if (!$id) {
    header("Location: /contrats/admin/contrat-manager.php?error=id_invalide");
    exit;
}

$contrat = Contrat::getById($pdo, $id);
if (!$contrat) {
    header("Location: /contrats/admin/contrat-manager.php?error=contrat_introuvable");
    exit;
}

$redirect = "/contrats/admin/generic-contrat.php?id=" . $id;

try {
    switch ($action) {

        case 'update_info':
            $nom = trim($_POST['nom'] ?? '');
            $type = trim($_POST['type'] ?? 'Inclus');
            $heures_totales = (float) ($_POST['heures_totales'] ?? 0);
            $taux_horaire = (float) ($_POST['taux_horaire'] ?? 0);
            $montant_total = (float) ($_POST['montant_total'] ?? 0);
            $statut = trim($_POST['statut'] ?? 'actif');
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            $conditions = trim($_POST['conditions'] ?? '');

            if (empty($nom) || $heures_totales <= 0 || $montant_total <= 0 || empty($date_debut) || empty($date_fin)) {
                header("Location: " . $redirect . "&error=champs_requis");
                exit;
            }

            $contrat->setNom($nom);
            $contrat->setType($type);
            $contrat->setHeuresTotales($heures_totales);
            $contrat->setTauxHoraire($taux_horaire);
            $contrat->setMontantTotal($montant_total);
            $contrat->setStatut($statut);
            $contrat->setDateDebut($date_debut);
            $contrat->setDateFin($date_fin);
            $contrat->setConditions($conditions);
            $contrat->update($pdo);

            header("Location: " . $redirect . "&success=info_updated");
            exit;

        case 'add_time':
            $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
            $collaborateurId = isset($_POST['collaborateur_id']) ? (int) $_POST['collaborateur_id'] : 0;
            $dateTravail = trim($_POST['date_travail'] ?? '');
            $duree = (float) ($_POST['duree'] ?? 0);
            $commentaire = trim($_POST['commentaire'] ?? '');

            if (!$ticketId || !$collaborateurId || empty($dateTravail) || $duree <= 0) {
                header("Location: " . $redirect . "&error=champs_requis");
                exit;
            }

            $ticket = Ticket::getById($pdo, $ticketId);
            if (!$ticket) {
                header("Location: " . $redirect . "&error=ticket_introuvable");
                exit;
            }

            $ticket->addTemps($pdo, $collaborateurId, $dateTravail, $duree, $commentaire ?: null);

            // Recalculer les heures consommées du contrat
            $contrat->recalculerHeuresConsommees($pdo);

            header("Location: " . $redirect . "&success=time_added");
            exit;

        default:
            header("Location: " . $redirect . "&error=action_inconnue");
            exit;
    }
} catch (Exception $e) {
    header("Location: " . $redirect . "&error=serveur");
    exit;
}
