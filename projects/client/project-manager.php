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

// Pré-calculer les données par projet
$projetData = [];
$totalHeuresConsommees = 0;
$totalHeuresDisponibles = 0;
$nbProjetsActifs = 0;
$nbTicketsOuverts = 0;

foreach ($allProjets as $projet) {
    $client = $projet->getClient($pdo);
    $contrats = Contrat::getByProjet($pdo, $projet->getId());
    $contrat = !empty($contrats) ? $contrats[0] : null;
    $collabs = $projet->getCollaborateurs($pdo);
    $tickets = Ticket::getByProjetId($pdo, $projet->getId());

    $nbTickets = count($tickets);
    $nbOuverts = count(array_filter($tickets, fn($t) => $t->isOpen()));
    $nbEnCours = count(array_filter($tickets, fn($t) => $t->getStatut() === 'en_cours'));
    $nbTermines = count(array_filter($tickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide', 'ferme'])));

    $tempsPasse = $projet->getTotalTempsPasse($pdo);
    $heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
    $heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : $tempsPasse;
    $heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;

    $pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

    // Classes CSS pour la row
    $rowClass = '';
    if ($contrat && $heuresConsommees > $heuresContrat) {
        $rowClass = 'hours-exceeded';
    } elseif ($contrat && $heuresContrat > 0 && ($heuresRestantes / $heuresContrat) < 0.15) {
        $rowClass = 'hours-warning';
    }

    // Statut select class
    $statusClassMap = [
        'actif' => 'status-active',
        'archive' => 'status-completed',
    ];
    $statusClass = $statusClassMap[$projet->getStatut()] ?? '';

    // Stats globales
    $totalHeuresConsommees += $heuresConsommees;
    if ($contrat)
        $totalHeuresDisponibles += $heuresContrat;
    if ($projet->isActif())
        $nbProjetsActifs++;
    $nbTicketsOuverts += $nbOuverts;

    $projetData[] = [
        'projet' => $projet,
        'client' => $client,
        'contrat' => $contrat,
        'collabs' => $collabs,
        'nbTickets' => $nbTickets,
        'nbOuverts' => $nbOuverts,
        'nbEnCours' => $nbEnCours,
        'nbTermines' => $nbTermines,
        'tempsPasse' => $tempsPasse,
        'heuresContrat' => $heuresContrat,
        'heuresConsommees' => $heuresConsommees,
        'heuresRestantes' => $heuresRestantes,
        'pourcentage' => $pourcentage,
        'rowClass' => $rowClass,
        'statusClass' => $statusClass,
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

<body class="oswald-font1 role-client">
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
                <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
            </div>

            <div class="header-separator"></div>

            <h1 class="dashboard-title">Mes Projets</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/tickets/client/ticket-manager.php" class="nav-btn">Mes Tickets</a>
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
        <div class="projects-manager">
            <div class="projects-header">
                <h2>Mes Projets</h2>
                <span class="info-text">Suivez l'avancement de vos projets en cours</span>
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
                        <option value="date">Date création</option>
                        <option value="heures">Heures restantes</option>
                        <option value="tickets">Nombre de tickets</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="projects-management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du Projet</th>
                            <th>Description</th>
                            <th>Collaborateurs</th>
                            <th>Contrat</th>
                            <th>Heures Consommées</th>
                            <th>Mes Tickets</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projetData as $pd):
                            $p = $pd['projet'];
                            $client = $pd['client'];
                            $contrat = $pd['contrat'];
                            $collabs = $pd['collabs'];

                            // Validité contrat
                            $contratValidite = '';
                            if ($contrat && $contrat->getDateFin()) {
                                $dateFin = new DateTime($contrat->getDateFin());
                                $now = new DateTime();
                                $contratValidite = $dateFin > $now
                                    ? 'Jusqu\'au ' . $dateFin->format('d/m/Y')
                                    : 'Expiré le ' . $dateFin->format('d/m/Y');
                            }

                            // Hours bar classes
                            $fillClass = '';
                            if ($pd['pourcentage'] >= 100) {
                                $fillClass = 'exceeded';
                            } elseif ($pd['pourcentage'] >= 85) {
                                $fillClass = 'warning';
                            } elseif ($p->getStatut() === 'archive') {
                                $fillClass = 'completed';
                            }
                            ?>
                            <tr class="<?= $pd['rowClass'] ?>" data-statut="<?= htmlspecialchars($p->getStatut()) ?>"
                                data-heures-restantes="<?= $pd['heuresRestantes'] ?>" data-tickets="<?= $pd['nbTickets'] ?>"
                                data-date="<?= htmlspecialchars($p->getDateCreation() ?? '') ?>">
                                <td>
                                    <a href="/projects/client/generic-project.php?id=<?= $p->getId() ?>"
                                        class="project-link">#P<?= str_pad($p->getId(), 3, '0', STR_PAD_LEFT) ?></a>
                                </td>
                                <td>
                                    <a href="/projects/client/generic-project.php?id=<?= $p->getId() ?>"
                                        class="project-link project-title"><?= htmlspecialchars($p->getNom()) ?></a>
                                </td>
                                <td class="description-cell">
                                    <?= htmlspecialchars($p->getDescription() ?? '') ?>
                                </td>
                                <td>
                                    <div class="collaborators-cell">
                                        <?php foreach ($collabs as $collab):
                                            $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                                            $fullName = htmlspecialchars($collab['name'] . ' ' . $collab['surname']);
                                            ?>
                                            <div class="avatar small" title="<?= $fullName ?>"><?= $initials ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($contrat): ?>
                                        <div class="contract-info">
                                            <span class="contract-hours"><?= $pd['heuresContrat'] ?>h incluses</span>
                                            <?php if ($contratValidite): ?>
                                                <span class="contract-validity"><?= $contratValidite ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted)">Aucun contrat</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="hours-progress">
                                        <div class="hours-bar">
                                            <div class="hours-fill <?= $fillClass ?>"
                                                style="width: <?= min($pd['pourcentage'], 100) ?>%"></div>
                                        </div>
                                        <span class="hours-text <?= $fillClass ?>"><?= $pd['heuresConsommees'] ?>h /
                                            <?= $pd['heuresContrat'] ?>h</span>
                                        <?php if ($pd['heuresRestantes'] > 0 && $pd['pourcentage'] < 85): ?>
                                            <span class="hours-remaining"><?= $pd['heuresRestantes'] ?>h restantes</span>
                                        <?php elseif ($pd['rowClass'] === 'hours-warning'): ?>
                                            <span class="hours-alert"><img src="/assets/avertissement.png" alt="avertissement"
                                                    class="inline-icon" />
                                                Heures bientôt épuisées</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="tickets-info">
                                        <span class="ticket-count"><?= $pd['nbTickets'] ?> tickets</span>
                                        <div class="ticket-status-mini">
                                            <span class="mini-badge open"><?= $pd['nbOuverts'] ?></span>
                                            <span class="mini-badge progress"><?= $pd['nbEnCours'] ?></span>
                                            <span class="mini-badge done"><?= $pd['nbTermines'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $pd['statusClass'] ?>">
                                        <?= $p->getStatut() === 'actif' ? 'Actif' : 'Archivé' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/projects/client/generic-project.php?id=<?= $p->getId() ?>"
                                            class="btn-view" title="Voir détails"><img src="/assets/oeil.png" alt="voir"
                                                class="inline-icon" /></a>
                                        <?php if ($contrat): ?>
                                            <a href="/contrats/client/generic-contrat.php?id=<?= $contrat->getId() ?>"
                                                class="btn-contract" title="Voir contrat"><img src="/assets/contrat.png"
                                                    alt="contrat" class="inline-icon" /></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projetData)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;color:var(--text-muted);padding:20px">Aucun projet
                                    trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stats rapides -->
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $nbProjetsActifs ?></span>
                    <span class="stat-label">Projets actifs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $nbTicketsOuverts ?></span>
                    <span class="stat-label">Tickets ouverts</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= round($totalHeuresDisponibles) ?>h</span>
                    <span class="stat-label">Heures totales</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= round($totalHeuresConsommees) ?>h</span>
                    <span class="stat-label">Heures consommées</span>
                </div>
            </div>

            <!-- Section Alertes -->
            <?php
            $alertes = [];
            foreach ($projetData as $pd) {
                if ($pd['rowClass'] === 'hours-warning') {
                    $alertes[] = [
                        'type' => 'warning',
                        'titre' => 'Heures bientôt épuisées',
                        'message' => 'Le projet "' . htmlspecialchars($pd['projet']->getNom()) . '" a consommé ' . $pd['pourcentage'] . '% de ses heures (' . $pd['heuresConsommees'] . 'h/' . $pd['heuresContrat'] . 'h). Pensez à renouveler votre contrat ou des frais supplémentaires s\'appliqueront.'
                    ];
                }
            }

            // Compter les tickets en attente de validation
            $ticketsAValider = 0;
            foreach ($allProjets as $projet) {
                $tickets = Ticket::getByProjetId($pdo, $projet->getId());
                $ticketsAValider += count(array_filter($tickets, fn($t) => $t->getValidationStatus() === 'en_attente'));
            }

            if ($ticketsAValider > 0) {
                $alertes[] = [
                    'type' => 'info',
                    'titre' => 'Tickets en attente de validation',
                    'message' => $ticketsAValider . ' ticket' . ($ticketsAValider > 1 ? 's facturables nécessitent' : ' facturable nécessite') . ' votre validation. Consultez-les dans la section <a href="/tickets/client/ticket-manager.php">Mes Tickets</a>.'
                ];
            }
            ?>

            <?php if (!empty($alertes)): ?>
                <div class="alerts-section">
                    <h3>Informations importantes</h3>
                    <?php foreach ($alertes as $alerte): ?>
                        <div class="alert-card <?= $alerte['type'] ?>">
                            <div class="alert-icon">
                                <?php if ($alerte['type'] === 'warning'): ?>
                                    <img src="/assets/avertissement.png" alt="avertissement" class="inline-icon" />
                                <?php else: ?>
                                    <img src="/assets/chiffre-daffaires.png" alt="info" class="inline-icon" />
                                <?php endif; ?>
                            </div>
                            <div class="alert-content">
                                <h4><?= $alerte['titre'] ?></h4>
                                <p><?= $alerte['message'] ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Vector - Tous droits réservés</p>
    </footer>
</body>

</html>