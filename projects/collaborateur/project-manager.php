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

// Récupérer tous les projets assignés à ce collaborateur
$mesProjets = Projet::getByCollaborateur($pdo, $user->getId());

// Stats globales
$totalProjets = count($mesProjets);
$totalTickets = 0;
$totalHeuresDisponibles = 0;
$totalTempsEnregistre = 0;

$projetsData = [];
foreach ($mesProjets as $projet) {
    $client = $projet->getClient($pdo);
    $collabs = $projet->getCollaborateurs($pdo);
    $tickets = Ticket::getByProjetId($pdo, $projet->getId());
    $contrats = Contrat::getByProjet($pdo, $projet->getId());
    $contrat = !empty($contrats) ? $contrats[0] : null;

    // Mes tickets sur ce projet
    $mesTickets = array_filter($tickets, function ($t) use ($pdo, $user) {
        $tCollabs = $t->getCollaborateurs($pdo);
        return in_array($user->getId(), array_map(fn($c) => $c['id'], $tCollabs));
    });

    $nbMesTickets = count($mesTickets);
    $nbMesTicketsOuverts = count(array_filter($mesTickets, fn($t) => $t->isOpen()));
    $nbMesTicketsEnCours = count(array_filter($mesTickets, fn($t) => $t->getStatut() === 'en_cours'));
    $nbMesTicketsTermines = count(array_filter($mesTickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide'])));

    // Heures
    $heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
    $heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : 0;
    $heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;
    $pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

    // Mon temps enregistré sur ce projet
    $monTemps = 0;
    foreach ($mesTickets as $mt) {
        $tempsEntries = $mt->getTempsByCollaborateur($pdo, $user->getId());
        $monTemps += array_sum(array_map(fn($e) => $e['duree'], $tempsEntries));
    }

    $totalTickets += $nbMesTickets;
    $totalHeuresDisponibles += $heuresRestantes;
    $totalTempsEnregistre += $monTemps;

    $projetsData[] = [
        'projet' => $projet,
        'client' => $client,
        'collabs' => $collabs,
        'contrat' => $contrat,
        'heuresRestantes' => $heuresRestantes,
        'pourcentage' => $pourcentage,
        'nbMesTickets' => $nbMesTickets,
        'nbMesTicketsOuverts' => $nbMesTicketsOuverts,
        'nbMesTicketsEnCours' => $nbMesTicketsEnCours,
        'nbMesTicketsTermines' => $nbMesTicketsTermines,
        'monTemps' => $monTemps,
    ];
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./project-manager.css" />
    <title>Mes Projets - Vector</title>
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

            <h1 class="dashboard-title">Mes Projets Assignés</h1>

            <nav class="header-nav">
                <a href="/dashboard/collaborateur/dashboard.php" class="nav-btn">Dashboard</a>
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
        <div class="projects-manager">
            <div class="projects-header">
                <h2>Projets assignés</h2>
                <span class="info-text">Projets sur lesquels vous travaillez</span>
            </div>

            <div class="filters-section">
                <div class="filter-group">
                    <label for="status-filter">Statut:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all">Tous</option>
                        <option value="actif">Actif</option>
                        <option value="archive">Archivé</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort-select">Trier par:</label>
                    <select id="sort-select" class="filter-select">
                        <option value="date">Date</option>
                        <option value="heures">Heures restantes</option>
                        <option value="tickets">Mes tickets</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="projects-management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du Projet</th>
                            <th>Client</th>
                            <th>Description</th>
                            <th>Équipe</th>
                            <th>Heures Disponibles</th>
                            <th>Mes Tickets</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projetsData as $pd):
                            $projet = $pd['projet'];
                            $client = $pd['client'];
                            $collabs = $pd['collabs'];
                            $heuresRestantes = $pd['heuresRestantes'];
                            $pourcentage = $pd['pourcentage'];
                            $nbMesTickets = $pd['nbMesTickets'];
                            $nbMesTicketsOuverts = $pd['nbMesTicketsOuverts'];
                            $nbMesTicketsEnCours = $pd['nbMesTicketsEnCours'];
                            $nbMesTicketsTermines = $pd['nbMesTicketsTermines'];

                            $projetIdFormatted = '#P' . str_pad($projet->getId(), 3, '0', STR_PAD_LEFT);
                            $statusClass = $projet->isActif() ? 'status-active' : 'status-archived';
                            $statusLabel = $projet->isActif() ? 'Actif' : 'Archivé';

                            $rowClass = '';
                            if ($pourcentage >= 85) {
                                $rowClass = 'hours-warning';
                            }

                            $fillClass = $pourcentage >= 85 ? 'warning' : '';
                            ?>
                            <tr class="<?= $rowClass ?>" data-statut="<?= $projet->getStatut() ?>"
                                data-heures="<?= $heuresRestantes ?>" data-tickets="<?= $nbMesTickets ?>">
                                <td>
                                    <a href="/projects/collaborateur/generic-project.php?id=<?= $projet->getId() ?>"
                                        class="project-link"><?= $projetIdFormatted ?></a>
                                </td>
                                <td>
                                    <a href="/projects/collaborateur/generic-project.php?id=<?= $projet->getId() ?>"
                                        class="project-link project-title"><?= htmlspecialchars($projet->getNom()) ?></a>
                                </td>
                                <td><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></td>
                                <td class="description-cell">
                                    <?= htmlspecialchars($projet->getDescription() ?? '') ?>
                                </td>
                                <td>
                                    <div class="collaborators-cell">
                                        <?php foreach ($collabs as $idx => $collab):
                                            if ($idx >= 3)
                                                break;
                                            $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                                            $meClass = $collab['id'] === $user->getId() ? 'me' : '';
                                            ?>
                                            <div class="avatar small <?= $meClass ?>"><?= $initials ?></div>
                                        <?php endforeach; ?>
                                        <?php if (count($collabs) > 3): ?>
                                            <span class="more-collabs">+<?= count($collabs) - 3 ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hours-progress">
                                        <div class="hours-bar">
                                            <div class="hours-fill <?= $fillClass ?>"
                                                style="width: <?= min($pourcentage, 100) ?>%"></div>
                                        </div>
                                        <span class="hours-text <?= $fillClass ?>"><?= $heuresRestantes ?>h restantes</span>
                                        <?php if ($pourcentage >= 85): ?>
                                            <span class="hours-alert"><img src="/assets/avertissement.png" alt="avertissement"
                                                    class="inline-icon" /> Bientôt épuisées</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="tickets-info">
                                        <span class="ticket-count"><?= $nbMesTickets ?>
                                            ticket<?= $nbMesTickets > 1 ? 's' : '' ?></span>
                                        <div class="ticket-status-mini">
                                            <span class="mini-badge open"><?= $nbMesTicketsOuverts ?></span>
                                            <span class="mini-badge progress"><?= $nbMesTicketsEnCours ?></span>
                                            <span class="mini-badge done"><?= $nbMesTicketsTermines ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-ticket" title="Créer un ticket"
                                            onclick="openTicketModal(<?= $projet->getId() ?>)">🎫</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projetsData)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;color:var(--text-muted);padding:20px">Aucun projet
                                    assigné.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stats rapides -->
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $totalProjets ?></span>
                    <span class="stat-label">Projets assignés</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $totalTickets ?></span>
                    <span class="stat-label">Mes tickets</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= round($totalHeuresDisponibles) ?>h</span>
                    <span class="stat-label">Heures disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= round($totalTempsEnregistre) ?>h</span>
                    <span class="stat-label">Temps enregistré</span>
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
                <input type="hidden" name="projet_id" id="modal-projet-id" value="" />
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
                            <select id="ticket-project" name="projet_id_select" required>
                                <option value="">Sélectionner un projet</option>
                                <?php foreach ($mesProjets as $p): ?>
                                    <option value="<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ticket-type">Type *</label>
                            <select id="ticket-type" name="type" required>
                                <option value="inclus" selected>Inclus</option>
                                <option value="facturable">Facturable</option>
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

    <script src="../../js/form-validation.js"></script>
    <script src="./project-manager.js"></script>
</body>

</html>