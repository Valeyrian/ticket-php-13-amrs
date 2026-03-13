<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/contrat.php';
require_once __DIR__ . '/../../php/classes/client.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';
require_once __DIR__ . '/../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isClient()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];
$contratId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$contratId) {
    header("Location: /contrats/client/contrat-manager.php");
    exit;
}

$contrat = Contrat::getById($pdo, $contratId);
if (!$contrat) {
    header("Location: /contrats/client/contrat-manager.php?error=contrat_introuvable");
    exit;
}

// Vérifier que l'utilisateur a accès à ce contrat
$clients = $contrat->getClients($pdo);
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());
$mesClientIds = array_map(fn($c) => $c->getId(), $mesClients);
$hasAccess = false;
foreach ($clients as $client) {
    if (in_array($client['id'], $mesClientIds)) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    header("Location: /contrats/client/contrat-manager.php?error=acces_refuse");
    exit;
}

// Recalculer les heures consommées
$contrat->recalculerHeuresConsommees($pdo);

// Données du contrat
$pourcentage = $contrat->getPourcentageConsomme();
$heuresRestantes = $contrat->getHeuresRestantes();
$heuresConsommees = $contrat->getHeuresConsommees();
$heuresTotales = $contrat->getHeuresTotales();
$tauxHoraire = $contrat->getTauxHoraire();
$montantTotal = $contrat->getMontantTotal();
$montantConsomme = $heuresConsommees * $tauxHoraire;
$montantRestant = $montantTotal - $montantConsomme;

// SVG circular progress
$circumference = 2 * M_PI * 80; // ~502.4
$offset = $circumference * (1 - $pourcentage / 100);

// Client
$clientNom = !empty($clients) ? $clients[0]['nom'] : '—';

// Projets liés
$projets = $contrat->getProjets($pdo);

// Statut visuel
$statutClass = 'active';
$statutLabel = 'Actif';
if ($contrat->getStatut() === 'termine') {
    $statutClass = 'completed';
    $statutLabel = 'Terminé';
} elseif ($contrat->getStatut() === 'inactif') {
    $statutClass = 'inactive';
    $statutLabel = 'Suspendu';
} elseif ($pourcentage >= 90 && $contrat->isActif()) {
    $statutClass = 'warning';
    $statutLabel = 'Bientôt épuisé';
}

// Dates formatées
$dateDebutFormatted = $contrat->getDateDebut() ? date('d/m/Y', strtotime($contrat->getDateDebut())) : '—';
$dateFinFormatted = $contrat->getDateFin() ? date('d/m/Y', strtotime($contrat->getDateFin())) : '—';

// Durée en mois
$duree = '—';
if ($contrat->getDateDebut() && $contrat->getDateFin()) {
    $d1 = new DateTime($contrat->getDateDebut());
    $d2 = new DateTime($contrat->getDateFin());
    $diff = $d1->diff($d2);
    $mois = $diff->m + ($diff->y * 12);
    $duree = $mois . ' mois';
}

// Saisies de temps : tous les ticket_temps des tickets liés aux projets du contrat
$timeEntries = [];
foreach ($projets as $p) {
    $projetObj = Projet::getById($pdo, $p['id']);
    if (!$projetObj)
        continue;
    $tickets = Ticket::getByProjetId($pdo, $p['id']);
    foreach ($tickets as $t) {
        $temps = $t->getTemps($pdo);
        foreach ($temps as $entry) {
            $entry['ticket_id'] = $t->getId();
            $entry['ticket_titre'] = $t->getTitre();
            $timeEntries[] = $entry;
        }
    }
}
// Trier par date desc
usort($timeEntries, function ($a, $b) {
    return strtotime($b['date_travail']) - strtotime($a['date_travail']);
});

$contratIdFormatted = '#CT' . str_pad($contrat->getId(), 3, '0', STR_PAD_LEFT);

// Calcul TVA
$montantTVA = $montantTotal * 0.20;
$montantTTC = $montantTotal + $montantTVA;
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./generic-contrat.css" />
    <title>Détail Contrat <?= $contratIdFormatted ?> - Vector</title>
    <script src="../../js/theme.js"></script>
</head>

<body class="oswald-font1 role-client">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Détail du Contrat</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/contrats/client/contrat-manager.php" class="nav-btn">Mes Contrats</a>
                <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
            </nav>

            <div class="profile-info">
                <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
                <div class="profile-text">
                    <span
                        class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
                    <span class="profile-role">Client</span>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="contract-detail">
            <!-- Contract Header -->
            <div class="contract-header-section">
                <div class="contract-title-block">
                    <div class="contract-id"><?= $contratIdFormatted ?></div>
                    <h2 class="contract-name"><?= htmlspecialchars($contrat->getNom()) ?></h2>
                    <div class="contract-status-badge <?= $statutClass ?>"><?= $statutLabel ?></div>
                </div>
            </div>

            <!-- Main Info Grid -->
            <div class="contract-info-grid">
                <!-- General Information -->
                <div class="info-card">
                    <h3>Informations générales</h3>
                    <div class="info-row">
                        <span class="info-label">Type de contrat:</span>
                        <span class="info-value"><?= htmlspecialchars($contrat->getType()) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date de début:</span>
                        <span class="info-value"><?= $dateDebutFormatted ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date de fin:</span>
                        <span class="info-value"><?= $dateFinFormatted ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Durée:</span>
                        <span class="info-value"><?= $duree ?></span>
                    </div>
                    <?php if ($contrat->getConditions()): ?>
                        <div class="info-row">
                            <span class="info-label">Conditions:</span>
                            <span class="info-value"><?= nl2br(htmlspecialchars($contrat->getConditions())) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Hours Details -->
                <div class="info-card hours-card">
                    <h3>Détails des heures</h3>
                    <div class="hours-big-progress">
                        <div class="hours-circle">
                            <svg width="180" height="180">
                                <circle cx="90" cy="90" r="80" fill="none" stroke="#3a3b3e" stroke-width="12" />
                                <circle cx="90" cy="90" r="80" fill="none"
                                    stroke="<?= $pourcentage >= 90 ? '#e67e22' : '#9b59b6' ?>" stroke-width="12"
                                    stroke-dasharray="<?= round($circumference, 2) ?>"
                                    stroke-dashoffset="<?= round($offset, 2) ?>" transform="rotate(-90 90 90)" />
                            </svg>
                            <div class="hours-center">
                                <span class="hours-percent"><?= round($pourcentage) ?>%</span>
                                <span class="hours-label">consommées</span>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heures incluses:</span>
                        <span class="info-value"><?= $heuresTotales ?>h</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heures consommées:</span>
                        <span class="info-value text-warning"><?= $heuresConsommees ?>h</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heures restantes:</span>
                        <span class="info-value success"><?= $heuresRestantes ?>h</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heures supplémentaires:</span>
                        <span class="info-value"><?= max(0, $heuresConsommees - $heuresTotales) ?>h</span>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="info-card financial-card">
                    <h3>Détails financiers</h3>
                    <div class="info-row">
                        <span class="info-label">Montant HT:</span>
                        <span class="info-value"><?= number_format($montantTotal, 0, ',', ' ') ?> €</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TVA (20%):</span>
                        <span class="info-value"><?= number_format($montantTVA, 0, ',', ' ') ?> €</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Montant TTC:</span>
                        <span class="info-value total"><?= number_format($montantTTC, 0, ',', ' ') ?> €</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tarif horaire:</span>
                        <span class="info-value"><?= number_format($tauxHoraire, 0, ',', ' ') ?> € HT</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Facturation:</span>
                        <span class="info-value">Annuelle</span>
                    </div>
                </div>
            </div>

            <!-- Projects Using This Contract -->
            <div class="section-card">
                <h3>Projets liés à ce contrat</h3>
                <div class="projects-list">
                    <?php if (!empty($projets)): ?>
                        <?php foreach ($projets as $p):
                            $projetObj = Projet::getById($pdo, $p['id']);
                            $heuresProjet = $projetObj ? $projetObj->getTotalTempsPasse($pdo) : 0;
                            $projetStatut = $p['statut'] ?? 'actif';
                            $projetStatutClass = $projetStatut === 'actif' ? 'status-active' : 'status-completed';
                            $projetStatutLabel = $projetStatut === 'actif' ? 'Actif' : 'Archivé';
                            ?>
                            <div class="project-item">
                                <div class="project-info">
                                    <a href="/projects/client/generic-project.php?id=<?= $p['id'] ?>" class="project-link">
                                        #P<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?> -
                                        <?= htmlspecialchars($p['nom']) ?>
                                    </a>
                                    <span class="project-hours"><?= $heuresProjet ?>h consommées</span>
                                </div>
                                <span class="status-badge <?= $projetStatutClass ?>"><?= $projetStatutLabel ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--text-gray);padding:15px 0">Aucun projet associé à ce contrat.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Consumption Details -->
            <div class="section-card">
                <h3>Détail de consommation</h3>
                <div class="table-container">
                    <table class="consumption-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Ticket</th>
                                <th>Description</th>
                                <th>Collaborateur</th>
                                <th>Heures</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($timeEntries)): ?>
                                <?php foreach ($timeEntries as $entry):
                                    $collabName = ($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? '');
                                    $dateFormatted = date('d/m/Y', strtotime($entry['date_travail']));
                                    ?>
                                    <tr>
                                        <td><?= $dateFormatted ?></td>
                                        <td>
                                            <a href="/tickets/client/generic-ticket.php?id=<?= $entry['ticket_id'] ?>"
                                                class="ticket-link">
                                                #T<?= str_pad($entry['ticket_id'], 3, '0', STR_PAD_LEFT) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($entry['commentaire'] ?? $entry['ticket_titre'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars(trim($collabName)) ?></td>
                                        <td><?= $entry['duree'] ?>h</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--text-gray);padding:30px">Aucune
                                        saisie
                                        de temps.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <h3>Besoin d'aide ?</h3>
                <p>
                    Pour toute question concernant votre contrat, contactez notre service client :
                </p>
                <div class="contact-info">
                    <div class="contact-item">
                        <img src="/assets/enveloppe.png" alt="email" class="inline-icon" />
                        <a href="mailto:contrats@vector.com">contrats@vector.com</a>
                    </div>
                    <div class="contact-item">
                        <img src="/assets/telephone.png" alt="téléphone" class="inline-icon" />
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vector - Tous droits réservés</p>
    </footer>
</body>

</html>