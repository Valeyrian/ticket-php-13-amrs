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

if (!isset($_SESSION['user']) || !$_SESSION['user']->isCollaborateur()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];
$projetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$projetId) {
    header("Location: /projects/collaborateur/project-manager.php");
    exit;
}

$projet = Projet::getById($pdo, $projetId);
if (!$projet) {
    header("Location: /projects/collaborateur/project-manager.php?error=projet_introuvable");
    exit;
}

// Vérifier que le collaborateur est assigné à ce projet
$collabs = $projet->getCollaborateurs($pdo);
$collabIds = array_map(fn($c) => $c['id'], $collabs);
if (!in_array($user->getId(), $collabIds)) {
    header("Location: /projects/collaborateur/project-manager.php?error=acces_refuse");
    exit;
}

// Données liées
$client = $projet->getClient($pdo);
$tickets = Ticket::getByProjetId($pdo, $projet->getId());
$contrats = Contrat::getByProjet($pdo, $projet->getId());
$contrat = !empty($contrats) ? $contrats[0] : null;

// Mes tickets sur ce projet
$mesTickets = array_filter($tickets, function ($t) use ($pdo, $user) {
    $tCollabs = $t->getCollaborateurs($pdo);
    return in_array($user->getId(), array_map(fn($c) => $c['id'], $tCollabs));
});

$nbMesTickets = count($mesTickets);
$nbMesTicketsEnCours = count(array_filter($mesTickets, fn($t) => $t->getStatut() === 'en_cours'));

// Mon temps enregistré sur ce projet
$monTemps = 0;
foreach ($mesTickets as $mt) {
    $tempsEntries = $mt->getTempsByCollaborateur($pdo, $user->getId());
    $monTemps += array_sum(array_map(fn($e) => $e['duree'], $tempsEntries));
}

// Heures
$heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
$heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : 0;
$heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;
$pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

// Stats par collaborateur
$collabsStats = [];
foreach ($collabs as $collab) {
    $collabTickets = array_filter($tickets, function ($t) use ($pdo, $collab) {
        $tCollabs = $t->getCollaborateurs($pdo);
        return in_array($collab['id'], array_map(fn($c) => $c['id'], $tCollabs));
    });
    $collabTemps = 0;
    foreach ($collabTickets as $ct) {
        $tempsEntries = $ct->getTempsByCollaborateur($pdo, $collab['id']);
        $collabTemps += array_sum(array_map(fn($e) => $e['duree'], $tempsEntries));
    }
    $collabsStats[$collab['id']] = [
        'collab' => $collab,
        'nbTickets' => count($collabTickets),
        'temps' => $collabTemps,
    ];
}

// Format dates
function formatDateFr(?string $date): string
{
    if (!$date)
        return '-';
    $d = new DateTime($date);
    return $d->format('d/m/Y');
}

$statusBadgeClass = $projet->isActif() ? 'active' : 'archived';
$statusBadgeText = $projet->isActif() ? 'Actif' : 'Archivé';

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

<body class="oswald-font1 role-collaborateur">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Détail du Projet</h1>

            <nav class="header-nav">
                <a href="/dashboard/collaborateur/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/projects/collaborateur/project-manager.php" class="nav-btn">Mes Projets</a>
                <a href="/tickets/collaborateur/ticket-manager.php" class="nav-btn">Mes Tickets</a>
            </nav>

            <div class="profile-info">
                <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
                <div class="profile-text">
                    <span
                        class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
                    <span class="profile-role">Collaborateur</span>
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
                    <button class="btn-ticket" onclick="openTicketModal()">Créer un ticket</button>
                    <button class="btn-time" onclick="openTimeModal()">
                        <img src="/assets/heures-douverture.png" alt="temps" />
                        Enregistrer du temps
                    </button>
                </div>
            </div>

            <!-- Informations principales -->
            <div class="project-info-grid">
                <div class="info-card">
                    <h3>Informations générales</h3>
                    <div class="info-row">
                        <span class="info-label">Client:</span>
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
                </div>

                <div class="info-card">
                    <h3>Heures disponibles</h3>
                    <div class="info-row">
                        <span class="info-label">Heures consommées:</span>
                        <span class="info-value text-warning"><?= $heuresConsommees ?>h</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Heures restantes:</span>
                        <span class="info-value text-success"><?= $heuresRestantes ?>h</span>
                    </div>
                    <div class="contract-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min($pourcentage, 100) ?>%"></div>
                        </div>
                        <span class="progress-text"><?= $pourcentage ?>% consommé</span>
                    </div>
                </div>

                <div class="info-card">
                    <h3>Mes statistiques</h3>
                    <div class="info-row">
                        <span class="info-label">Mes tickets:</span>
                        <span class="info-value"><?= $nbMesTickets ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">En cours:</span>
                        <span class="info-value"><?= $nbMesTicketsEnCours ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Temps enregistré:</span>
                        <span class="info-value"><?= round($monTemps) ?>h</span>
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
                <h3>Équipe du projet</h3>
                <div class="team-grid">
                    <?php foreach ($collabsStats as $cStat):
                        $collab = $cStat['collab'];
                        $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                        $isMe = $collab['id'] === $user->getId();
                        $cardClass = $isMe ? 'current-user' : '';
                        $nameLabel = $isMe ? htmlspecialchars($collab['name'] . ' ' . $collab['surname']) . ' (Vous)' : htmlspecialchars($collab['name'] . ' ' . $collab['surname']);
                        ?>
                        <div class="team-member-card <?= $cardClass ?>">
                            <div class="member-avatar"><?= $initials ?></div>
                            <div class="member-info">
                                <div class="member-name"><?= $nameLabel ?></div>
                                <div class="member-role"><?= htmlspecialchars($collab['role'] ?? 'Collaborateur') ?></div>
                                <div class="member-stats">
                                    <span><?= $cStat['nbTickets'] ?> ticket<?= $cStat['nbTickets'] > 1 ? 's' : '' ?></span>
                                    <span><?= round($cStat['temps']) ?>h enregistrées</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mes tickets du projet -->
            <div class="project-section">
                <div class="section-header">
                    <h3>Mes tickets sur ce projet</h3>
                    <button class="btn-add" onclick="openTicketModal()">Nouveau ticket</button>
                </div>
                <div class="tickets-table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Type</th>
                                <th>Temps</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mesTickets as $t):
                                $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                                $pInfo = $priorityMap[$t->getPriorite()] ?? ['medium', $t->getPriorite()];
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
                                $typeClass = $t->getType() === 'facturable' ? 'facturable' : 'inclus';
                                $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                                ?>
                                <tr>
                                    <td>
                                        <a href="/tickets/collaborateur/generic-ticket.php?id=<?= $t->getId() ?>"
                                            class="ticket-link">#<?= $t->getId() ?></a>
                                    </td>
                                    <td class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></td>
                                    <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                                    <td><span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span></td>
                                    <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                                    <td><?= $t->getTempsPasse() ?>h<?= $t->getTempsEstime() > 0 ? '/' . $t->getTempsEstime() . 'h' : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mesTickets)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px">Aucun
                                        ticket
                                        assigné.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t):
                                $tCollabs = $t->getCollaborateurs($pdo);
                                $isMyTicket = in_array($user->getId(), array_map(fn($c) => $c['id'], $tCollabs));
                                $rowClass = $isMyTicket ? 'my-ticket' : '';

                                $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                                $pInfo = $priorityMap[$t->getPriorite()] ?? ['medium', $t->getPriorite()];
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
                                $typeClass = $t->getType() === 'facturable' ? 'facturable' : 'inclus';
                                $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        <a href="/tickets/collaborateur/generic-ticket.php?id=<?= $t->getId() ?>"
                                            class="ticket-link">#<?= $t->getId() ?></a>
                                    </td>
                                    <td class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></td>
                                    <td>
                                        <div class="collaborators-mini">
                                            <?php foreach ($tCollabs as $tc):
                                                $tcInit = strtoupper(mb_substr($tc['name'], 0, 1) . mb_substr($tc['surname'], 0, 1));
                                                $meClass = $tc['id'] === $user->getId() ? 'me' : '';
                                                ?>
                                                <div class="avatar-tiny <?= $meClass ?>"><?= $tcInit ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                                    <td><span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span></td>
                                    <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                                    <td><?= $t->getTempsPasse() ?>h<?= $t->getTempsEstime() > 0 ? '/' . $t->getTempsEstime() . 'h' : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Timeline / Historique -->
            <div class="project-section">
                <h3>Activité récente</h3>
                <div class="timeline">
                    <?php
                    $historyItems = [];
                    if ($projet->getDateCreation()) {
                        $historyItems[] = [
                            'date' => $projet->getDateCreation(),
                            'text' => '<strong>Projet créé</strong>',
                        ];
                    }
                    foreach ($mesTickets as $t) {
                        if ($t->getDateModification() && $t->getDateModification() !== $t->getDateCreation()) {
                            $historyItems[] = [
                                'date' => $t->getDateModification(),
                                'text' => '<strong>Ticket #' . $t->getId() . '</strong> mis à jour par vous',
                            ];
                        }
                        $tempsEntries = $t->getTempsByCollaborateur($pdo, $user->getId());
                        foreach ($tempsEntries as $entry) {
                            $historyItems[] = [
                                'date' => $entry['date_creation'] ?? $entry['date_travail'],
                                'text' => '<strong>' . $entry['duree'] . 'h</strong> enregistrées sur le ticket #' . $t->getId(),
                                'icon' => 'time',
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
                                <div class="timeline-icon">
                                    <?php if (isset($item['icon']) && $item['icon'] === 'time'): ?>
                                        <img src="/assets/heures-douverture.png" alt="temps" />
                                    <?php endif; ?>
                                </div>
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

    <!-- Modal Nouveau Ticket -->
    <div class="modal-overlay" id="modal-ticket-overlay">
        <div class="modal" id="add-ticket-modal">
            <div class="modal-header">
                <h2>Nouveau Ticket</h2>
                <button class="modal-close" id="close-ticket-modal">&times;</button>
            </div>
            <form id="add-ticket-form" class="modal-form" method="POST" action="/php/actions/add-ticket.php">
                <input type="hidden" name="projet_id" value="<?= $projet->getId() ?>" />
                <div class="form-section">
                    <h3>Informations du Ticket</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ticket-title">Titre *</label>
                            <input type="text" id="ticket-title" name="titre" required
                                placeholder="Ex: Intégration module authentification" />
                        </div>
                        <div class="form-group">
                            <label for="ticket-priority">Priorité *</label>
                            <select id="ticket-priority" name="priorite" required>
                                <option value="">Sélectionner</option>
                                <option value="basse">Basse</option>
                                <option value="moyenne" selected>Moyenne</option>
                                <option value="haute">Haute</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ticket-description">Description *</label>
                        <textarea id="ticket-description" name="description" rows="4" required
                            placeholder="Décrivez le ticket en détail..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Classification</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ticket-estimated-hours">Heures estimées</label>
                            <input type="number" id="ticket-estimated-hours" name="temps_estime" min="0" step="0.5"
                                placeholder="Ex: 4" value="0" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancel-ticket-modal">Annuler</button>
                    <button type="submit" class="btn-submit">Créer le ticket</button>
                    <div class="form-error" id="ticket-form-error">
                        Veuillez remplir tous les champs obligatoires.
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Enregistrer Temps -->
    <div class="modal-overlay" id="modal-time-overlay">
        <div class="modal" id="log-time-modal">
            <div class="modal-header">
                <h2>Enregistrer du temps</h2>
                <button class="modal-close" id="close-time-modal">&times;</button>
            </div>
            <form id="log-time-form" class="modal-form" method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="action" value="add_temps" />
                <input type="hidden" name="collaborateur_id" value="<?= $user->getId() ?>" />
                <div class="form-section">
                    <h3>Détails du temps</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="time-ticket">Ticket *</label>
                            <select id="time-ticket" name="ticket_id" required>
                                <option value="">Sélectionner un ticket</option>
                                <?php foreach ($mesTickets as $mt): ?>
                                    <option value="<?= $mt->getId() ?>">#<?= $mt->getId() ?> -
                                        <?= htmlspecialchars($mt->getTitre()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="time-date">Date *</label>
                            <input type="date" id="time-date" name="date_travail" required
                                value="<?= date('Y-m-d') ?>" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="time-duration">Durée (heures) *</label>
                            <input type="number" id="time-duration" name="duree" min="0" step="0.5" required
                                placeholder="Ex: 2.5" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="time-description">Description de l'activité</label>
                        <textarea id="time-description" name="commentaire" rows="3"
                            placeholder="Décrivez ce que vous avez fait..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancel-time-modal">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer le temps</button>
                    <div class="form-error" id="time-form-error">
                        Veuillez remplir tous les champs obligatoires.
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/form-validation.js"></script>
    <script src="./generic-project.js"></script>
</body>

</html>