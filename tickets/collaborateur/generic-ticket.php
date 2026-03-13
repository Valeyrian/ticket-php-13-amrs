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
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$ticketId) {
    header("Location: /tickets/collaborateur/ticket-manager.php");
    exit;
}

$ticket = Ticket::getById($pdo, $ticketId);
if (!$ticket) {
    header("Location: /tickets/collaborateur/ticket-manager.php?error=ticket_introuvable");
    exit;
}

// Vérifier que le collaborateur est assigné à ce ticket
$collabs = $ticket->getCollaborateurs($pdo);
$collabIds = array_map(fn($c) => $c['id'], $collabs);
if (!in_array($user->getId(), $collabIds)) {
    header("Location: /tickets/collaborateur/ticket-manager.php?error=acces_refuse");
    exit;
}

// Données liées
$projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
$client = $projet ? $projet->getClient($pdo) : null;
$tempsEntries = $ticket->getTemps($pdo);
$commentaires = $ticket->getCommentaires($pdo);
$contrats = $projet ? Contrat::getByProjet($pdo, $projet->getId()) : [];
$contrat = !empty($contrats) ? $contrats[0] : null;

// Tous les collaborateurs disponibles pour ajout
$allUsers = User::getAll($pdo);
$allCollaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');
$availableCollabs = array_filter($allCollaborateurs, fn($u) => !in_array($u->getId(), $collabIds));

// Nombre de tickets ouverts pour le badge
$mesTickets = Ticket::getByCollaborateur($pdo, $user->getId());
$nbTicketsOuverts = count(array_filter($mesTickets, fn($t) => $t->isOpen()));

// Stats contrat
$heuresIncluses = $contrat ? $contrat->getHeuresTotales() : 0;
$heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : 0;
$heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;

// Contact client
$contactPrincipal = $client ? $client->getContactPrincipal($pdo) : null;

// Validation status
$validationMap = [
    'en_attente' => ['label' => 'En attente de validation', 'class' => 'pending'],
    'valide' => ['label' => 'Validé', 'class' => 'validated'],
    'refuse' => ['label' => 'Refusé', 'class' => 'refused'],
];
$vs = $ticket->getValidationStatus();
$vInfo = $validationMap[$vs] ?? null;
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

<body class="oswald-font1 role-collaborateur">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site"></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site"></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Détail Ticket</h1>

            <nav class="header-nav">
                <a href="/dashboard/collaborateur/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/tickets/collaborateur/ticket-manager.php" class="nav-btn">
                    Tickets
                    <span class="badge"><?= $nbTicketsOuverts ?></span>
                </a>
                <a href="/projects/collaborateur/project-manager.php" class="nav-btn">Projets</a>
            </nav>

            <div class="profile-info">
                <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic">
                <div class="profile-text">
                    <span
                        class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
                    <span class="profile-role">Collaborateur</span>
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
                    <button class="action-btn-header btn-log-time" onclick="openTimeModal()">Enregistrer du
                        temps</button>
                    <button class="action-btn-header btn-edit-ticket" onclick="openEditModal()">✏️ Modifier</button>
                </div>
            </div>

            <!-- Métadonnées principales -->
            <form method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="update_metadata" />
                <div class="ticket-metadata">
                    <div class="metadata-card">
                        <label>Statut</label>
                        <?php
                        $statutMap = [
                            'nouveau' => ['nouveau', 'Nouveau'],
                            'en_cours' => ['progress', 'En cours'],
                            'en_attente_client' => ['pending', 'En attente client'],
                            'termine' => ['completed', 'Terminé'],
                            'a_valider' => ['validate', 'À valider'],
                            'valide' => ['validated', 'Validé'],
                            'refuse' => ['refused', 'Refusé'],
                        ];
                        $sInfo = $statutMap[$ticket->getStatut()] ?? ['pending', $ticket->getStatut()];
                        ?>
                        <select name="statut" class="status-select status-<?= $sInfo[0] ?>">
                            <?php foreach ($statutMap as $val => $info): ?>
                                <option value="<?= $val ?>" <?= $ticket->getStatut() === $val ? 'selected' : '' ?>>
                                    <?= $info[1] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="metadata-card">
                        <label>Priorité</label>
                        <?php
                        $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                        $pInfo = $priorityMap[$ticket->getPriorite()] ?? ['medium', $ticket->getPriorite()];
                        ?>
                        <select name="priorite" class="priority-select priority-<?= $pInfo[0] ?>">
                            <?php foreach ($priorityMap as $val => $info): ?>
                                <option value="<?= $val ?>" <?= $ticket->getPriorite() === $val ? 'selected' : '' ?>>
                                    <?= $info[1] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="metadata-card">
                        <label>Type</label>
                        <select name="type" class="type-select">
                            <option value="inclus" <?= $ticket->getType() === 'inclus' ? 'selected' : '' ?>>Inclus</option>
                            <option value="facturable" <?= $ticket->getType() === 'facturable' ? 'selected' : '' ?>>
                                Facturable</option>
                        </select>
                    </div>

                    <div class="metadata-card">
                        <label>Temps estimé</label>
                        <input type="number" name="temps_estime" class="time-input-meta"
                            value="<?= $ticket->getTempsEstime() ?>" step="0.5" min="0">
                        <span>heures</span>
                    </div>

                    <div class="metadata-card highlight">
                        <label>Temps passé total</label>
                        <div class="time-total"><?= $ticket->getTempsPasse() ?>h</div>
                    </div>

                    <div class="metadata-card">
                        <button type="submit" class="btn-primary">Enregistrer</button>
                    </div>
                </div>
            </form>

            <!-- Informations projet et client -->
            <div class="project-client-info">
                <div class="info-card">
                    <h3>Projet</h3>
                    <p class="project-name"><?= $projet ? htmlspecialchars($projet->getNom()) : 'Aucun projet' ?></p>
                    <?php if ($contrat): ?>
                        <div class="project-stats-small">
                            <span>Heures incluses: <strong><?= $heuresIncluses ?>h</strong></span>
                            <span>Consommées: <strong><?= $heuresConsommees ?>h</strong></span>
                            <span>Restantes: <strong
                                    class="<?= $heuresRestantes < 10 ? 'text-warning' : '' ?>"><?= $heuresRestantes ?>h</strong></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Client</h3>
                    <p class="client-name"><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></p>
                    <?php if ($contactPrincipal): ?>
                        <p class="client-contact"><?= htmlspecialchars($contactPrincipal['email'] ?? '') ?></p>
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
                                <span><?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn-add-collab" onclick="openCollabModal()">+ Ajouter un collaborateur</button>
                </div>
            </div>

            <!-- Description du ticket -->
            <form method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="update_description" />
                <div class="ticket-section">
                    <h3>Description</h3>
                    <textarea name="description" class="ticket-description"
                        rows="6"><?= htmlspecialchars($ticket->getDescription() ?? '') ?></textarea>
                    <button type="submit" class="btn-primary" style="margin-top:10px">Enregistrer la
                        description</button>
                </div>
            </form>

            <!-- Enregistrement du temps -->
            <form method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="add_temps" />
                <input type="hidden" name="collaborateur_id" value="<?= $user->getId() ?>" />
                <div class="ticket-section">
                    <h3>Enregistrer du temps</h3>
                    <div class="time-entry-form">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date_travail" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Durée (heures)</label>
                            <input type="number" name="duree" class="form-control" step="0.5" min="0" placeholder="2.5"
                                required>
                        </div>
                        <div class="form-group full-width">
                            <label>Commentaire (optionnel)</label>
                            <input type="text" name="commentaire" class="form-control"
                                placeholder="Description du travail effectué...">
                        </div>
                        <button type="submit" class="btn-primary">Ajouter l'entrée</button>
                    </div>
                </div>
            </form>

            <!-- Historique du temps -->
            <div class="ticket-section">
                <h3>Historique du temps passé</h3>
                <div class="time-entries-list">
                    <?php foreach ($tempsEntries as $entry):
                        $entryDate = new DateTime($entry['date_travail']);
                        $initials = strtoupper(mb_substr($entry['name'] ?? '?', 0, 1) . mb_substr($entry['surname'] ?? '?', 0, 1));
                        ?>
                        <div class="time-entry">
                            <div class="entry-header">
                                <div class="entry-collaborator">
                                    <div class="avatar small"><?= $initials ?></div>
                                    <span><?= htmlspecialchars(($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? '')) ?></span>
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

            <!-- Commentaires et historique -->
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

                <form method="POST" action="/php/actions/update-ticket.php">
                    <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                    <input type="hidden" name="action" value="add_commentaire" />
                    <div class="add-comment">
                        <textarea name="contenu" class="comment-input" placeholder="Ajouter un commentaire..." rows="3"
                            required minlength="3"></textarea>
                        <button type="submit" class="btn-primary">Publier</button>
                    </div>
                </form>
            </div>

            <!-- Validation client (si facturable) -->
            <?php if ($ticket->getType() === 'facturable'): ?>
                <div class="ticket-section validation-section">
                    <h3>Validation client</h3>
                    <div class="validation-info">
                        <p class="info-text">
                            Ce ticket est marqué comme <strong>facturable</strong>. Il nécessite une validation du client
                            avant facturation.
                        </p>
                        <?php if ($vInfo): ?>
                            <div class="validation-status">
                                <span class="status-label">Statut de validation:</span>
                                <span class="validation-badge <?= $vInfo['class'] ?>"><?= $vInfo['label'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vector - Tous droits réservés</p>
    </footer>

    <!-- Modal Enregistrer Temps -->
    <div class="modal-overlay" id="modal-time-overlay">
        <div class="modal" id="log-time-modal">
            <div class="modal-header">
                <h2>Enregistrer du temps - Ticket #<?= $ticket->getId() ?></h2>
                <button class="modal-close" id="close-time-modal">&times;</button>
            </div>
            <form id="log-time-form" class="modal-form" method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="add_temps" />
                <input type="hidden" name="collaborateur_id" value="<?= $user->getId() ?>" />
                <div class="form-section">
                    <h3>Détails du temps</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="time-date">Date *</label>
                            <input type="date" id="time-date" name="date_travail" required
                                value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="form-group">
                            <label for="time-duration">Durée (heures) *</label>
                            <input type="number" id="time-duration" name="duree" min="0" step="0.5" required
                                placeholder="2.5" />
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
                    <div class="form-error" id="time-form-error">Veuillez remplir tous les champs obligatoires.</div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modifier Ticket -->
    <div class="modal-overlay" id="modal-edit-overlay">
        <div class="modal" id="edit-ticket-modal">
            <div class="modal-header">
                <h2>Modifier le Ticket</h2>
                <button class="modal-close" id="close-edit-modal">&times;</button>
            </div>
            <form id="edit-ticket-form" class="modal-form" method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="update_metadata" />
                <div class="form-section">
                    <h3>Informations du Ticket</h3>
                    <div class="form-group">
                        <label for="edit-ticket-title">Titre *</label>
                        <input type="text" id="edit-ticket-title" name="titre" required
                            value="<?= htmlspecialchars($ticket->getTitre()) ?>" />
                    </div>
                    <div class="form-group">
                        <label for="edit-ticket-description">Description *</label>
                        <textarea id="edit-ticket-description" name="description" rows="4"
                            required><?= htmlspecialchars($ticket->getDescription() ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Planning</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-estimated-hours">Heures estimées</label>
                            <input type="number" id="edit-estimated-hours" name="temps_estime" min="0" step="0.5"
                                value="<?= $ticket->getTempsEstime() ?>" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancel-edit-modal">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                    <div class="form-error" id="edit-form-error">Veuillez remplir tous les champs obligatoires.</div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ajouter Collaborateur -->
    <div class="modal-overlay" id="modal-collab-overlay">
        <div class="modal" id="add-collab-modal">
            <div class="modal-header">
                <h2>Ajouter un collaborateur</h2>
                <button class="modal-close" id="close-collab-modal">&times;</button>
            </div>
            <form id="add-collab-form" class="modal-form" method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="add_collaborateur" />
                <div class="form-section">
                    <h3>Sélectionner un collaborateur</h3>
                    <div class="collab-list" id="collab-list">
                        <?php foreach ($availableCollabs as $ac):
                            $acInitials = strtoupper(mb_substr($ac->getName(), 0, 1) . mb_substr($ac->getSurname(), 0, 1));
                            ?>
                            <label class="collab-option">
                                <input type="radio" name="collaborateur_id" value="<?= $ac->getId() ?>" />
                                <div class="avatar small"><?= $acInitials ?></div>
                                <div class="collab-info">
                                    <span
                                        class="collab-name"><?= htmlspecialchars($ac->getName() . ' ' . $ac->getSurname()) ?></span>
                                    <span class="collab-role"><?= htmlspecialchars($ac->getRole()) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($availableCollabs)): ?>
                            <p class="text-muted">Tous les collaborateurs sont déjà assignés.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancel-collab-modal">Annuler</button>
                    <button type="submit" class="btn-submit">Ajouter</button>
                    <div class="form-error" id="collab-form-error">Veuillez sélectionner un collaborateur.</div>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/form-validation.js"></script>
    <script src="./generic-ticket.js"></script>

</body>

</html>