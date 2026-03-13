<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/contrat.php';
require_once __DIR__ . '/../../php/classes/client.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isClient()) {
    header("Location: /login_or_registration/login.php");
    exit;
}

$user = $_SESSION['user'];

// Récupérer les clients associés à cet utilisateur
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());

// Si l'utilisateur n'a aucun client associé, afficher un message
if (empty($mesClients)) {
    $allContrats = [];
    $totalContratsActifs = 0;
    $totalHeuresIncluses = 0;
    $totalHeuresConsommees = 0;
} else {
    // Récupérer tous les contrats de ces clients
    $allContrats = [];
    foreach ($mesClients as $client) {
        $contratsClient = Contrat::getByClient($pdo, $client->getId());
        $allContrats = array_merge($allContrats, $contratsClient);
    }

    // Stats
    $totalContratsActifs = count(array_filter($allContrats, fn($c) => $c->isActif()));
    $totalHeuresIncluses = array_sum(array_map(fn($c) => $c->getHeuresTotales(), array_filter($allContrats, fn($c) => $c->isActif())));
    $totalHeuresConsommees = array_sum(array_map(fn($c) => $c->getHeuresConsommees(), array_filter($allContrats, fn($c) => $c->isActif())));
}

// Préparer les données par contrat
$contratsData = [];
foreach ($allContrats as $c) {
    $clients = $c->getClients($pdo);
    $clientNom = !empty($clients) ? $clients[0]['nom'] : '—';

    $pourcentage = $c->getPourcentageConsomme();
    $heuresText = $c->getHeuresConsommees() . 'h / ' . $c->getHeuresTotales() . 'h';
    $heuresRestantes = $c->getHeuresRestantes();

    // Statut visuel
    $statutClass = 'active';
    $statutLabel = 'Actif';
    if ($c->getStatut() === 'termine') {
        $statutClass = 'completed';
        $statutLabel = 'Terminé';
    } elseif ($c->getStatut() === 'inactif') {
        $statutClass = 'inactive';
        $statutLabel = 'Suspendu';
    } elseif ($pourcentage >= 90 && $c->isActif()) {
        $statutClass = 'warning';
        $statutLabel = 'Bientôt épuisé';
    }

    // Warning row?
    $rowClass = '';
    if ($statutClass === 'warning')
        $rowClass = 'hours-warning';
    if ($statutClass === 'completed')
        $rowClass = 'completed-row';

    // Progress bar class
    $progressClass = '';
    if ($pourcentage >= 90)
        $progressClass = 'warning';
    if ($pourcentage >= 100)
        $progressClass = 'complete';

    $dateDebut = $c->getDateDebut() ? date('d/m/Y', strtotime($c->getDateDebut())) : '—';
    $dateFin = $c->getDateFin() ? date('d/m/Y', strtotime($c->getDateFin())) : '—';

    $contratsData[] = [
        'contrat' => $c,
        'clientNom' => $clientNom,
        'pourcentage' => $pourcentage,
        'heuresText' => $heuresText,
        'heuresRestantes' => $heuresRestantes,
        'statutClass' => $statutClass,
        'statutLabel' => $statutLabel,
        'rowClass' => $rowClass,
        'progressClass' => $progressClass,
        'dateDebut' => $dateDebut,
        'dateFin' => $dateFin,
    ];
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./contrat-manager.css" />
    <title>Mes Contrats - Vector</title>
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

            <h1 class="dashboard-title">Mes Contrats</h1>

            <nav class="header-nav">
                <a href="/dashboard/client/dashboard.php" class="nav-btn">Dashboard</a>
                <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
                <a href="/tickets/client/ticket-manager.php" class="nav-btn">Mes Tickets</a>
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
        <div class="contracts-manager">
            <div class="contracts-header">
                <h2>Mes Contrats</h2>
                <span class="info-text">Consultez et gérez vos contrats de service</span>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="/assets/contrat.png" alt="contrats" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalContratsActifs ?></div>
                        <div class="stat-label">Contrats actifs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="/assets/heures-douverture.png" alt="heures" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalHeuresIncluses ?>h</div>
                        <div class="stat-label">Heures incluses</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <img src="/assets/urgent.png" alt="consommation" />
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalHeuresConsommees ?>h</div>
                        <div class="stat-label">Heures consommées</div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filter-group">
                    <label for="status-filter">Statut:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all">Tous</option>
                        <option value="actif">Actif</option>
                        <option value="expire-bientot">Expire bientôt</option>
                        <option value="expire">Expiré</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort-select">Trier par:</label>
                    <select id="sort-select" class="filter-select">
                        <option value="date-fin">Date d'expiration</option>
                        <option value="heures">Heures restantes</option>
                        <option value="montant">Montant</option>
                    </select>
                </div>
            </div>

            <!-- Contracts Table -->
            <div class="table-container">
                <table class="contracts-table">
                    <thead>
                        <tr>
                            <th>N° Contrat</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Heures</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contratsData as $data):
                            $c = $data['contrat'];
                            ?>
                            <tr class="<?= $data['rowClass'] ?>" data-statut="<?= $c->getStatut() ?>"
                                data-heures-restantes="<?= $data['heuresRestantes'] ?>"
                                data-montant="<?= $c->getMontantTotal() ?>" data-date-fin="<?= $c->getDateFin() ?? '' ?>">
                                <td>
                                    <a href="/contrats/client/generic-contrat.php?id=<?= $c->getId() ?>"
                                        class="contract-link">
                                        #CT<?= str_pad($c->getId(), 3, '0', STR_PAD_LEFT) ?>
                                    </a>
                                </td>
                                <td><?= $data['dateDebut'] ?></td>
                                <td><?= $data['dateFin'] ?></td>
                                <td>
                                    <div class="hours-progress">
                                        <div class="hours-bar">
                                            <div class="hours-fill <?= $data['progressClass'] ?>"
                                                style="width: <?= min($data['pourcentage'], 100) ?>%"></div>
                                        </div>
                                        <span
                                            class="hours-text <?= $data['progressClass'] ?>"><?= $data['heuresText'] ?></span>
                                        <?php if ($data['heuresRestantes'] > 0 && $data['pourcentage'] < 85): ?>
                                            <span class="hours-remaining"><?= $data['heuresRestantes'] ?>h restantes</span>
                                        <?php elseif ($data['pourcentage'] >= 85): ?>
                                            <span class="hours-alert">
                                                <img src="/assets/avertissement.png" alt="attention" class="inline-icon" />
                                                <?= $data['heuresRestantes'] ?>h restantes
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-info">
                                        <span class="amount"><?= number_format($c->getMontantTotal(), 0, ',', ' ') ?>
                                            €</span>
                                        <span class="amount-detail">HT/an</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($contratsData)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:var(--text-gray);padding:30px">Aucun contrat
                                    trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Contract Summary -->
            <div class="summary-section">
                <h3>Résumé global</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Total heures souscrites:</span>
                        <span class="summary-value"><?= $totalHeuresIncluses ?>h</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total heures consommées:</span>
                        <span class="summary-value"><?= $totalHeuresConsommees ?>h
                            (<?= $totalHeuresIncluses > 0 ? round(($totalHeuresConsommees / $totalHeuresIncluses) * 100) : 0 ?>%)</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total heures restantes:</span>
                        <span
                            class="summary-value success"><?= max(0, $totalHeuresIncluses - $totalHeuresConsommees) ?>h</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Coût total annuel:</span>
                        <span
                            class="summary-value"><?= number_format(array_sum(array_map(fn($c) => $c->getMontantTotal(), array_filter($allContrats, fn($c) => $c->isActif()))), 0, ',', ' ') ?>
                            € HT</span>
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