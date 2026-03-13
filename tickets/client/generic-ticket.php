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
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$ticketId) {
    header("Location: /tickets/client/ticket-manager.php");
    exit;
}

$ticket = Ticket::getById($pdo, $ticketId);
if (!$ticket) {
    header("Location: /tickets/client/ticket-manager.php?error=ticket_introuvable");
    exit;
}

// Vérifier l'accès
$projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
$client = $projet ? $projet->getClient($pdo) : null;
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());
$mesClientIds = array_map(fn($c) => $c->getId(), $mesClients);
$hasAccess = $client && in_array($client->getId(), $mesClientIds);

if (!$hasAccess) {
    header("Location: /tickets/client/ticket-manager.php?error=acces_refuse");
    exit;
}

// Données liées
$collabs = $ticket->getCollaborateurs($pdo);
$tempsEntries = $ticket->getTemps($pdo);
$commentaires = $ticket->getCommentaires($pdo);
$contrats = $projet ? Contrat::getByProjet($pdo, $projet->getId()) : [];
$contrat = !empty($contrats) ? $contrats[0] : null;

// Stats projet
$projetTicketsOuverts = 0;
$projetProgression = 0;
if ($projet) {
    $projetTickets = Ticket::getByProjetId($pdo, $projet->getId());
    $projetTicketsOuverts = count(array_filter($projetTickets, fn($t) => $t->isOpen()));
    $projetTempsPasse = $projet->getTotalTempsPasse($pdo);
    $projetTempsEstime = $projet->getTotalTempsEstime($pdo);
    $projetProgression = $projetTempsEstime > 0 ? round(($projetTempsPasse / $projetTempsEstime) * 100) : 0;
}

// Temps restant
$tempsRestant = max(0, $ticket->getTempsEstime() - $ticket->getTempsPasse());

// Statut badges
$statutMap = [
    'nouveau' => ['status-nouveau', 'Nouveau'],
    'en_cours' => ['status-progress', 'En cours'],
    'en_attente_client' => ['status-pending', 'En attente'],
    'termine' => ['status-completed', 'Terminé'],
    'a_valider' => ['status-validate', 'À valider'],
    'valide' => ['status-completed', 'Validé'],
    'refuse' => ['status-refused', 'Refusé'],
];
$sInfo = $statutMap[$ticket->getStatut()] ?? ['status-pending', $ticket->getStatut()];

$priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
$pInfo = $priorityMap[$ticket->getPriorite()] ?? ['medium', $ticket->getPriorite()];

$typeClass = $ticket->getType() === 'facturable' ? 'billing-facturable' : 'billing-inclus';
$typeLabel = $ticket->getType() === 'facturable' ? 'Facturable' : 'Inclus';

// Validation status
$validationMap = [
    'en_attente' => ['label' => 'En attente de validation', 'class' => 'pending'],
    'valide' => ['label' => 'Validé', 'class' => 'validated'],
    'refuse' => ['label' => 'Refusé', 'class' => 'refused'],
];
$vs = $ticket->getValidationStatus();
$vInfo = $validationMap[$vs] ?? null;

// Calcul coût si facturable
$tauxHoraire = 85; // €/h
$coutTotal = $ticket->getTempsPasse() * $tauxHoraire;
$depassement = max(0, $ticket->getTempsPasse() - $ticket->getTempsEstime());
$coutDepassement = $depassement * $tauxHoraire;

// Nombre de tickets ouverts pour le badge
$allTickets = [];
foreach ($mesClients as $mc) {
    $mcProjets = Projet::getByClient($pdo, $mc->getId());
    foreach ($mcProjets as $mcp) {
        $mcTickets = Ticket::getByProjetId($pdo, $mcp->getId());
        $allTickets = array_merge($allTickets, $mcTickets);
    }
}
$nbTicketsOuverts = count(array_filter($allTickets, fn($t) => $t->isOpen()));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./generic-ticket.css">
    <title>Détail Ticket #<?= $ticket->getId() ?> - Vector</title>
    <script src="../../js/theme.js"></script>
</head>

<body class="oswald-font1 role-client">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site"></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site"></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Détail Ticket</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/tickets/client/ticket-manager.php" class="nav-btn">
                    Tickets
                    <span class="badge"><?= $nbTicketsOuverts ?></span>
                </a>
                <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
                <a href="/contrats/client/contrat-manager.php" class="nav-btn">Contrats</a>
            </nav>

            <div class="profile-info">
                <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic">
                <div class="profile-text">
                    <span
                        class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
                    <span class="profile-role">Client</span>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="ticket-detail-container">
            <!-- En-tête du ticket -->
            <div class="ticket-header">
                <div class="ticket-header-left">
                    <span class="ticket-id">#<?= $ticket->getId() ?></span>
                    <h2 class="ticket-title-main"><?= htmlspecialchars($ticket->getTitre()) ?></h2>
                </div>
                <div class="ticket-header-right">
                    <span class="read-only-badge">Lecture seule</span>
                </div>
            </div>

            <!-- Métadonnées principales (lecture seule) -->
            <div class="ticket-metadata">
                <div class="metadata-card">
                    <label>Statut</label>
                    <div class="metadata-value">
                        <span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span>
                    </div>
                </div>

                <div class="metadata-card">
                    <label>Priorité</label>
                    <div class="metadata-value">
                        <span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span>
                    </div>
                </div>

                <div class="metadata-card">
                    <label>Type</label>
                    <div class="metadata-value">
                        <span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span>
                    </div>
                </div>

                <div class="metadata-card">
                    <label>Temps estimé</label>
                    <div class="metadata-value time-value"><?= $ticket->getTempsEstime() ?>h</div>
                </div>

                <div class="metadata-card highlight">
                    <label>Temps passé total</label>
                    <div class="time-total"><?= $ticket->getTempsPasse() ?>h</div>
                    <div class="time-remaining">Reste: <?= $tempsRestant ?>h</div>
                </div>
            </div>

            <!-- Informations projet et contrat -->
            <div class="project-client-info">
                <div class="info-card">
                    <h3>Projet</h3>
                    <p class="project-name"><?= $projet ? htmlspecialchars($projet->getNom()) : 'Aucun projet' ?></p>
                    <?php if ($projet): ?>
                        <div class="project-stats-small">
                            <span>Statut: <strong><?= $projet->isActif() ? 'Actif' : 'Archivé' ?></strong></span>
                            <span>Progression: <strong><?= $projetProgression ?>%</strong></span>
                            <span>Tickets ouverts: <strong><?= $projetTicketsOuverts ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Contrat associé</h3>
                    <?php if ($contrat): ?>
                        <p class="contract-name"><?= htmlspecialchars($contrat->getNom()) ?></p>
                        <div class="project-stats-small">
                            <span>Heures incluses: <strong><?= $contrat->getHeuresTotales() ?>h</strong></span>
                            <span>Consommées: <strong><?= $contrat->getHeuresConsommees() ?>h</strong></span>
                            <span>Restantes: <strong
                                    class="<?= $contrat->getHeuresRestantes() < 10 ? 'text-warning' : '' ?>"><?= $contrat->getHeuresRestantes() ?>h</strong></span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun contrat associé</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Collaborateurs assignés</h3>
                    <div class="collaborators-list">
                        <?php foreach ($collabs as $collab):
                            $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                            ?>
                            <div class="collaborator-item">
                                <div class="avatar"><?= $initials ?></div>
                                <div class="collab-info">
                                    <span
                                        class="collab-name"><?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?></span>
                                    <span
                                        class="collab-role"><?= htmlspecialchars($collab['role'] ?? 'Collaborateur') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($collabs)): ?>
                            <p class="text-muted">Aucun collaborateur assigné</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description du ticket (lecture seule) -->
            <div class="ticket-section">
                <h3>Description</h3>
                <div class="ticket-description-text">
                    <?= nl2br(htmlspecialchars($ticket->getDescription() ?? 'Aucune description disponible.')) ?>
                </div>
            </div>

            <!-- Historique du temps (lecture seule) -->
            <div class="ticket-section">
                <h3>Historique du temps passé</h3>
                <div class="time-entries-list">
                    <?php foreach ($tempsEntries as $entry):
                        $entryDate = new DateTime($entry['date_travail']);
                        $initials = strtoupper(mb_substr($entry['name'] ?? '?', 0, 1) . mb_substr($entry['surname'] ?? '?', 0, 1));
                        $collabName = htmlspecialchars(($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? ''));
                        ?>
                        <div class="time-entry">
                            <div class="entry-header">
                                <div class="entry-collaborator">
                                    <div class="avatar small"><?= $initials ?></div>
                                    <span><?= $collabName ?></span>
                                </div>
                                <div class="entry-date"><?= $entryDate->format('d F Y') ?></div>
                                <div class="entry-duration"><?= $entry['duree'] ?>h</div>
                            </div>
                            <?php if (!empty($entry['commentaire'])): ?>
                                <div class="entry-comment"><?= htmlspecialchars($entry['commentaire']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($tempsEntries)): ?>
                        <p class="text-muted">Aucune entrée de temps enregistrée.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commentaires (lecture seule) -->
            <div class="ticket-section">
                <h3>Commentaires</h3>
                <div class="comments-list">
                    <?php foreach ($commentaires as $com):
                        $comDate = new DateTime($com['date_creation']);
                        $comInitials = strtoupper(mb_substr($com['name'] ?? '?', 0, 1) . mb_substr($com['surname'] ?? '?', 0, 1));
                        ?>
                        <div class="comment">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <div class="avatar small"><?= $comInitials ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars(($com['name'] ?? '') . ' ' . ($com['surname'] ?? '')) ?></strong>
                                        <span class="comment-date"><?= $comDate->format('d M Y') ?> à
                                            <?= $comDate->format('H:i') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="comment-body"><?= nl2br(htmlspecialchars($com['contenu'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($commentaires)): ?>
                        <p class="text-muted">Aucun commentaire.</p>
                    <?php endif; ?>
                </div>

                <div class="client-comment-note">
                    <p><img src="/assets/logo.png" alt="info" class="inline-icon"> En tant que client, vous pouvez
                        suivre
                        l'avancement mais ne pouvez pas ajouter de commentaires directement. Pour toute question,
                        contactez
                        le support.</p>
                </div>
            </div>

            <!-- Section validation client -->
            <div class="ticket-section validation-section <?= $ticket->getType() === 'facturable' ? 'billable' : '' ?>">
                <h3><?= $ticket->getType() === 'facturable' ? 'Validation requise' : 'Validation client' ?></h3>
                <div class="validation-info">
                    <?php if ($ticket->getType() === 'inclus'): ?>
                        <p class="info-text">
                            Ce ticket est marqué comme <strong>inclus dans le contrat</strong>. Aucune validation de
                            facturation n'est requise.
                        </p>
                        <div class="validation-status-display">
                            <span class="status-label">Statut:</span>
                            <span class="validation-badge included">Inclus - Pas de validation nécessaire</span>
                        </div>
                    <?php else: ?>
                        <p class="info-text">
                            Ce ticket est marqué comme <strong>facturable</strong>. Il nécessite votre validation avant
                            facturation.
                        </p>
                        <div class="validation-details">
                            <div class="detail-row">
                                <span class="detail-label">Temps prévu:</span>
                                <span class="detail-value"><?= $ticket->getTempsEstime() ?>h</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Temps réel:</span>
                                <span
                                    class="detail-value <?= $depassement > 0 ? 'text-warning' : '' ?>"><?= $ticket->getTempsPasse() ?>h</span>
                            </div>
                            <?php if ($depassement > 0): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Dépassement:</span>
                                    <span class="detail-value text-warning">+<?= $depassement ?>h</span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Taux horaire:</span>
                                <span class="detail-value"><?= $tauxHoraire ?>€/h</span>
                            </div>
                            <div class="detail-row total">
                                <span class="detail-label">Coût total:</span>
                                <span
                                    class="detail-value text-warning"><?= number_format($coutTotal, 0, ',', ' ') ?>€</span>
                            </div>
                            <?php if ($depassement > 0): ?>
                                <div class="detail-row total">
                                    <span class="detail-label">Coût supplémentaire:</span>
                                    <span
                                        class="detail-value text-warning"><?= number_format($coutDepassement, 0, ',', ' ') ?>€</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($vs === 'en_attente'): ?>
                            <div class="validation-actions">
                                <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                                    <input type="hidden" name="action" value="client_validate" />
                                    <button type="submit" class="btn-validate"
                                        onclick="return confirm('Confirmer la validation et accepter la facturation ?')">Valider
                                        et accepter la facturation</button>
                                </form>
                                <button class="btn-reject-with-reason"
                                    onclick="document.querySelector('.rejection-form').style.display='block'">Refuser</button>
                            </div>

                            <div class="rejection-form" style="display: none;">
                                <form method="POST" action="/php/actions/update-ticket.php">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                                    <input type="hidden" name="action" value="client_reject" />
                                    <label>Motif du refus:</label>
                                    <textarea name="motif_refus" class="rejection-reason"
                                        placeholder="Veuillez indiquer la raison du refus..." rows="4" required></textarea>
                                    <div class="rejection-actions">
                                        <button type="submit" class="btn-submit-rejection">Envoyer le refus</button>
                                        <button type="button" class="btn-cancel-rejection"
                                            onclick="document.querySelector('.rejection-form').style.display='none'">Annuler</button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="validation-status-display">
                                <span class="status-label">Statut de validation:</span>
                                <span class="validation-badge <?= $vInfo['class'] ?>"><?= $vInfo['label'] ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique des actions -->
            <div class="ticket-section">
                <h3>Historique</h3>
                <div class="history-list">
                    <?php
                    $historyItems = [];
                    if ($ticket->getDateCreation()) {
                        $historyItems[] = [
                            'date' => $ticket->getDateCreation(),
                            'text' => 'Ticket créé',
                        ];
                    }
                    if ($ticket->getDateModification() && $ticket->getDateModification() !== $ticket->getDateCreation()) {
                        $historyItems[] = [
                            'date' => $ticket->getDateModification(),
                            'text' => 'Statut modifié en <strong>"' . $sInfo[1] . '"</strong>',
                        ];
                    }
                    foreach ($collabs as $collab) {
                        $historyItems[] = [
                            'date' => $ticket->getDateCreation(),
                            'text' => '<strong>' . htmlspecialchars($collab['name'] . ' ' . $collab['surname']) . '</strong> assigné au ticket',
                        ];
                    }
                    foreach ($tempsEntries as $entry) {
                        $who = trim(($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? ''));
                        $historyItems[] = [
                            'date' => $entry['date_creation'] ?? $entry['date_travail'],
                            'text' => 'Temps enregistré: <strong>' . $entry['duree'] . 'h</strong> par ' . htmlspecialchars($who),
                        ];
                    }
                    usort($historyItems, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                    $historyItems = array_slice($historyItems, 0, 10);
                    ?>
                    <?php foreach ($historyItems as $item):
                        $itemDate = new DateTime($item['date']);
                        ?>
                        <div class="history-item">
                            <div class="history-icon">•</div>
                            <div class="history-content">
                                <div class="history-text"><?= $item['text'] ?></div>
                                <div class="history-date"><?= $itemDate->format('d F Y') ?> à
                                    <?= $itemDate->format('H:i') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($historyItems)): ?>
                        <p class="text-muted">Aucun historique.</p>
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