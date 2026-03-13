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

// Récupérer tous les tickets assignés à ce collaborateur
$mesTickets = Ticket::getByCollaborateur($pdo, $user->getId());

// Récupérer tous les projets assignés pour le select
$mesProjets = Projet::getByCollaborateur($pdo, $user->getId());

// Stats
$nbTotal = count($mesTickets);
$nbEnCours = count(array_filter($mesTickets, fn($t) => in_array($t->getStatut(), ['en_cours', 'nouveau'])));
$nbTermines = count(array_filter($mesTickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide'])));
$tempsTotalPasse = array_sum(array_map(fn($t) => $t->getTempsPasse(), $mesTickets));
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./ticket-manager.css" />
    <title>Mes Tickets - Vector</title>
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

            <h1 class="dashboard-title">Mes Tickets</h1>

            <nav class="header-nav">
                <a href="/dashboard/collaborateur/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/projects/collaborateur/project-manager.php" class="nav-btn">Projets</a>
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
        <div class="tickets-manager">
            <div class="tickets-header">
                <h2>Gestion des Tickets</h2>
                <button class="cta-button" onclick="openTicketModal()">+ Nouveau Ticket</button>
            </div>

            <div class="filters-section">
                <div class="filter-group">
                    <label for="sort-select">Trier par:</label>
                    <select id="sort-select" class="filter-select">
                        <option value="date">Date</option>
                        <option value="priority">Priorité</option>
                        <option value="status">Statut</option>
                        <option value="time">Temps passé</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="show-completed" class="filter-checkbox" />
                        <span>Afficher les tickets terminés</span>
                    </label>
                </div>
            </div>

            <div class="table-container">
                <table class="tickets-management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Projet</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Temps Estimé</th>
                            <th>Temps Passé</th>
                            <th>Collaborateurs</th>
                            <th>Facturation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mesTickets as $ticket):
                            $projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
                            $projetNom = $projet ? $projet->getNom() : '-';
                            $collabs = $ticket->getCollaborateurs($pdo);

                            // Priority
                            $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                            $pInfo = $priorityMap[$ticket->getPriorite()] ?? ['medium', $ticket->getPriorite()];

                            // Status
                            $statutMap = [
                                'nouveau' => ['nouveau', 'Nouveau'],
                                'en_cours' => ['progress', 'En cours'],
                                'en_attente_client' => ['pending', 'En attente'],
                                'termine' => ['completed', 'Terminé'],
                                'a_valider' => ['review', 'En révision'],
                                'valide' => ['completed', 'Validé'],
                                'refuse' => ['refused', 'Refusé'],
                            ];
                            $sInfo = $statutMap[$ticket->getStatut()] ?? ['pending', $ticket->getStatut()];

                            // Type
                            $typeClass = $ticket->getType() === 'facturable' ? 'billing-facturable' : 'billing-inclus';
                            $typeLabel = $ticket->getType() === 'facturable' ? 'Facturable' : 'Inclus';

                            // Row class pour masquer les terminés
                            $rowClass = in_array($ticket->getStatut(), ['termine', 'valide']) ? 'completed-ticket' : '';
                            ?>
                            <tr class="<?= $rowClass ?>" data-statut="<?= htmlspecialchars($ticket->getStatut()) ?>"
                                data-priorite="<?= htmlspecialchars($ticket->getPriorite()) ?>"
                                data-temps="<?= $ticket->getTempsPasse() ?>">
                                <td>
                                    <a href="/tickets/collaborateur/generic-ticket.php?id=<?= $ticket->getId() ?>"
                                        class="ticket-link">#<?= $ticket->getId() ?></a>
                                </td>
                                <td>
                                    <a href="/tickets/collaborateur/generic-ticket.php?id=<?= $ticket->getId() ?>"
                                        class="ticket-link ticket-title"><?= htmlspecialchars($ticket->getTitre()) ?></a>
                                </td>
                                <td>
                                    <span class="project-badge"><?= htmlspecialchars($projetNom) ?></span>
                                </td>
                                <td class="description-cell">
                                    <?= htmlspecialchars($ticket->getDescription() ?? '') ?>
                                </td>
                                <td>
                                    <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                                        <input type="hidden" name="action" value="update_statut" />
                                        <select name="statut" class="status-select status-<?= $sInfo[0] ?>"
                                            onchange="this.form.submit()">
                                            <option value="nouveau" <?= $ticket->getStatut() === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                            <option value="en_cours" <?= $ticket->getStatut() === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                            <option value="en_attente_client" <?= $ticket->getStatut() === 'en_attente_client' ? 'selected' : '' ?>>En attente</option>
                                            <option value="a_valider" <?= $ticket->getStatut() === 'a_valider' ? 'selected' : '' ?>>En révision</option>
                                            <option value="termine" <?= $ticket->getStatut() === 'termine' ? 'selected' : '' ?>>Terminé</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                                        <input type="hidden" name="action" value="update_priorite" />
                                        <select name="priorite" class="priority-select priority-<?= $pInfo[0] ?>"
                                            onchange="this.form.submit()">
                                            <option value="haute" <?= $ticket->getPriorite() === 'haute' ? 'selected' : '' ?>>
                                                Haute</option>
                                            <option value="moyenne" <?= $ticket->getPriorite() === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                                            <option value="basse" <?= $ticket->getPriorite() === 'basse' ? 'selected' : '' ?>>
                                                Basse</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="time-cell"><?= $ticket->getTempsEstime() ?>h</td>
                                <td class="time-cell">
                                    <div class="time-input-group">
                                        <span><?= $ticket->getTempsPasse() ?>h</span>
                                        <button class="add-time-btn" title="Ajouter du temps"
                                            onclick="openTimeModal(<?= $ticket->getId() ?>)">+</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="collaborators-cell">
                                        <?php foreach ($collabs as $idx => $collab):
                                            if ($idx >= 3)
                                                break;
                                            $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                                            ?>
                                            <div class="avatar small"
                                                title="<?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?>">
                                                <?= $initials ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mesTickets)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center;color:var(--text-muted);padding:20px">Aucun ticket
                                    assigné.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                            <label for="ticket-project">Projet *</label>
                            <select id="ticket-project" name="projet_id" required>
                                <option value="">Sélectionner un projet</option>
                                <?php foreach ($mesProjets as $p): ?>
                                    <option value="<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Planning</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ticket-estimated-hours">Heures estimées</label>
                            <input type="number" id="ticket-estimated-hours" name="temps_estime" min="0" step="0.5"
                                placeholder="Ex: 4" value="0" />
                            <small class="form-help-text">Estimation du temps nécessaire</small>
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

    <!-- Modal Ajouter Temps -->
    <div class="modal-overlay" id="modal-time-overlay">
        <div class="modal" id="add-time-modal">
            <div class="modal-header">
                <h2>Enregistrer du temps</h2>
                <button class="modal-close" id="close-time-modal">&times;</button>
            </div>
            <form id="add-time-form" class="modal-form" method="POST" action="/php/actions/update-ticket.php">
                <input type="hidden" name="action" value="add_temps" />
                <input type="hidden" name="ticket_id" id="time-ticket-id" value="" />
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
                                placeholder="Ex: 2.5" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="time-comment">Commentaire</label>
                        <textarea id="time-comment" name="commentaire" rows="3"
                            placeholder="Décrivez ce que vous avez fait..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancel-time-modal">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTimeModal(ticketId) {
            document.getElementById('time-ticket-id').value = ticketId;
            document.getElementById('modal-time-overlay').style.display = 'flex';
        }
        document.getElementById('close-time-modal')?.addEventListener('click', () => {
            document.getElementById('modal-time-overlay').style.display = 'none';
        });
        document.getElementById('cancel-time-modal')?.addEventListener('click', () => {
            document.getElementById('modal-time-overlay').style.display = 'none';
        });
    </script>
    <script src="../../js/form-validation.js"></script>
    <script src="./ticket-manager.js"></script>
</body>

</html>