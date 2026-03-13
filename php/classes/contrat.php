<?php

class Contrat
{
    private ?int $id;
    private string $nom;
    private string $type;
    private float $heures_totales;
    private float $heures_consommees;
    private float $taux_horaire;
    private float $montant_total;
    private ?string $date_debut;
    private ?string $date_fin;
    private ?string $conditions;
    private string $statut;
    private ?string $date_creation;
    private ?string $date_modification;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $type = '',
        float $heures_totales = 0.00,
        float $heures_consommees = 0.00,
        float $taux_horaire = 0.00,
        float $montant_total = 0.00,
        ?string $date_debut = null,
        ?string $date_fin = null,
        ?string $conditions = null,
        string $statut = 'actif',
        ?string $date_creation = null,
        ?string $date_modification = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->type = $type;
        $this->heures_totales = $heures_totales;
        $this->heures_consommees = $heures_consommees;
        $this->taux_horaire = $taux_horaire;
        $this->montant_total = $montant_total;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->conditions = $conditions;
        $this->statut = $statut;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getHeuresTotales(): float
    {
        return $this->heures_totales;
    }

    public function getHeuresConsommees(): float
    {
        return $this->heures_consommees;
    }

    public function getTauxHoraire(): float
    {
        return $this->taux_horaire;
    }

    public function getMontantTotal(): float
    {
        return $this->montant_total;
    }

    public function getDateDebut(): ?string
    {
        return $this->date_debut;
    }

    public function getDateFin(): ?string
    {
        return $this->date_fin;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function getStatut(): string
    {
        return $this->statut;
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

    public function setType(string $type): void
    {
        $allowedTypes = ['Inclus', 'Facturable'];
        if (!in_array($type, $allowedTypes)) {
            throw new InvalidArgumentException("Type invalide : $type");
        }
        $this->type = $type;
    }

    public function setHeuresTotales(float $heures_totales): void
    {
        $this->heures_totales = $heures_totales;
    }

    public function setHeuresConsommees(float $heures_consommees): void
    {
        $this->heures_consommees = $heures_consommees;
    }

    public function setTauxHoraire(float $taux_horaire): void
    {
        $this->taux_horaire = $taux_horaire;
    }

    public function setMontantTotal(float $montant_total): void
    {
        $this->montant_total = $montant_total;
    }

    public function setDateDebut(?string $date_debut): void
    {
        $this->date_debut = $date_debut;
    }

    public function setDateFin(?string $date_fin): void
    {
        $this->date_fin = $date_fin;
    }

    public function setConditions(?string $conditions): void
    {
        $this->conditions = $conditions;
    }

    public function setStatut(string $statut): void
    {
        $allowedStatuts = ['actif', 'inactif', 'termine'];
        if (!in_array($statut, $allowedStatuts)) {
            throw new InvalidArgumentException("Statut invalide : $statut");
        }
        $this->statut = $statut;
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
     * Vérifie si le contrat est actif
     */
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifie si le contrat est expiré
     */
    public function isExpire(): bool
    {
        return $this->statut === 'termine';
    }

    /**
     * Calcule les heures restantes
     */
    public function getHeuresRestantes(): float
    {
        return max(0, $this->heures_totales - $this->heures_consommees);
    }

    /**
     * Retourne le pourcentage d'heures consommées
     */
    public function getPourcentageConsomme(): float
    {
        if ($this->heures_totales <= 0) {
            return 0;
        }
        return round(($this->heures_consommees / $this->heures_totales) * 100, 2);
    }

    // Méthodes liées à la base de données

    /**
     * Crée un nouveau contrat dans la base de données
     */
    public function create(PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO contrat (nom, type, heures_totales, heures_consommees, taux_horaire, montant_total, date_debut, date_fin, conditions, statut)
                            VALUES (:nom, :type, :heures_totales, :heures_consommees, :taux_horaire, :montant_total, :date_debut, :date_fin, :conditions, :statut)");

        $stmt->execute([
            ':nom' => $this->nom,
            ':type' => $this->type,
            ':heures_totales' => $this->heures_totales,
            ':heures_consommees' => $this->heures_consommees,
            ':taux_horaire' => $this->taux_horaire,
            ':montant_total' => $this->montant_total,
            ':date_debut' => $this->date_debut,
            ':date_fin' => $this->date_fin,
            ':conditions' => $this->conditions,
            ':statut' => $this->statut
        ]);

        $this->id = (int) $pdo->lastInsertId();
    }

    /**
     * Met à jour le contrat dans la base de données
     */
    public function update(PDO $pdo): void
    {
        $stmt = $pdo->prepare("UPDATE contrat SET nom = :nom, type = :type, heures_totales = :heures_totales,
                            heures_consommees = :heures_consommees, taux_horaire = :taux_horaire, montant_total = :montant_total,
                            date_debut = :date_debut, date_fin = :date_fin, conditions = :conditions, statut = :statut
                            WHERE id = :id");

        $stmt->execute([
            ':id' => $this->id,
            ':nom' => $this->nom,
            ':type' => $this->type,
            ':heures_totales' => $this->heures_totales,
            ':heures_consommees' => $this->heures_consommees,
            ':taux_horaire' => $this->taux_horaire,
            ':montant_total' => $this->montant_total,
            ':date_debut' => $this->date_debut,
            ':date_fin' => $this->date_fin,
            ':conditions' => $this->conditions,
            ':statut' => $this->statut
        ]);
    }

    /**
     * Récupère un contrat par son ID
     */
    public static function getById(PDO $pdo, int $id): ?Contrat
    {
        $stmt = $pdo->prepare("SELECT * FROM contrat WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        $contrat = $stmt->fetch();

        return $contrat ?: null;
    }

    /**
     * Récupère tous les contrats
     * @return Contrat[]
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM contrat ORDER BY date_creation DESC");
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        return $stmt->fetchAll();
    }

    /**
     * Récupère les contrats par statut
     * @return Contrat[]
     */
    public static function getByStatut(PDO $pdo, string $statut): array
    {
        $stmt = $pdo->prepare("SELECT * FROM contrat WHERE statut = :statut ORDER BY date_creation DESC");
        $stmt->execute([':statut' => $statut]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        return $stmt->fetchAll();
    }

    /**
     * Récupère les contrats par type
     * @return Contrat[]
     */
    public static function getByType(PDO $pdo, string $type): array
    {
        $stmt = $pdo->prepare("SELECT * FROM contrat WHERE type = :type ORDER BY date_creation DESC");
        $stmt->execute([':type' => $type]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        return $stmt->fetchAll();
    }

    /**
     * Supprime un contrat par son ID
     */
    public static function deleteById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM contrat WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Recalcule les heures consommées depuis les tickets liés au contrat
     */
    public function recalculerHeuresConsommees(PDO $pdo): void
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(t.temps_passe), 0)
                            FROM ticket t
                            INNER JOIN projet p ON t.projet_id = p.id
                            INNER JOIN contrat_projet cp ON p.id = cp.projet_id
                            WHERE cp.contrat_id = :contrat_id");
        $stmt->execute([':contrat_id' => $this->id]);
        $total = (float) $stmt->fetchColumn();

        $this->heures_consommees = $total;

        $update = $pdo->prepare("UPDATE contrat SET heures_consommees = :heures_consommees WHERE id = :id");
        $update->execute([
            ':heures_consommees' => $total,
            ':id' => $this->id
        ]);
    }

    // ====== Méthodes contrat_client ======

    /**
     * Associe un client à ce contrat
     */
    public function addClient(PDO $pdo, int $clientId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO contrat_client (contrat_id, client_id)
                            VALUES (:contrat_id, :client_id)");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':client_id' => $clientId
        ]);
    }

    /**
     * Retire un client de ce contrat
     */
    public function removeClient(PDO $pdo, int $clientId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM contrat_client
                            WHERE contrat_id = :contrat_id AND client_id = :client_id");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':client_id' => $clientId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les clients associés à ce contrat
     * @return array
     */
    public function getClients(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM contrat_client cc
                            INNER JOIN client c ON cc.client_id = c.id
                            WHERE cc.contrat_id = :contrat_id
                            ORDER BY c.nom ASC");
        $stmt->execute([':contrat_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un client est associé à ce contrat
     */
    public function hasClient(PDO $pdo, int $clientId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM contrat_client
                            WHERE contrat_id = :contrat_id AND client_id = :client_id");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':client_id' => $clientId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les contrats d'un client
     * @return Contrat[]
     */
    public static function getByClient(PDO $pdo, int $clientId): array
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM contrat c
                            INNER JOIN contrat_client cc ON c.id = cc.contrat_id
                            WHERE cc.client_id = :client_id
                            ORDER BY c.date_creation DESC");
        $stmt->execute([':client_id' => $clientId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        return $stmt->fetchAll();
    }

    // ====== Méthodes contrat_projet ======

    /**
     * Associe un projet à ce contrat
     */
    public function addProjet(PDO $pdo, int $projetId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO contrat_projet (contrat_id, projet_id)
                            VALUES (:contrat_id, :projet_id)");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':projet_id' => $projetId
        ]);
    }

    /**
     * Retire un projet de ce contrat
     */
    public function removeProjet(PDO $pdo, int $projetId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM contrat_projet
                            WHERE contrat_id = :contrat_id AND projet_id = :projet_id");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':projet_id' => $projetId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les projets associés à ce contrat
     * @return array
     */
    public function getProjets(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT p.*
                            FROM contrat_projet cp
                            INNER JOIN projet p ON cp.projet_id = p.id
                            WHERE cp.contrat_id = :contrat_id
                            ORDER BY p.nom ASC");
        $stmt->execute([':contrat_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un projet est associé à ce contrat
     */
    public function hasProjet(PDO $pdo, int $projetId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM contrat_projet
                            WHERE contrat_id = :contrat_id AND projet_id = :projet_id");
        $stmt->execute([
            ':contrat_id' => $this->id,
            ':projet_id' => $projetId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les contrats d'un projet
     * @return Contrat[]
     */
    public static function getByProjet(PDO $pdo, int $projetId): array
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM contrat c
                            INNER JOIN contrat_projet cp ON c.id = cp.contrat_id
                            WHERE cp.projet_id = :projet_id
                            ORDER BY c.date_creation DESC");
        $stmt->execute([':projet_id' => $projetId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Contrat');
        return $stmt->fetchAll();
    }
}
