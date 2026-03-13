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

// Récupérer les clients associés à cet utilisateur
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());

// Récupérer tous les projets de ces clients
$allProjets = [];
foreach ($mesClients as $client) {
    $projetsClient = Projet::getByClient($pdo, $client->getId());
    $allProjets = array_merge($allProjets, $projetsClient);
}

// Récupérer tous les tickets de ces projets
$allTickets = [];
foreach ($allProjets as $projet) {
    $ticketsProjet = Ticket::getByProjetId($pdo, $projet->getId());
    $allTickets = array_merge($allTickets, $ticketsProjet);
}

// Stats
$nbTotal = count($allTickets);
$nbEnCours = count(array_filter($allTickets, fn($t) => in_array($t->getStatut(), ['en_cours', 'nouveau'])));
$nbAValider = count(array_filter($allTickets, fn($t) => $t->getValidationStatus() === 'en_attente'));
$tempsTotalPasse = array_sum(array_map(fn($t) => $t->getTempsPasse(), $allTickets));

// Calculer les heures totales du contrat (pour affichage)
$heuresTotalesContrat = 0;
foreach ($allProjets as $projet) {
    $contrats = Contrat::getByProjet($pdo, $projet->getId());
    if (!empty($contrats)) {
        $heuresTotalesContrat += $contrats[0]->getHeuresTotales();
    }
}
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

<body class="oswald-font1 role-client">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Mes Tickets</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
                <a href="/contrats/client/contrat-manager.php" class="nav-btn">Contrats</a>
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
        <div class="tickets-manager">
            <div class="tickets-header">
                <h2>Mes Tickets</h2>
                <div class="header-info">
                    <span class="tickets-count"><?= $nbEnCours ?> tickets ouverts</span>
                </div>
            </div>

            <div class="filters-section">
                <div class="filter-group">
                    <label for="project-filter">Projet:</label>
                    <select id="project-filter" class="filter-select">
                        <option value="all">Tous les projets</option>
                        <?php foreach ($allProjets as $projet): ?>
                            <option value="<?= $projet->getId() ?>"><?= htmlspecialchars($projet->getNom()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status-filter">Statut:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all">Tous</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="en_attente_client">En attente</option>
                        <option value="termine">Terminé</option>
                        <option value="a_valider">À valider</option>
                        <option value="valide">Validé</option>
                        <option value="refuse">Refusé</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="priority-filter">Priorité:</label>
                    <select id="priority-filter" class="filter-select">
                        <option value="all">Toutes</option>
                        <option value="haute">Haute</option>
                        <option value="moyenne">Moyenne</option>
                        <option value="basse">Basse</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort-select">Trier par:</label>
                    <select id="sort-select" class="filter-select">
                        <option value="date">Date</option>
                        <option value="priority">Priorité</option>
                        <option value="status">Statut</option>
                    </select>
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
                            <th>Type</th>
                            <th>Temps passé</th>
                            <th>Validation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTickets as $ticket):
                            $projet = Projet::getById($pdo, $ticket->getProjetId());
                            $projetNom = $projet ? $projet->getNom() : '-';

                            // Priority
                            $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                            $pInfo = $priorityMap[$ticket->getPriorite()] ?? ['medium', $ticket->getPriorite()];

                            // Status
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

                            // Type
                            $typeClass = $ticket->getType() === 'facturable' ? 'billing-facturable' : 'billing-inclus';
                            $typeLabel = $ticket->getType() === 'facturable' ? 'Facturable' : 'Inclus';

                            // Validation status
                            $validationClass = 'na';
                            $validationLabel = 'N/A';
                            if ($ticket->getValidationStatus() === 'en_attente') {
                                $validationClass = 'pending';
                                $validationLabel = '⏳ En attente';
                            } elseif ($ticket->getValidationStatus() === 'valide') {
                                $validationClass = 'validated';
                                $validationLabel = 'Validé';
                            } elseif ($ticket->getValidationStatus() === 'refuse') {
                                $validationClass = 'rejected';
                                $validationLabel = 'Refusé';
                            }

                            // Row class
                            $rowClass = '';
                            if ($ticket->getValidationStatus() === 'en_attente') {
                                $rowClass = 'validation-required';
                            } elseif ($ticket->getValidationStatus() === 'refuse') {
                                $rowClass = 'validation-rejected';
                            }

                            // Overtime
                            $overtime = $ticket->getTempsPasse() - $ticket->getTempsEstime();
                            ?>
                            <tr class="<?= $rowClass ?>" data-projet-id="<?= $ticket->getProjetId() ?>"
                                data-statut="<?= htmlspecialchars($ticket->getStatut()) ?>"
                                data-priorite="<?= htmlspecialchars($ticket->getPriorite()) ?>"
                                data-date="<?= htmlspecialchars($ticket->getDateCreation() ?? '') ?>">
                                <td>
                                    <a href="/tickets/client/generic-ticket.php?id=<?= $ticket->getId() ?>"
                                        class="ticket-link">#<?= $ticket->getId() ?></a>
                                </td>
                                <td>
                                    <a href="/tickets/client/generic-ticket.php?id=<?= $ticket->getId() ?>"
                                        class="ticket-link ticket-title"><?= htmlspecialchars($ticket->getTitre()) ?></a>
                                </td>
                                <td><span class="project-badge"><?= htmlspecialchars($projetNom) ?></span></td>
                                <td class="description-cell">
                                    <?= htmlspecialchars($ticket->getDescription() ?? '') ?>
                                </td>
                                <td><span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span></td>
                                <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                                <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                                <td class="time-cell">
                                    <span><?= $ticket->getTempsPasse() ?>h</span>
                                    <?php if ($overtime > 0 && $ticket->getType() === 'facturable'): ?>
                                        <span class="overtime-info">+<?= $overtime ?>h (<?= round($overtime * 85) ?>€)</span>
                                    <?php elseif ($ticket->getType() === 'facturable' && $ticket->getTempsPasse() > 0): ?>
                                        <span class="overtime-info">Hors contrat (<?= round($ticket->getTempsPasse() * 85) ?>€)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="validation-status <?= $validationClass ?>"><?= $validationLabel ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/tickets/client/generic-ticket.php?id=<?= $ticket->getId() ?>"
                                            class="btn-view">Voir</a>
                                        <?php if ($ticket->getValidationStatus() === 'en_attente'): ?>
                                            <button class="btn-validate-mini" title="Valider">V</button>
                                            <button class="btn-reject-mini" title="Refuser">X</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allTickets)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center;color:var(--text-muted);padding:20px">Aucun ticket
                                    trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stats rapides -->
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $nbTotal ?></span>
                    <span class="stat-label">Total tickets</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $nbEnCours ?></span>
                    <span class="stat-label">En cours</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $nbAValider ?></span>
                    <span class="stat-label">À valider</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= round($tempsTotalPasse) ?>h</span>
                    <span class="stat-label">Consommées / <?= round($heuresTotalesContrat) ?>h</span>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vector - Tous droits réservés</p>
    </footer>
</body>

</html>