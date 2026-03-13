<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../db.php';

require_once "../../php/classes/client.php";
require_once "../../php/classes/contrat.php";
require_once "../../php/classes/projet.php";
require_once "../../php/classes/ticket.php";
require_once "../../php/classes/user.php";

session_start();

// Vérifier que l'utilisateur est connecté et est admin
if (!isset($_SESSION['user'])) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];
if (!$user->isAdmin()) {
  header("Location: /login_or_registration/login.php");
  session_abort();
  exit;
}

// Récupérer tous les utilisateurs depuis la BDD
$allUsers = User::getAll($pdo);

// Récupérer tous les clients
$allClients = Client::getAll($pdo);
$nbClientsActifs = count(Client::getByStatut($pdo, 'actif'));

// Récupérer tous les projets
$allProjets = Projet::getAll($pdo);
$nbProjetsEnCours = count(Projet::getByStatut($pdo, 'actif'));

// Récupérer tous les tickets
$allTickets = Ticket::getAll($pdo);
$nbTicketsOuverts = count(array_filter($allTickets, fn($t) => $t->isOpen()));

// Récupérer tous les collaborateurs
$collaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');
$nbCollaborateursActifs = count(array_filter($collaborateurs, fn($u) => $u->getState() === 'active'));

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./dashboard.css" />
  <title>Dashboard Admin - Vector</title>
  <script src="../../js/theme.js"></script>

  <?php
  // Générer le mapping client -> projets pour le JS 
  echo '<script>window.clientProjects = {';
  foreach ($allClients as $client) {
    echo $client->getId() . ': [';
    $projets = Projet::getByClient($pdo, $client->getId());
    foreach ($projets as $projet) {
      echo '{id: ' . $projet->getId() . ", nom: '" . addslashes($projet->getNom()) . "'},";
    }
    echo '],';
  }
  echo '};</script>';

  // Mapping client -> contrats pour le JS (modal projet)
  echo '<script>window.clientContrats = {';
  foreach ($allClients as $client) {
    $contratsClient = Contrat::getByClient($pdo, $client->getId());
    echo $client->getId() . ': [';
    foreach ($contratsClient as $ct) {
      echo '{id: ' . $ct->getId() . ", nom: '" . addslashes($ct->getNom()) . "'},";
    }
    echo '],';
  }
  echo '};</script>';
  ?>


</head>

<body class="oswald-font1 role-admin">
  <header class="dashboard-header">
    <div class="header-container">
      <div class="logo-section">
        <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
        <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
      </div>

      <div class="header-separator"></div>

      <h1 class="dashboard-title">Dashboard Admin</h1>

      <nav class="header-nav">
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">
          Tickets
        </a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">
          Projets
        </a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">
          Utilisateurs
        </a>
        <a href="/contrats/admin/contrat-manager.php" class="nav-btn">
          Contrats
        </a>
        <a href="/clients/admin/client-manager.php" class="nav-btn">
          Clients
        </a>
      </nav>

      <div class="profile-info">
        <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
        <div class="profile-text">
          <span class="profile-name">Admin Principal</span>
          <span class="profile-role">Administrateur</span>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="dashboard-layout">
      <!-- Contenu Central -->
      <div class="dashboard-container">
        <!-- Vue d'ensemble des statistiques -->
        <div class="stats-overview">
          <div class="stat-card">
            <div class="stat-icon clients-icon">
              <img src="/assets/client.png" alt="clients icon" />
            </div>
            <div class="stat-content">
              <h3>Clients</h3>
              <div class="stat-value"><?= $nbClientsActifs ?></div>
              <div class="stat-label">Clients actifs</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon projects-icon">
              <img src="/assets/project.png" alt="projets iccon" />
            </div>
            <div class="stat-content">
              <h3>Projets</h3>
              <div class="stat-value"><?= $nbProjetsEnCours ?></div>
              <div class="stat-label">Projets en cours</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon tickets-icon">
              <img src="/assets/ticket.png" alt="tickets icon" />
            </div>
            <div class="stat-content">
              <h3>Tickets</h3>
              <div class="stat-value"><?= $nbTicketsOuverts ?></div>
              <div class="stat-label">Tickets ouverts</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon users-icon">
              <img src="/assets/utilisateur.png" alt="users icon" />
            </div>
            <div class="stat-content">
              <h3>Collaborateurs</h3>
              <div class="stat-value"><?= $nbCollaborateursActifs ?></div>
              <div class="stat-label">Actifs</div>
            </div>
          </div>
        </div>

        <!-- Tickets récents tous projets -->
        <div id="tickets" class="open-tickets-section">
          <div class="section-header">
            <h2>Tous les tickets</h2>
            <button class="btn-primary">+ Créer un ticket</button>
          </div>
          <table class="tickets-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Client</th>
                <th>Projet</th>
                <th>Collaborateurs</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Type</th>
                <th>Temps</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allTickets as $ticket): ?>
                <tr>
                  <td>
                    <a href="/tickets/admin/generic-ticket.php?id=<?= urlencode($ticket->getId()) ?>"
                      class="ticket-id-link" style="color: inherit; text-decoration: none;">
                      <span class="ticket-id-badge">#<?= htmlspecialchars($ticket->getId()) ?></span>
                    </a>
                  </td>
                  <td class="ticket-title-cell">
                    <a href="/tickets/admin/generic-ticket.php?id=<?= urlencode($ticket->getId()) ?>"
                      class="ticket-title-link" style="color: inherit; text-decoration: none;">
                      <?= htmlspecialchars($ticket->getTitre()) ?>
                    </a>
                  </td>
                  <td>
                    <?php
                    $projet = Projet::getById($pdo, $ticket->getProjetId());
                    $client = $projet ? $projet->getClient($pdo) : null;
                    echo $client ? htmlspecialchars($client->getNom()) : '-';
                    ?>
                  </td>
                  <td>
                    <?php
                    echo $projet ? htmlspecialchars($projet->getNom()) : '-';
                    ?>
                  </td>
                  <td>
                    <div class="collaborators-mini">
                      <?php
                      $collabs = $ticket->getCollaborateurs($pdo);
                      foreach ($collabs as $collab) {
                        echo '<div class="avatar tiny">' . htmlspecialchars($collab['name'][0] . $collab['surname'][0]) . '</div>';
                      }
                      if (count($collabs) > 3) {
                        echo '<div class="avatar-more tiny">+' . (count($collabs) - 3) . '</div>';
                      }
                      ?>
                    </div>
                  </td>
                  <td><span
                      class="priority-badge <?= $ticket->getPriorite() ?>"><?= ucfirst($ticket->getPriorite()) ?></span>
                  </td>
                  <td>
                    <span class="status-badge status-<?= $ticket->getStatut() ?>">
                      <?= ucfirst($ticket->getStatut()) ?>
                    </span>
                  </td>
                  <td>
                    <span class="billing-badge billing-<?= $ticket->getType() ?>">
                      <?= ucfirst($ticket->getType()) ?>
                    </span>
                  </td>
                  <td><?= $ticket->getTempsPasse() ?>h/<?= $ticket->getTempsEstime() ?>h</td>
                  <td><?= date('d M', strtotime($ticket->getDateCreation())) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>


        <!-- Section Gestion des Projets -->
        <div id="projects" class="management-section">
          <div class="section-header">
            <h2>Gestion des Projets</h2>
            <button class="btn-primary">+ Ajouter un projet</button>
          </div>
          <table class="management-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Date début</th>
                <th>Date fin prévue</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allProjets as $projet): ?>
                <tr>
                  <td>
                    <a href="/projects/admin/generic-project.php?id=<?= urlencode($projet->getId()) ?>"
                      style="color: inherit; text-decoration: none;">
                      <?= htmlspecialchars($projet->getNom()) ?>
                    </a>
                  </td>
                  <td>
                    <?= htmlspecialchars($projet->getDescription()) ?>
                  </td>
                  <td>
                    <?php
                    $client = $projet->getClient($pdo);
                    echo $client ? '<a href="/clients/admin/generic-client.php?id=' . urlencode($client->getId()) . '" style="color: inherit; text-decoration: none;">' . htmlspecialchars($client->getNom()) . '</a>' : '-';
                    ?>
                  </td>
                  <td><span class="status-<?= $projet->getStatut() ?>">
                      <?= ucfirst($projet->getStatut()) ?>
                    </span></td>
                  <td>
                    <?= $projet->getDateDebut() ? date('d/m/Y', strtotime($projet->getDateDebut())) : '-' ?>
                  </td>
                  <td>
                    <?= $projet->getDateFinPrevue() ? date('d/m/Y', strtotime($projet->getDateFinPrevue())) : '-' ?>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-delete" title="Supprimer">
                        <img src="/assets/supprimer.png" alt="supprimer" />
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>


        <!-- Section Gestion des Utilisateurs -->
        <div id="users" class="management-section">
          <div class="section-header">
            <h2>Gestion des Utilisateurs</h2>
            <button class="btn-primary">+ Ajouter un utilisateur</button>
          </div>
          <table class="management-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allUsers as $u): ?>
                <?php
                $roleBadge = match ($u->getRole()) {
                  'admin' => '<span class="role-badge admin">Administrateur</span>',
                  'collaborateur' => '<span class="role-badge collaborateur">Collaborateur</span>',
                  default => '<span class="role-badge client">Client</span>',
                };
                $statusClass = $u->getState() === 'active' ? 'status-active' : 'status-inactive';
                $statusLabel = $u->getState() === 'active' ? 'Actif' : 'Inactif';
                ?>
                <tr>
                  <td>
                    <div class="user-cell">
                      <div class="avatar small"><?= htmlspecialchars($u->getInitials()) ?></div>
                      <a href="/utilisateurs/admin/generic-utilisateur.php?id=<?= urlencode($u->getId()) ?>"
                        style="color: inherit; text-decoration: none;">
                        <?= htmlspecialchars($u->getName() . ' ' . $u->getSurname()) ?>
                      </a>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($u->getEmail()) ?></td>
                  <td><?= $roleBadge ?></td>
                  <td><span class="<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <?php if ($u->getId() !== $user->getId()): ?>
                        <form action="../../php/actions/delete-user.php" method="POST" class="inline-form">
                          <input type="hidden" name="id" value="<?= $u->getId() ?>" />
                          <button type="submit" class="btn-delete" title="Supprimer" data-user-id="<?= $u->getId() ?>">
                            <img src="/assets/supprimer.png" alt="supprimer" class="inline-icon" />
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>


        <!-- Section Gestion des Clients -->
        <div id="clients" class="management-section">
          <div class="section-header">
            <h2>Gestion des Clients</h2>
            <button class="btn-primary">+ Ajouter un client</button>
          </div>
          <table class="management-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Contact Principal</th>
                <th>Projets actifs</th>
                <th>Tickets ouverts</th>
                <th>Contrats en cours</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allClients as $client): ?>
                <tr>
                  <td>
                    <div class="user-cell">
                      <div class="avatar small client-avatar">
                        <?= htmlspecialchars(substr($client->getNom(), 0, 1) . substr($client->getNom(), -1)) ?>
                      </div>
                      <a href="/clients/admin/generic-client.php?id=<?= urlencode($client->getId()) ?>"
                        style="color: inherit; text-decoration: none;">
                        <?= htmlspecialchars($client->getNom()) ?>
                      </a>
                    </div>
                  </td>
                  <td>
                    <?php
                    // Contact principal
                    $contact = $client->getContactPrincipal($pdo);
                    echo $contact ? htmlspecialchars($contact['email']) : '-';
                    ?>
                  </td>
                  <td>
                    <?php
                    // Projets actifs
                    $projets = $client->getClientsByUtilisateur($pdo, $client->getId());
                    echo count($projets);
                    ?>
                  </td>
                  <td>
                    <?php
                    // Tickets ouverts
                    $tickets = Ticket::getAll($pdo);
                    $ticketsClient = array_filter($tickets, function ($t) use ($client, $pdo) {
                      $projet = Projet::getById($pdo, $t->getProjetId());
                      $projetClient = $projet ? $projet->getClient($pdo) : null;
                      return $projetClient && $projetClient->getId() == $client->getId() && $t->isOpen();
                    });
                    echo count($ticketsClient);
                    ?>
                  </td>
                  <td>
                    <?php
                    // Contrats en cours
                    $contrats = Contrat::getByClient($pdo, $client->getId());
                    $contratsEnCours = array_filter($contrats, fn($c) => $c->getStatut() === 'actif');
                    echo count($contratsEnCours);
                    ?>
                  </td>
                  <td><span class="status-<?= $client->getStatut() ?>"><?= ucfirst($client->getStatut()) ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-delete" title="Supprimer">
                        <img src="/assets/supprimer.png" alt="supprimer" />
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Section Gestion des Contrats -->
        <div id="contracts" class="management-section">
          <div class="section-header">
            <h2>Gestion des Contrats</h2>
            <button class="btn-primary">+ Créer un contrat</button>
          </div>
          <div class="contracts-grid">
            <?php
            $allContrats = Contrat::getAll($pdo);
            foreach ($allContrats as $contrat):
              // Récupérer le client associé
              $clients = $contrat->getClients($pdo);
              $clientName = isset($clients[0]['nom']) ? $clients[0]['nom'] : '-';
              // Récupérer les projets associés
              $projets = $contrat->getProjets($pdo);
              // Calcul période
              $periode = $contrat->getDateDebut() && $contrat->getDateFin() ?
                date('d/m/Y', strtotime($contrat->getDateDebut())) . ' - ' . date('d/m/Y', strtotime($contrat->getDateFin())) : '-';
              // Pourcentage consommé
              $pourcentage = $contrat->getPourcentageConsomme();
              ?>
              <div class="contract-card">
                <div class="contract-header">
                  <h3>Contrat : <?= htmlspecialchars($clientName) ?></h3>
                  <span class="contract-status <?= $contrat->getStatut() ?>"><?= ucfirst($contrat->getStatut()) ?></span>
                </div>
                <div class="contract-details">
                  <div class="detail-row">
                    <span class="detail-label">Projet<?= count($projets) > 1 ? 's' : '' ?>:</span>
                    <span class="detail-value">
                      <?php if (empty($projets)): ?>
                        -
                      <?php else: ?>
                        <?= implode(', ', array_map(fn($p) => htmlspecialchars($p['nom']), $projets)) ?>
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Heures incluses:</span>
                    <span class="detail-value"><?= $contrat->getHeuresTotales() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Consommées:</span>
                    <span class="detail-value text-warning"><?= $contrat->getHeuresConsommees() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Restantes:</span>
                    <span class="detail-value text-success"><?= $contrat->getHeuresRestantes() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Taux horaire (supp):</span>
                    <span class="detail-value"><?= $contrat->getTauxHoraire() ?>€/h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Période:</span>
                    <span class="detail-value"><?= $periode ?></span>
                  </div>
                </div>
                <div class="contract-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $pourcentage ?>%"></div>
                  </div>
                  <span class="progress-label"><?= $pourcentage ?>% consommé</span>
                </div>
                <div class="contract-actions">
                  <a href="/contrats/admin/generic-contrat.php?id=<?= $contrat->getId() ?>" class="btn-secondary">Voir
                    détails</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar Droite - Statistiques -->
      <aside class="sidebar-right">
        <!-- Carte Activité Récente -->
        <div class="sidebar-card">
          <h3>Activité Récente</h3>
          <div class="activity-list">
            <?php
            // Construire un tableau d'activités récentes à partir des données réelles
            $activities = [];

            // Tickets récents (création)
            foreach ($allTickets as $t) {
              if ($t->getDateCreation()) {
                $activities[] = [
                  'date' => $t->getDateCreation(),
                  'icon_class' => 'new-ticket',
                  'icon_img' => "/assets/ticket.png",
                  'title' => 'Ticket créé',
                  'meta' => '#' . $t->getId() . ' - ' . htmlspecialchars($t->getTitre()),
                ];
              }
              if ($t->getDateModification() && $t->getDateModification() !== $t->getDateCreation()) {
                $activities[] = [
                  'date' => $t->getDateModification(),
                  'icon_class' => $t->getStatut() === 'ferme' ? 'ticket-closed' : 'new-ticket',
                  'icon_img' => "/assets/ticket.png",
                  'title' => $t->getStatut() === 'ferme' ? 'Ticket fermé' : 'Ticket modifié',
                  'meta' => '#' . $t->getId() . ' - ' . htmlspecialchars($t->getTitre()),
                ];
              }
            }

            // Utilisateurs récents (inscription)
            foreach ($allUsers as $u) {
              if ($u->getCreationDate()) {
                $activities[] = [
                  'date' => $u->getCreationDate(),
                  'icon_class' => 'user-added',
                  'icon_img' => "/assets/account.png",
                  'title' => 'Nouvel utilisateur',
                  'meta' => htmlspecialchars($u->getName() . ' ' . $u->getSurname()),
                ];
              }
            }

            // Contrats récents (modification)
            $allContratsForSidebar = Contrat::getAll($pdo);
            foreach ($allContratsForSidebar as $c) {
              if ($c->getDateModification()) {
                $cClients = $c->getClients($pdo);
                $cClientName = isset($cClients[0]['nom']) ? $cClients[0]['nom'] : $c->getNom();
                $activities[] = [
                  'date' => $c->getDateModification(),
                  'icon_class' => 'contract-updated',
                  'icon_img' => '/assets/contrat.png',
                  'title' => 'Contrat modifié',
                  'meta' => htmlspecialchars($cClientName),
                ];
              }
            }

            // Trier par date décroissante et prendre les 5 plus récentes
            usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
            $activities = array_slice($activities, 0, 5);

            // Fonction pour afficher le temps relatif
            function tempsRelatif(string $date): string
            {
              $diff = time() - strtotime($date);
              if ($diff < 0)
                return "À l'instant";
              if ($diff < 60)
                return 'Il y a ' . $diff . 's';
              if ($diff < 3600)
                return 'Il y a ' . floor($diff / 60) . ' min';
              if ($diff < 86400)
                return 'Il y a ' . floor($diff / 3600) . 'h';
              if ($diff < 604800)
                return 'Il y a ' . floor($diff / 86400) . 'j';
              return date('d/m/Y', strtotime($date));
            }

            foreach ($activities as $act): ?>
              <div class="activity-item">
                <div class="activity-icon <?= $act['icon_class'] ?>">
                  <?php if ($act['icon_img']): ?>
                    <img src="<?= $act['icon_img'] ?>" alt="icon" />
                  <?php endif; ?>
                </div>
                <div class="activity-content">
                  <div class="activity-title"><?= $act['title'] ?></div>
                  <div class="activity-meta"><?= $act['meta'] ?> - <?= tempsRelatif($act['date']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($activities)): ?>
              <div class="activity-item">
                <div class="activity-content">
                  <div class="activity-title">Aucune activité récente</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Alertes -->
        <div class="sidebar-card alerts-card">
          <h3>Alertes</h3>
          <?php
          // Alerte 1 : Contrats avec heures presque épuisées (> 80% consommé)
          $alertesHeures = [];
          foreach ($allContratsForSidebar as $c) {
            if ($c->getStatut() === 'actif' && $c->getHeuresTotales() > 0) {
              $pct = $c->getPourcentageConsomme();
              if ($pct >= 80) {
                $cClients = $c->getClients($pdo);
                $cName = isset($cClients[0]['nom']) ? $cClients[0]['nom'] : $c->getNom();
                $alertesHeures[] = [
                  'nom' => $cName,
                  'restantes' => $c->getHeuresRestantes(),
                  'pct' => $pct,
                ];
              }
            }
          }

          // Alerte 2 : Tickets urgents (priorité haute + ouverts)
          $ticketsUrgents = array_filter($allTickets, fn($t) => $t->getPriorite() === 'haute' && $t->isOpen());
          $nbUrgents = count($ticketsUrgents);

          // Alerte 3 : Tickets facturables en attente de validation
          $ticketsAValider = array_filter($allTickets, fn($t) => $t->getType() === 'Facturable' && $t->getValidationStatus() === 'en_attente');
          $nbAValider = count($ticketsAValider);

          $hasAlertes = !empty($alertesHeures) || $nbUrgents > 0 || $nbAValider > 0;
          ?>

          <?php foreach ($alertesHeures as $alerte): ?>
            <div class="alert-item warning">
              <div class="alert-icon">
                <img src="/assets/avertissement.png" alt="alerte" />
              </div>
              <div class="alert-content">
                <div class="alert-title">Heures presque épuisées</div>
                <div class="alert-text"><?= htmlspecialchars($alerte['nom']) ?>: <?= $alerte['restantes'] ?>h restantes
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if ($nbUrgents > 0): ?>
            <div class="alert-item urgent">
              <div class="alert-icon">
                <img src="/assets/urgent.png" alt="urgent" />
              </div>
              <div class="alert-content">
                <div class="alert-title">Tickets urgents</div>
                <div class="alert-text">
                  <?= $nbUrgents ?> ticket<?= $nbUrgents > 1 ? 's nécessitent' : ' nécessite' ?> une attention immédiate
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($nbAValider > 0): ?>
            <div class="alert-item info">
              <div class="alert-icon"></div>
              <div class="alert-content">
                <div class="alert-title">Validation en attente</div>
                <div class="alert-text"><?= $nbAValider ?> ticket<?= $nbAValider > 1 ? 's facturables' : ' facturable' ?>
                  à valider</div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!$hasAlertes): ?>
            <div class="alert-item info">
              <div class="alert-icon"></div>
              <div class="alert-content">
                <div class="alert-title">Tout est en ordre</div>
                <div class="alert-text">Aucune alerte pour le moment</div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Graphique heures par projet -->
        <div class="sidebar-card">
          <h3>Top Projets (heures)</h3>
          <div class="project-hours-list">
            <?php
            // Calculer les heures passées par projet
            $projetHeures = [];
            foreach ($allProjets as $p) {
              $heures = $p->getTotalTempsPasse($pdo);
              if ($heures > 0) {
                $projetHeures[] = [
                  'nom' => $p->getNom(),
                  'heures' => $heures,
                ];
              }
            }
            // Trier par heures décroissantes
            usort($projetHeures, fn($a, $b) => $b['heures'] <=> $a['heures']);
            $topProjets = array_slice($projetHeures, 0, 5);
            $maxHeures = !empty($topProjets) ? $topProjets[0]['heures'] : 1;

            foreach ($topProjets as $tp): ?>
              <div class="project-hours-item">
                <div class="project-info">
                  <span class="project-name-small"><?= htmlspecialchars($tp['nom']) ?></span>
                  <span class="hours-count"><?= $tp['heures'] ?>h</span>
                </div>
                <div class="hours-bar">
                  <div class="hours-fill" style="width: <?= round(($tp['heures'] / $maxHeures) * 100) ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($topProjets)): ?>
              <div class="project-hours-item">
                <div class="project-info">
                  <span class="project-name-small">Aucun projet avec des heures</span>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <!-- Modal Créer un Ticket -->
  <div class="modal-overlay" id="modal-ticket">
    <div class="modal">
      <div class="modal-header">
        <h2>Nouveau Ticket</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" method="POST" action="/php/actions/add-ticket.php">
        <div class="form-section">
          <h3>Informations Générales</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-title">Titre du ticket *</label>
              <input type="text" id="ticket-title" name="title" required placeholder="Ex: Problème de connexion" />
            </div>
          </div>
          <div class="form-group">
            <label for="ticket-description">Description *</label>
            <textarea id="ticket-description" rows="3" required name="description"
              placeholder="Description détaillée du ticket..."></textarea>
          </div>
        </div>
        <div class="form-section">
          <h3>Client et Projet</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-client">Client *</label>
              <select id="ticket-client" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $client): ?>
                  <option value="<?= $client->getId() ?>"><?= htmlspecialchars($client->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="ticket-project">Projet *</label>
              <select id="ticket-project" name="project_id" required>
                <option value="">Sélectionner un projet</option>
                <?php foreach ($allProjets as $projet): ?>
                  <option value="<?= $projet->getId() ?>"><?= htmlspecialchars($projet->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Priorité et Statut</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-priority">Priorité *</label>
              <select id="ticket-priority" name="priority" required>
                <option value="">Sélectionner</option>
                <option value="haute">Haute</option>
                <option value="moyenne">Moyenne</option>
                <option value="basse">Basse</option>
              </select>
            </div>
            <div class="form-group">
              <label for="ticket-status">Statut</label>
              <select id="ticket-status" name="status">
                <option value="nouveau">Nouveau</option>
                <option value="en_cours">En cours</option>
                <option value="en_attente_client">En attente client</option>
                <option value="termine">Terminé</option>
                <option value="a_valider">À validé</option>
                <option value="valide">Validé</option>
                <option value="refuse">Refusé</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Assignation et Temps</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-collaborators">Collaborateurs</label>
              <select id="ticket-collaborators" name="collaborators[]" multiple size="4">
                <?php foreach ($collaborateurs as $collab): ?>
                  <option value="<?= $collab->getId() ?>">
                    <?= htmlspecialchars($collab->getName() . ' ' . $collab->getSurname()) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small>Maintenez Ctrl pour sélectionner plusieurs collaborateurs</small>
            </div>
            <div class="form-group">
              <label for="ticket-time">Temps estimé (heures) *</label>
              <input type="number" id="ticket-time" name="ticket-time" required min="0.5" step="0.5"
                placeholder="Ex: 8" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Facturation</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-billing">Type de facturation *</label>
              <select id="ticket-billing" name="billing_type" required>
                <option value="inclus">Inclus dans le contrat</option>
                <option value="facturable">Facturable</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">Créer le ticket</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Ajouter un Utilisateur -->
  <div class="modal-overlay" id="modal-user">
    <div class="modal">
      <div class="modal-header">
        <h2>Ajouter un Utilisateur</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" action="../../php/actions/add-user.php" method="POST">
        <div class="form-section">
          <h3>Informations Personnelles</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-firstname">Prénom *</label>
              <input type="text" id="user-firstname" name="name" required placeholder="Ex: Jean" />
            </div>
            <div class="form-group">
              <label for="user-lastname">Nom *</label>
              <input type="text" id="user-lastname" name="surname" required placeholder="Ex: Dupont" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="user-email">Email *</label>
              <input type="email" id="user-email" name="email" required placeholder="Ex: jean.dupont@vector.fr" />
            </div>
            <div class="form-group">
              <label for="user-phone">Téléphone</label>
              <input type="tel" id="user-phone" name="phone" placeholder="Ex: 06 12 34 56 78" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="user-password">Mot de passe *</label>
              <input type="password" id="user-password" name="password" required minlength="8"
                placeholder="Minimum 8 caractères" />
            </div>
            <div class="form-group">
              <label for="user-password-confirm">Confirmer le mot de passe *</label>
              <input type="password" id="user-password-confirm" name="password_confirm" required minlength="8"
                placeholder="Répéter le mot de passe" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Rôle et Accès</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-role">Rôle *</label>
              <select id="user-role" name="role" required>
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur</option>
                <option value="collaborateur">Collaborateur</option>
                <option value="client">Client</option>
              </select>
            </div>
            <div class="form-group">
              <label for="user-status">Statut</label>
              <select id="user-status" name="state">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section" id="section-company" style="display: none;">
          <h3>Entreprise</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-company">Entreprise *</label>
              <select id="user-company" name="company">
                <option value="">Sélectionner ou créer une entreprise</option>
                <?php
                $companies = $pdo->query("SELECT DISTINCT company FROM users WHERE company != '' ORDER BY company ASC")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($companies as $comp):
                  ?>
                  <option value="<?= htmlspecialchars($comp) ?>"><?= htmlspecialchars($comp) ?></option>
                <?php endforeach; ?>
                <option value="__new__">+ Créer une nouvelle entreprise</option>
              </select>
            </div>
            <div class="form-group" id="new-company-group" style="display: none;">
              <label for="user-new-company">Nom de l'entreprise *</label>
              <input type="text" id="user-new-company" name="new_company" placeholder="Ex: Entreprise ABC" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Assignation Projets</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-projects">Projets assignés</label>
              <select id="user-projects" multiple size="3">
                <?php foreach ($allProjets as $projet): ?>
                  <option value="<?= $projet->getId() ?>"><?= htmlspecialchars($projet->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
              <small>Maintenez Ctrl pour sélectionner plusieurs projets</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">
            Ajouter l'utilisateur
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Ajouter un Client -->
  <div class="modal-overlay" id="modal-client">
    <div class="modal">
      <div class="modal-header">
        <h2>Ajouter un Client</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" action="../../php/actions/add-client.php" method="POST">
        <div class="form-section">
          <h3>Informations Client</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="client-nom">Nom de l'entreprise *</label>
              <input type="text" id="client-nom" name="nom" required placeholder="Ex: Entreprise ABC" />
            </div>
            <div class="form-group">
              <label for="client-adresse">Adresse</label>
              <input type="text" id="client-adresse" name="adresse" placeholder="Ex: 12 rue de Paris, 75001" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="client-code-postal">Code postal</label>
              <input type="text" id="client-code-postal" name="code_postal" maxlength="10" placeholder="Ex: 75001" />
            </div>
            <div class="form-group">
              <label for="client-ville">Ville</label>
              <input type="text" id="client-ville" name="ville" maxlength="100" placeholder="Ex: Paris" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="client-pays">Pays</label>
              <input type="text" id="client-pays" name="pays" maxlength="100" value="France" placeholder="Ex: France" />
            </div>
            <div class="form-group">
              <label for="client-statut">Statut</label>
              <select id="client-statut" name="statut">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Contact Principal</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="client-contact-principal">Sélectionner le contact principal </label>
              <select id="client-contact-principal" name="contact_principal">
                <option value="">Sélectionner un utilisateur</option>
                <?php foreach ($allUsers as $user): ?>
                  <option value="<?= $user->getId() ?>">
                    <?= htmlspecialchars($user->getName() . ' ' . $user->getSurname() . ' (' . $user->getEmail() . ')') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small>Ce contact sera lié comme principal pour ce client.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">Ajouter le client</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Ajouter un Projet -->
  <div class="modal-overlay" id="modal-project">
    <div class="modal" id="add-project-modal">
      <div class="modal-header">
        <h2>Nouveau Projet</h2>
        <button class="modal-close" id="close-modal">&times;</button>
      </div>
      <form id="add-project-form" class="modal-form" method="POST" action="/php/actions/add-project.php">
        <div class="form-section">
          <h3>Informations Générales</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="project-name">Nom du projet *</label>
              <input type="text" id="project-name" name="nom" required placeholder="Ex: Refonte Site Web" />
            </div>
          </div>
          <div class="form-group">
            <label for="project-description">Description *</label>
            <textarea id="project-description" name="description" rows="3" required
              placeholder="Description du projet..."></textarea>
          </div>
        </div>

        <div class="form-section">
          <h3>Client et Contrat</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="client-select">Client *</label>
              <select id="client-select" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $c): ?>
                  <option value="<?= $c->getId() ?>"><?= htmlspecialchars($c->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="contract-select">Contrat associé</label>
              <select id="contract-select" name="contrat_id">
                <option value="">Sélectionnez d'abord un client</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Dates</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="start-date">Date de début *</label>
              <input type="date" id="start-date" name="date_debut" required />
            </div>
            <div class="form-group">
              <label for="end-date">Date de fin prévue *</label>
              <input type="date" id="end-date" name="date_fin_prevue" required />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Collaborateurs</h3>
          <div class="form-group">
            <label for="collaborators">Assigner des collaborateurs</label>
            <select id="collaborators" name="collaborators[]" multiple size="4">
              <?php foreach ($collaborateurs as $collab): ?>
                <option value="<?= $collab->getId() ?>">
                  <?= htmlspecialchars($collab->getName() . ' ' . $collab->getSurname()) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small style="color: var(--text-muted); margin-top: 0.3rem">Maintenez Ctrl/Cmd pour sélectionner plusieurs
              collaborateurs</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" id="cancel-modal">
            Annuler
          </button>
          <button type="submit" class="btn-submit">Créer le projet</button>
          <div class="form-error" id="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Créer un Contrat -->
  <div class="modal-overlay" id="modal-contract">
    <div class="modal">
      <div class="modal-header">
        <h2>Créer un Contrat</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" action="../../php/actions/add-contrat.php" method="POST">
        <div class="form-section">
          <h3>Informations du Contrat</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-nom">Nom du contrat *</label>
              <input type="text" id="contract-nom" name="nom" required placeholder="Ex: Contrat Annuel 2026" />
            </div>
            <div class="form-group">
              <label for="contract-type">Type *</label>
              <select id="contract-type" name="type" required>
                <option value="Incus">Inclus</option>
                <option value="Facturable">Facturable</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-client">Client *</label>
              <select id="contract-client" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $client): ?>
                  <option value="<?= $client->getId() ?>"><?= htmlspecialchars($client->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Heures et Tarification</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-heures-totales">Heures totales *</label>
              <input type="number" id="contract-heures-totales" name="heures_totales" required min="0" step="0.5"
                placeholder="Ex: 50" />
            </div>
            <div class="form-group">
              <label for="contract-taux-horaire">Taux horaire (€/h) *</label>
              <input type="number" id="contract-taux-horaire" name="taux_horaire" required min="0" step="0.01"
                placeholder="Ex: 85" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Montant total (€)</label>
              <div id="contract-montant-total" class="computed-value">0.00 €</div>
              <input type="hidden" name="montant_total" id="contract-montant-total-hidden" value="0" />
            </div>
            <div class="form-group">
              <label for="contract-statut">Statut</label>
              <select id="contract-statut" name="statut">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
                <option value="termine">Terminé</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Période</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-date-debut">Date de début *</label>
              <input type="date" id="contract-date-debut" name="date_debut" required />
            </div>
            <div class="form-group">
              <label for="contract-date-fin">Date de fin *</label>
              <input type="date" id="contract-date-fin" name="date_fin" required />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Conditions</h3>
          <div class="form-group">
            <label for="contract-conditions">Conditions du contrat</label>
            <textarea id="contract-conditions" name="conditions" rows="3"
              placeholder="Conditions particulières..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">Créer le contrat</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Confirmation Suppression -->
  <div class="modal-overlay" id="modal-confirm-delete">
    <div class="modal modal-small">
      <div class="modal-header">
        <h2>Confirmer la suppression</h2>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body">
        <p class="confirm-message">Êtes-vous sûr de vouloir supprimer l'utilisateur <strong
            id="delete-user-name"></strong> ?</p>
        <p class="confirm-warning">Cette action est irréversible.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel">Annuler</button>
        <button type="button" class="btn-submit btn-danger" id="btn-confirm-delete">Supprimer</button>
      </div>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./dashboard.js" defer></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>