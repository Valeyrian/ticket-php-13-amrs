<?php

class Ticket
{
    private ?int $id;
    private ?int $projet_id;
    private string $titre;
    private ?string $description;
    private string $statut;
    private string $priorite;
    private string $type;
    private float $temps_estime;
    private float $temps_passe;
    private ?string $validation_status;
    private ?string $date_creation;
    private ?string $date_modification;

    public function __construct(
        ?int $id = null,
        ?int $projet_id = null,
        string $titre = '',
        ?string $description = null,
        string $statut = 'nouveau',
        string $priorite = 'moyenne',
        string $type = 'inclus',
        float $temps_estime = 0.00,
        float $temps_passe = 0.00,
        ?string $validation_status = null,
        ?string $date_creation = null,
        ?string $date_modification = null
    ) {
        $this->id = $id;
        $this->projet_id = $projet_id;
        $this->titre = $titre;
        $this->description = $description;
        $this->statut = $statut;
        $this->priorite = $priorite;
        $this->type = $type;
        $this->temps_estime = $temps_estime;
        $this->temps_passe = $temps_passe;
        $this->validation_status = $validation_status;
        $this->date_creation = $date_creation;
        $this->date_modification = $date_modification;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjetId(): ?int
    {
        return $this->projet_id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTempsEstime(): float
    {
        return $this->temps_estime;
    }

    public function getTempsPasse(): float
    {
        return $this->temps_passe;
    }

    public function getValidationStatus(): ?string
    {
        return $this->validation_status;
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

    public function setProjetId(int $projet_id): void
    {
        $this->projet_id = $projet_id;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setStatut(string $statut): void
    {
        $allowedStatuts = ['nouveau', 'progress', 'pending', 'termine', 'a-valider', 'valide', 'refuse'];
        if (!in_array($statut, $allowedStatuts)) {
            throw new InvalidArgumentException("Statut invalide : $statut");
        }
        $this->statut = $statut;
    }

    public function setPriorite(string $priorite): void
    {
        $allowedPriorites = ['haute', 'moyenne', 'basse'];
        if (!in_array($priorite, $allowedPriorites)) {
            throw new InvalidArgumentException("Priorité invalide : $priorite");
        }
        $this->priorite = $priorite;
    }

    public function setType(string $type): void
    {
        $allowedTypes = ['inclus', 'facturable'];
        if (!in_array($type, $allowedTypes)) {
            throw new InvalidArgumentException("Type invalide : $type");
        }
        $this->type = $type;
    }

    public function setTempsEstime(float $temps_estime): void
    {
        $this->temps_estime = $temps_estime;
    }

    public function setTempsPasse(float $temps_passe): void
    {
        $this->temps_passe = $temps_passe;
    }

    public function setValidationStatus(?string $validation_status): void
    {
        $this->validation_status = $validation_status;
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
     * Vérifie si le ticket est ouvert (ni terminé, ni validé, ni refusé)
     */
    public function isOpen(): bool
    {
        return !in_array($this->statut, ['termine', 'valide', 'refuse']);
    }

    /**
     * Vérifie si le ticket est en attente de validation
     */
    public function isAwaitingValidation(): bool
    {
        return $this->statut === 'a-valider';
    }

    /**
     * Calcule le temps restant estimé
     */
    public function getTempsRestant(): float
    {
        return max(0, $this->temps_estime - $this->temps_passe);
    }

    /**
     * Retourne le pourcentage de temps consommé
     */
    public function getPourcentageTemps(): float
    {
        if ($this->temps_estime <= 0) {
            return 0;
        }
        return round(($this->temps_passe / $this->temps_estime) * 100, 2);
    }

    // Méthodes liées à la base de données

    /**
     * Crée un nouveau ticket dans la base de données
     */
    public function create(PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO ticket (projet_id, titre, description, statut, priorite, type, temps_estime, temps_passe, validation_status)
                            VALUES (:projet_id, :titre, :description, :statut, :priorite, :type, :temps_estime, :temps_passe, :validation_status)");

        $stmt->execute([
            ':projet_id' => $this->projet_id,
            ':titre' => $this->titre,
            ':description' => $this->description,
            ':statut' => $this->statut,
            ':priorite' => $this->priorite,
            ':type' => $this->type,
            ':temps_estime' => $this->temps_estime,
            ':temps_passe' => $this->temps_passe,
            ':validation_status' => $this->validation_status
        ]);

        $this->id = (int) $pdo->lastInsertId();
    }

    /**
     * Met à jour le ticket dans la base de données
     */
    public function update(PDO $pdo): void
    {
        $stmt = $pdo->prepare("UPDATE ticket SET projet_id = :projet_id, titre = :titre, description = :description,
                            statut = :statut, priorite = :priorite, type = :type, temps_estime = :temps_estime,
                            temps_passe = :temps_passe, validation_status = :validation_status, date_modification = :date_modification
                            WHERE id = :id");

        $stmt->execute([
            ':id' => $this->id,
            ':projet_id' => $this->projet_id,
            ':titre' => $this->titre,
            ':description' => $this->description,
            ':statut' => $this->statut,
            ':priorite' => $this->priorite,
            ':type' => $this->type,
            ':temps_estime' => $this->temps_estime,
            ':temps_passe' => $this->temps_passe,
            ':validation_status' => $this->validation_status,
            ':date_modification' => date('Y-m-d-H:i:s')
        ]);
    }

    /**
     * Récupère un ticket par son ID
     */
    public static function getById(PDO $pdo, int $id): ?Ticket
    {
        $stmt = $pdo->prepare("SELECT * FROM ticket WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Ticket');
        $ticket = $stmt->fetch();

        return $ticket ?: null;
    }

    /**
     * Récupère tous les ticket
     * @return Ticket[]
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM ticket ORDER BY date_creation DESC");
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Ticket');
        return $stmt->fetchAll();
    }

    /**
     * Récupère tous les ticket d'un projet
     * @return Ticket[]
     */
    public static function getByProjetId(PDO $pdo, int $projetId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM ticket WHERE projet_id = :projet_id ORDER BY date_creation DESC");
        $stmt->execute([':projet_id' => $projetId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Ticket');
        return $stmt->fetchAll();
    }

    /**
     * Supprime un ticket par son ID
     */
    public static function deleteById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM ticket WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ====== Méthodes ticket_temps ======

    /**
     * Ajoute une entrée de temps sur ce ticket
     */
    public function addTemps(PDO $pdo, int $collaborateurId, string $dateTravail, float $duree, ?string $commentaire = null): int
    {
        $stmt = $pdo->prepare("INSERT INTO ticket_temps (ticket_id, collaborateur_id, date_travail, duree, commentaire)
                            VALUES (:ticket_id, :collaborateur_id, :date_travail, :duree, :commentaire)");

        $stmt->execute([
            ':ticket_id' => $this->id,
            ':collaborateur_id' => $collaborateurId,
            ':date_travail' => $dateTravail,
            ':duree' => $duree,
            ':commentaire' => $commentaire
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Récupère toutes les entrées de temps de ce ticket
     * @return array
     */
    public function getTemps(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT tt.*, u.name, u.surname
                            FROM ticket_temps tt
                            LEFT JOIN users u ON tt.collaborateur_id = u.id
                            WHERE tt.ticket_id = :ticket_id
                            ORDER BY tt.date_travail DESC");
        $stmt->execute([':ticket_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les entrées de temps d'un collaborateur sur ce ticket
     * @return array
     */
    public function getTempsByCollaborateur(PDO $pdo, int $collaborateurId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM ticket_temps
                            WHERE ticket_id = :ticket_id AND collaborateur_id = :collaborateur_id
                            ORDER BY date_travail DESC");
        $stmt->execute([
            ':ticket_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Calcule le temps total passé depuis ticket_temps et met à jour temps_passe
     */
    public function recalculerTempsPasse(PDO $pdo): void
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(duree), 0) AS total FROM ticket_temps WHERE ticket_id = :ticket_id");
        $stmt->execute([':ticket_id' => $this->id]);
        $total = (float) $stmt->fetchColumn();

        $this->temps_passe = $total;

        $update = $pdo->prepare("UPDATE ticket SET temps_passe = :temps_passe WHERE id = :id");
        $update->execute([
            ':temps_passe' => $total,
            ':id' => $this->id
        ]);
    }

    /**
     * Met à jour une entrée de temps
     */
    public static function updateTemps(PDO $pdo, int $tempsId, string $dateTravail, float $duree, ?string $commentaire = null): bool
    {
        $stmt = $pdo->prepare("UPDATE ticket_temps SET date_travail = :date_travail, duree = :duree, commentaire = :commentaire
                            WHERE id = :id");
        $stmt->execute([
            ':id' => $tempsId,
            ':date_travail' => $dateTravail,
            ':duree' => $duree,
            ':commentaire' => $commentaire
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime une entrée de temps
     */
    public static function deleteTemps(PDO $pdo, int $tempsId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM ticket_temps WHERE id = :id");
        $stmt->execute([':id' => $tempsId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère toutes les entrées de temps d'un collaborateur (tous ticket confondus)
     * @return array
     */
    public static function getAllTempsByCollaborateur(PDO $pdo, int $collaborateurId): array
    {
        $stmt = $pdo->prepare("SELECT tt.*, t.titre AS ticket_titre
                            FROM ticket_temps tt
                            LEFT JOIN ticket t ON tt.ticket_id = t.id
                            WHERE tt.collaborateur_id = :collaborateur_id
                            ORDER BY tt.date_travail DESC");
        $stmt->execute([':collaborateur_id' => $collaborateurId]);
        return $stmt->fetchAll();
    }

    // ====== Méthodes ticket_commentaire ======

    /**
     * Ajoute un commentaire sur ce ticket
     */
    public function addCommentaire(PDO $pdo, int $auteurId, string $contenu): int
    {
        $stmt = $pdo->prepare("INSERT INTO ticket_commentaire (ticket_id, auteur_id, contenu)
                            VALUES (:ticket_id, :auteur_id, :contenu)");

        $stmt->execute([
            ':ticket_id' => $this->id,
            ':auteur_id' => $auteurId,
            ':contenu' => $contenu
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Récupère tous les commentaires de ce ticket (avec nom/prénom de l'auteur)
     * @return array
     */
    public function getCommentaires(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT tc.*, u.name, u.surname
                            FROM ticket_commentaire tc
                            LEFT JOIN users u ON tc.auteur_id = u.id
                            WHERE tc.ticket_id = :ticket_id
                            ORDER BY tc.date_creation ASC");
        $stmt->execute([':ticket_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Met à jour un commentaire
     */
    public static function updateCommentaire(PDO $pdo, int $commentaireId, string $contenu): bool
    {
        $stmt = $pdo->prepare("UPDATE ticket_commentaire SET contenu = :contenu WHERE id = :id");
        $stmt->execute([
            ':id' => $commentaireId,
            ':contenu' => $contenu
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime un commentaire
     */
    public static function deleteCommentaire(PDO $pdo, int $commentaireId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM ticket_commentaire WHERE id = :id");
        $stmt->execute([':id' => $commentaireId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Compte le nombre de commentaires sur ce ticket
     */
    public function countCommentaires(PDO $pdo): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket_commentaire WHERE ticket_id = :ticket_id");
        $stmt->execute([':ticket_id' => $this->id]);
        return (int) $stmt->fetchColumn();
    }

    // ====== Méthodes ticket_collaborateur ======

    /**
     * Assigne un collaborateur à ce ticket
     */
    public function addCollaborateur(PDO $pdo, int $collaborateurId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO ticket_collaborateur (ticket_id, collaborateur_id)
                            VALUES (:ticket_id, :collaborateur_id)");
        $stmt->execute([
            ':ticket_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
    }

    /**
     * Retire un collaborateur de ce ticket
     */
    public function removeCollaborateur(PDO $pdo, int $collaborateurId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM ticket_collaborateur
                            WHERE ticket_id = :ticket_id AND collaborateur_id = :collaborateur_id");
        $stmt->execute([
            ':ticket_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les collaborateurs assignés à ce ticket
     * @return array
     */
    public function getCollaborateurs(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT u.*
                            FROM ticket_collaborateur tc
                            INNER JOIN users u ON tc.collaborateur_id = u.id
                            WHERE tc.ticket_id = :ticket_id
                            ORDER BY u.name ASC");
        $stmt->execute([':ticket_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un collaborateur est assigné à ce ticket
     */
    public function hasCollaborateur(PDO $pdo, int $collaborateurId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM ticket_collaborateur
                            WHERE ticket_id = :ticket_id AND collaborateur_id = :collaborateur_id");
        $stmt->execute([
            ':ticket_id' => $this->id,
            ':collaborateur_id' => $collaborateurId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les ticket assignés à un collaborateur
     * @return Ticket[]
     */
    public static function getByCollaborateur(PDO $pdo, int $collaborateurId): array
    {
        $stmt = $pdo->prepare("SELECT t.*
                            FROM ticket t
                            INNER JOIN ticket_collaborateur tc ON t.id = tc.ticket_id
                            WHERE tc.collaborateur_id = :collaborateur_id
                            ORDER BY t.date_creation DESC");
        $stmt->execute([':collaborateur_id' => $collaborateurId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Ticket');
        return $stmt->fetchAll();
    }
}
