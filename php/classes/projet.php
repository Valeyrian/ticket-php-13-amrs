<?php

class Projet
{
    private ?int $id;
    private string $nom;
    private ?string $description;
    private string $statut;
    private ?string $date_debut;
    private ?string $date_fin_prevue;
    private ?string $date_creation;
    private ?string $date_modification;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        ?string $description = null,
        string $statut = 'actif',
        ?string $date_debut = null,
        ?string $date_fin_prevue = null,
        ?string $date_creation = null,
        ?string $date_modification = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
        $this->statut = $statut;
        $this->date_debut = $date_debut;
        $this->date_fin_prevue = $date_fin_prevue;
        $this->date_creation = $date_creation;
        $this->date_modification = $date_modification;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getDateDebut(): ?string
    {
        return $this->date_debut;
    }

    public function getDateFinPrevue(): ?string
    {
        return $this->date_fin_prevue;
    }

    public function getDateCreation(): ?string
    {
        return $this->date_creation;
    }

    public function getDateModification(): ?string
    {
        return $this->date_modification;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setStatut(string $statut): void
    {
        $allowedStatuts = ['actif', 'archive'];
        if (!in_array($statut, $allowedStatuts)) {
            throw new InvalidArgumentException("Statut invalide : $statut");
        }
        $this->statut = $statut;
    }

    public function setDateDebut(?string $date_debut): void
    {
        $this->date_debut = $date_debut;
    }

    public function setDateFinPrevue(?string $date_fin_prevue): void
    {
        $this->date_fin_prevue = $date_fin_prevue;
    }

    public function setDateCreation(string $date_creation): void
    {
        $this->date_creation = $date_creation;
    }

    public function setDateModification(string $date_modification): void
    {
        $this->date_modification = $date_modification;
    }

    // Méthodes métier

    /**
     * Vérifie si le projet est actif
     */
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifie si le projet est archivé
     */
    public function isArchive(): bool
    {
        return $this->statut === 'archive';
    }



    // Méthodes liées à la base de données

    /**
     * Crée un nouveau projet dans la base de données
     */
    public function create(PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO projet (nom, description, statut, date_debut, date_fin_prevue)
                            VALUES (:nom, :description, :statut, :date_debut, :date_fin_prevue)");

        $stmt->execute([
            ':nom' => $this->nom,
            ':description' => $this->description,
            ':statut' => $this->statut,
            ':date_debut' => $this->date_debut,
            ':date_fin_prevue' => $this->date_fin_prevue
        ]);

        $this->id = (int) $pdo->lastInsertId();
    }

    /**
     * Met à jour le projet dans la base de données
     */
    public function update(PDO $pdo): void
    {
        $stmt = $pdo->prepare("UPDATE projet SET nom = :nom, description = :description, statut = :statut,
                            date_debut = :date_debut, date_fin_prevue = :date_fin_prevue
                            WHERE id = :id");

        $stmt->execute([
            ':id' => $this->id,
            ':nom' => $this->nom,
            ':description' => $this->description,
            ':statut' => $this->statut,
            ':date_debut' => $this->date_debut,
            ':date_fin_prevue' => $this->date_fin_prevue
        ]);
    }

    /**
     * Récupère un projet par son ID
     */
    public static function getById(PDO $pdo, int $id): ?Projet
    {
        $stmt = $pdo->prepare("SELECT * FROM projet WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Projet');
        $projet = $stmt->fetch();

        return $projet ?: null;
    }

    /**
     * Récupère tous les projets
     * @return Projet[]
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM projet ORDER BY date_creation DESC");
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Projet');
        return $stmt->fetchAll();
    }

    /**
     * Récupère les projets par statut
     * @return Projet[]
     */
    public static function getByStatut(PDO $pdo, string $statut): array
    {
        $stmt = $pdo->prepare("SELECT * FROM projet WHERE statut = :statut ORDER BY date_creation DESC");
        $stmt->execute([':statut' => $statut]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Projet');
        return $stmt->fetchAll();
    }

    /**
     * Supprime un projet par son ID
     */
    public static function deleteById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM projet WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Compte le nombre de tickets d'un projet
     */
    public function countTickets(PDO $pdo): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket WHERE projet_id = :projet_id");
        $stmt->execute([':projet_id' => $this->id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte le nombre de tickets ouverts d'un projet
     */
    public function countTicketsOuverts(PDO $pdo): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket WHERE projet_id = :projet_id AND statut NOT IN ('termine', 'valide', 'refuse')");
        $stmt->execute([':projet_id' => $this->id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Calcule le temps total passé sur le projet (somme des temps_passe des tickets)
     */
    public function getTotalTempsPasse(PDO $pdo): float
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(temps_passe), 0) FROM ticket WHERE projet_id = :projet_id");
        $stmt->execute([':projet_id' => $this->id]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Calcule le temps total estimé sur le projet
     */
    public function getTotalTempsEstime(PDO $pdo): float
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(temps_estime), 0) FROM ticket WHERE projet_id = :projet_id");
        $stmt->execute([':projet_id' => $this->id]);
        return (float) $stmt->fetchColumn();
    }

    // ====== Méthodes projet_client ======

    /**
     * Associe un client à ce projet
     */
    public function addClient(PDO $pdo, int $clientId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO projet_client (projet_id, client_id)
                            VALUES (:projet_id, :client_id)");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':client_id' => $clientId
        ]);
    }

    /**
     * Retire un client de ce projet
     */
    public function removeClient(PDO $pdo, int $clientId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM projet_client
                            WHERE projet_id = :projet_id AND client_id = :client_id");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':client_id' => $clientId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère le client (société) associé à ce projet (un seul client max)
     * @return Client|null
     */
    public function getClient(PDO $pdo): ?Client
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM projet_client pc
                            INNER JOIN client c ON pc.client_id = c.id
                            WHERE pc.projet_id = :projet_id
                            LIMIT 1");
        $stmt->execute([':projet_id' => $this->id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        $client = $stmt->fetch();
        return $client ?: null;
    }

    /**
     * Vérifie si un client est associé à ce projet
     */
    public function hasClient(PDO $pdo, int $clientId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM projet_client
                            WHERE projet_id = :projet_id AND client_id = :client_id");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':client_id' => $clientId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les projets d'un client
     * @return Projet[]
     */
    public static function getByClient(PDO $pdo, int $clientId): array
    {
        $stmt = $pdo->prepare("SELECT p.*
                            FROM projet p
                            INNER JOIN projet_client pc ON p.id = pc.projet_id
                            WHERE pc.client_id = :client_id
                            ORDER BY p.date_creation DESC");
        $stmt->execute([':client_id' => $clientId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Projet');
        return $stmt->fetchAll();
    }

    // ====== Méthodes projet_collaborateur ======

    /**
     * Assigne un collaborateur à ce projet
     */
    public function addCollaborateur(PDO $pdo, int $collaborateurId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO projet_collaborateur (projet_id, collaborateur_id)
                            VALUES (:projet_id, :collaborateur_id)");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);



    }

    /**
     * Retire un collaborateur de ce projet
     */
    public function removeCollaborateur(PDO $pdo, int $collaborateurId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM projet_collaborateur
                            WHERE projet_id = :projet_id AND collaborateur_id = :collaborateur_id");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les collaborateurs assignés à ce projet
     * @return array
     */
    public function getCollaborateurs(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT u.*
                            FROM projet_collaborateur pc
                            INNER JOIN users u ON pc.collaborateur_id = u.id
                            WHERE pc.projet_id = :projet_id
                            ORDER BY u.name ASC");
        $stmt->execute([':projet_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un collaborateur est assigné à ce projet
     */
    public function hasCollaborateur(PDO $pdo, int $collaborateurId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM projet_collaborateur
                            WHERE projet_id = :projet_id AND collaborateur_id = :collaborateur_id");
        $stmt->execute([
            ':projet_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les projets d'un collaborateur
     * @return Projet[]
     */
    public static function getByCollaborateur(PDO $pdo, int $collaborateurId): array
    {
        $stmt = $pdo->prepare("SELECT p.*
                            FROM projet p
                            INNER JOIN projet_collaborateur pc ON p.id = pc.projet_id
                            WHERE pc.collaborateur_id = :collaborateur_id
                            ORDER BY p.date_creation DESC");
        $stmt->execute([':collaborateur_id' => $collaborateurId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Projet');
        return $stmt->fetchAll();
    }
}
