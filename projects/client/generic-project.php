<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/client.php';
require_once __DIR__ . '/../../php/classes/contrat.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';
require_once __DIR__ . '/../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isClient()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];
$projetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$projetId) {
    header("Location: /projects/client/project-manager.php");
    exit;
}

$projet = Projet::getById($pdo, $projetId);
if (!$projet) {
    header("Location: /projects/client/project-manager.php?error=projet_introuvable");
    exit;
}

// Vérifier que l'utilisateur a accès à ce projet
$client = $projet->getClient($pdo);
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());
$mesClientIds = array_map(fn($c) => $c->getId(), $mesClients);
$hasAccess = $client && in_array($client->getId(), $mesClientIds);

if (!$hasAccess) {
    header("Location: /projects/client/project-manager.php?error=acces_refuse");
    exit;
}

// Données liées
$collabs = $projet->getCollaborateurs($pdo);
$tickets = Ticket::getByProjetId($pdo, $projet->getId());
$contrats = Contrat::getByProjet($pdo, $projet->getId());
$contrat = !empty($contrats) ? $contrats[0] : null;

// Stats tickets
$nbTickets = count($tickets);
$nbOuverts = count(array_filter($tickets, fn($t) => $t->isOpen()));
$nbEnCours = count(array_filter($tickets, fn($t) => $t->getStatut() === 'en_cours'));
$nbTermines = count(array_filter($tickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide', 'ferme'])));
$nbAValider = count(array_filter($tickets, fn($t) => $t->getValidationStatus() === 'en_attente'));

// Heures
$tempsPasse = $projet->getTotalTempsPasse($pdo);
$tempsEstime = $projet->getTotalTempsEstime($pdo);
$heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
$heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : $tempsPasse;
$heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;
$pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

// Progression du projet (basée sur tickets terminés)
$progressionProjet = $nbTickets > 0 ? round(($nbTermines / $nbTickets) * 100) : 0;

// Format dates
function formatDateFr(?string $date): string
{
    if (!$date)
        return '-';
    $d = new DateTime($date);
    return $d->format('d/m/Y');
}

// Badge statut
$statusBadgeClass = $projet->isActif() ? 'active' : 'archived';
$statusBadgeText = $projet->isActif() ? 'Actif' : 'Archivé';

// Tickets à valider
$ticketsAValider = array_filter($tickets, fn($t) => $t->getValidationStatus() === 'en_attente');

// Chef de projet (premier collaborateur ou premier de la liste)
$chefProjet = !empty($collabs) ? $collabs[0] : null;

$projetIdFormatted = '#P' . str_pad($projet->getId(), 3, '0', STR_PAD_LEFT);
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./generic-project.css" />
    <title>Projet - <?= htmlspecialchars($projet->getNom()) ?> - Vector</title>
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

            <h1 class="dashboard-title">Détail du Projet</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
                <a href="/tickets/client/ticket-manager.php" class="nav-btn">Mes Tickets</a>
                <a href="/contrats/client/contrat-manager.php" class="nav-btn">Mes Contrats</a>
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
        <div class="project-detail">
            <!-- En-tête du projet -->
            <div class="project-header-section">
                <div class="project-title-block">
                    <div class="project-id"><?= $projetIdFormatted ?></div>
                    <h2 class="project-name"><?= htmlspecialchars($projet->getNom()) ?></h2>
                    <div class="project-status-badge <?= $statusBadgeClass ?>"><?= $statusBadgeText ?></div>
                </div>
                <div class="project-actions">
                    <?php if ($contrat): ?>
                        <a href="/contrats/client/generic-contrat.php?id=<?= $contrat->getId() ?>" class="btn-contract">
                            <img src="/assets/contrat.png" alt="contrat" /> Voir le contrat
                        </a>
                    <?php endif; ?>
                    <?php if ($nbAValider > 0): ?>
                        <button class="btn-validate">Tickets à valider (<?= $nbAValider ?>)</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alerte contrat -->
            <?php if ($contrat && $pourcentage >= 75): ?>
                <div class="alert-box <?= $pourcentage >= 90 ? 'warning' : 'info' ?>">
                    <div class="alert-icon">
                        <img src="/assets/avertissement.png" alt="attention" />
                    </div>
                    <div class="alert-content">
                        <strong><?= $pourcentage >= 90 ? 'Attention:' : 'Information:' ?></strong> Vous avez consommé
                        <?= $pourcentage ?>% de vos heures
                        disponibles. Il reste <strong><?= $heuresRestantes ?>h</strong> sur votre contrat initial.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informations principales -->
            <div class="project-info-grid">
                <div class="info-card">
                    <h3>Informations générales</h3>
                    <div class="info-row">
                        <span class="info-label">Entreprise:</span>
                        <span class="info-value"><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date de début:</span>
                        <span class="info-value"><?= formatDateFr($projet->getDateDebut()) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date de fin prévue:</span>
                        <span class="info-value"><?= formatDateFr($projet->getDateFinPrevue()) ?></span>
                    </div>
                    <?php if ($chefProjet): ?>
                        <div class="info-row">
                            <span class="info-label">Chef de projet:</span>
                            <span
                                class="info-value"><?= htmlspecialchars($chefProjet['name'] . ' ' . $chefProjet['surname']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($contrat): ?>
                    <div class="info-card contract-card">
                        <h3>Détails du contrat</h3>
                        <div class="info-row">
                            <span class="info-label">Type:</span>
                            <span class="info-value"><?= htmlspecialchars($contrat->getType()) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Heures consommées:</span>
                            <span class="info-value text-warning"><?= $heuresConsommees ?>h</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Heures restantes:</span>
                            <span class="info-value text-success"><?= $heuresRestantes ?>h</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Heures en attente:</span>
                            <span
                                class="info-value"><?= array_sum(array_map(fn($t) => $t->getTempsEstime() - $t->getTempsPasse(), array_filter($tickets, fn($t) => $t->isOpen()))) ?>h</span>
                        </div>
                        <div class="contract-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= min($pourcentage, 100) ?>%"></div>
                            </div>
                            <span class="progress-text"><?= $pourcentage ?>% du forfait consommé</span>
                        </div>
                        <a href="/contrats/client/generic-contrat.php?id=<?= $contrat->getId() ?>"
                            class="btn-contract-full">
                            <img src="/assets/contrat.png" alt="contrat" /> Voir le contrat complet
                        </a>
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <h3>Statistiques du projet</h3>
                    <div class="info-row">
                        <span class="info-label">Tickets ouverts:</span>
                        <span class="info-value"><?= $nbOuverts ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tickets résolus:</span>
                        <span class="info-value"><?= $nbTermines ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">À valider:</span>
                        <span class="info-value text-warning"><?= $nbAValider ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Progression:</span>
                        <span class="info-value text-success"><?= $progressionProjet ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="project-section">
                <h3>Description du projet</h3>
                <p class="project-description">
                    <?= nl2br(htmlspecialchars($projet->getDescription() ?? 'Aucune description disponible.')) ?>
                </p>
            </div>

            <!-- Équipe -->
            <div class="project-section">
                <h3>Équipe assignée</h3>
                <div class="team-grid">
                    <?php foreach ($collabs as $collab):
                        $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                        $fullName = htmlspecialchars($collab['name'] . ' ' . $collab['surname']);
                        ?>
                        <div class="team-member-card">
                            <div class="member-avatar"><?= $initials ?></div>
                            <div class="member-info">
                                <div class="member-name"><?= $fullName ?></div>
                                <div class="member-role"><?= htmlspecialchars($collab['role'] ?? 'Collaborateur') ?></div>
                                <div class="member-contact">
                                    <span><img src="/assets/enveloppe.png" alt="email" />
                                        <?= htmlspecialchars($collab['email']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($collabs)): ?>
                        <p style="color:var(--text-gray)">Aucun collaborateur assigné.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tickets à valider -->
            <?php if (!empty($ticketsAValider)): ?>
                <div class="project-section">
                    <div class="section-header">
                        <h3>Tickets en attente de validation</h3>
                        <span class="badge-count"><?= count($ticketsAValider) ?>
                            ticket<?= count($ticketsAValider) > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="tickets-table-container">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Assigné à</th>
                                    <th>Type</th>
                                    <th>Temps passé</th>
                                    <th>Résolu le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ticketsAValider as $t):
                                    $tCollabs = $t->getCollaborateurs($pdo);
                                    $typeClass = $t->getType() === 'facturable' ? 'facturable' : 'inclus';
                                    $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                                    ?>
                                    <tr class="needs-validation">
                                        <td>
                                            <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>"
                                                class="ticket-link">#<?= $t->getId() ?></a>
                                        </td>
                                        <td class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></td>
                                        <td>
                                            <div class="collaborators-mini">
                                                <?php foreach ($tCollabs as $tc):
                                                    $tcInit = strtoupper(mb_substr($tc['name'], 0, 1) . mb_substr($tc['surname'], 0, 1));
                                                    ?>
                                                    <div class="avatar-tiny"><?= $tcInit ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                                        <td><?= $t->getTempsPasse() ?>h</td>
                                        <td><?= $t->getDateModification() ? date('d M Y', strtotime($t->getDateModification())) : '-' ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>"
                                                    class="btn-view" title="Voir"></a>
                                                <button class="btn-validate-ticket" title="Valider"></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tous les tickets du projet -->
            <div class="project-section">
                <h3>Tous les tickets du projet</h3>
                <div class="tickets-table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Assigné à</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Type</th>
                                <th>Temps</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t):
                                $tCollabs = $t->getCollaborateurs($pdo);
                                // Priority
                                $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                                $pInfo = $priorityMap[$t->getPriorite()] ?? ['medium', $t->getPriorite()];
                                // Status
                                $statutMap = [
                                    'nouveau' => ['nouveau', 'Nouveau'],
                                    'en_cours' => ['progress', 'En cours'],
                                    'en_attente_client' => ['pending', 'En attente'],
                                    'termine' => ['completed', 'Terminé'],
                                    'a_valider' => ['validate', 'À valider'],
                                    'valide' => ['validated', 'Validé'],
                                    'refuse' => ['refused', 'Refusé'],
                                ];
                                $sInfo = $statutMap[$t->getStatut()] ?? ['pending', $t->getStatut()];
                                // Type
                                $typeClass = $t->getType() === 'facturable' ? 'facturable' : 'inclus';
                                $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                                // Row class
                                $rowClass = $t->getValidationStatus() === 'valide' ? 'validated' : '';
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>"
                                            class="ticket-link">#<?= $t->getId() ?></a>
                                    </td>
                                    <td class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></td>
                                    <td>
                                        <div class="collaborators-mini">
                                            <?php foreach ($tCollabs as $tc):
                                                $tcInit = strtoupper(mb_substr($tc['name'], 0, 1) . mb_substr($tc['surname'], 0, 1));
                                                ?>
                                                <div class="avatar-tiny"><?= $tcInit ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                                    <td><span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span></td>
                                    <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                                    <td><?= $t->getTempsPasse() ?>h<?= $t->getTempsEstime() > 0 ? '/' . $t->getTempsEstime() . 'h' : '' ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>"
                                                class="btn-view" title="Voir"></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Aucun
                                        ticket
                                        pour ce projet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Timeline / Historique -->
            <div class="project-section">
                <h3>Activité récente</h3>
                <div class="timeline">
                    <?php
                    // Construire l'historique à partir des tickets
                    $historyItems = [];
                    if ($projet->getDateCreation()) {
                        $historyItems[] = [
                            'date' => $projet->getDateCreation(),
                            'text' => '<strong>Projet créé</strong>',
                        ];
                    }
                    foreach ($tickets as $t) {
                        if ($t->getDateModification() && $t->getDateModification() !== $t->getDateCreation()) {
                            $historyItems[] = [
                                'date' => $t->getDateModification(),
                                'text' => '<strong>Ticket #' . $t->getId() . '</strong> - ' . htmlspecialchars($t->getTitre()) . ' mis à jour',
                            ];
                        }
                        if ($t->getDateCreation()) {
                            $historyItems[] = [
                                'date' => $t->getDateCreation(),
                                'text' => '<strong>Ticket #' . $t->getId() . '</strong> créé',
                            ];
                        }
                    }
                    usort($historyItems, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                    $historyItems = array_slice($historyItems, 0, 10);
                    ?>
                    <?php foreach ($historyItems as $item):
                        $itemDate = new DateTime($item['date']);
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?= $itemDate->format('d M Y - H:i') ?></div>
                            <div class="timeline-content">
                                <div class="timeline-icon"></div>
                                <div class="timeline-text"><?= $item['text'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($historyItems)): ?>
                        <p style="color:var(--text-muted)">Aucun historique.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vector - Tous droits réservés</p>
    </footer>
</body>

</html>