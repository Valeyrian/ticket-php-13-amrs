<?php

class Client
{
    private ?int $id;
    private string $nom;
    private ?string $adresse;
    private ?string $code_postal;
    private ?string $ville;
    private ?string $pays;
    private string $statut;
    private ?string $date_creation;
    private ?string $date_modification;

    public function __construct(
        ?int $id = null,
        string $nom = '',
        ?string $adresse = null,
        ?string $code_postal = null,
        ?string $ville = null,
        ?string $pays = 'France',
        string $statut = 'actif',
        ?string $date_creation = null,
        ?string $date_modification = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->adresse = $adresse;
        $this->code_postal = $code_postal;
        $this->ville = $ville;
        $this->pays = $pays;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function getPays(): ?string
    {
        return $this->pays;
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

    public function setAdresse(?string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function setCodePostal(?string $code_postal): void
    {
        $this->code_postal = $code_postal;
    }

    public function setVille(?string $ville): void
    {
        $this->ville = $ville;
    }

    public function setPays(?string $pays): void
    {
        $this->pays = $pays;
    }

    public function setStatut(string $statut): void
    {
        $allowedStatuts = ['actif', 'inactif'];
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
     * Vérifie si le client est actif
     */
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifie si le client est inactif
     */
    public function isInactif(): bool
    {
        return $this->statut === 'inactif';
    }

    // Méthodes liées à la base de données

    /**
     * Crée un nouveau client dans la base de données
     */
    public function create(PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO client (nom, adresse, code_postal, ville, pays, statut)
                            VALUES (:nom, :adresse, :code_postal, :ville, :pays, :statut)");

        $stmt->execute([
            ':nom' => $this->nom,
            ':adresse' => $this->adresse,
            ':code_postal' => $this->code_postal,
            ':ville' => $this->ville,
            ':pays' => $this->pays,
            ':statut' => $this->statut
        ]);

        $this->id = (int) $pdo->lastInsertId();
    }

    /**
     * Met à jour le client dans la base de données
     */
    public function update(PDO $pdo): void
    {
        $stmt = $pdo->prepare("UPDATE client SET nom = :nom, adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays, statut = :statut WHERE id = :id");

        $stmt->execute([
            ':id' => $this->id,
            ':nom' => $this->nom,
            ':adresse' => $this->adresse,
            ':code_postal' => $this->code_postal,
            ':ville' => $this->ville,
            ':pays' => $this->pays,
            ':statut' => $this->statut
        ]);
    }

    /**
     * Récupère un client par son ID
     */
    public static function getById(PDO $pdo, int $id): ?Client
    {
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        $client = $stmt->fetch();

        return $client ?: null;
    }

    /**
     * Récupère tous les clients
     * @return Client[]
     */
    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM client ORDER BY date_creation DESC");
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        return $stmt->fetchAll();
    }

    /**
     * Récupère les clients par statut
     * @return Client[]
     */
    public static function getByStatut(PDO $pdo, string $statut): array
    {
        $stmt = $pdo->prepare("SELECT * FROM client WHERE statut = :statut ORDER BY date_creation DESC");
        $stmt->execute([':statut' => $statut]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        return $stmt->fetchAll();
    }

    /**
     * Supprime un client par son ID
     */
    public static function deleteById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM client WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ====== Méthodes client_contact_principal ======

    /**
     * Définit un utilisateur comme contact principal pour ce client
     */
    public function setContactPrincipal(PDO $pdo, int $utilisateurId): void
    {
        $stmt = $pdo->prepare("REPLACE INTO client_contact_principal (client_id, utilisateur_id)
                            VALUES (:client_id, :utilisateur_id)");
        $stmt->execute([
            ':client_id' => $this->id,
            ':utilisateur_id' => $utilisateurId
        ]);
    }

    /**
     * Retire le contact principal de ce client
     */
    public function removeContactPrincipal(PDO $pdo): bool
    {
        $stmt = $pdo->prepare("DELETE FROM client_contact_principal WHERE client_id = :client_id");
        $stmt->execute([':client_id' => $this->id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère le contact principal de ce client
     * @return array|null
     */
    public function getContactPrincipal(PDO $pdo): ?array
    {
        $stmt = $pdo->prepare("SELECT u.*
                            FROM client_contact_principal ccp
                            INNER JOIN users u ON ccp.utilisateur_id = u.id
                            WHERE ccp.client_id = :client_id");
        $stmt->execute([':client_id' => $this->id]);
        $contact = $stmt->fetch();
        return $contact ?: null;
    }

    /**
     * Vérifie si un utilisateur est contact principal de ce client
     */
    public function hasContactPrincipal(PDO $pdo, int $utilisateurId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM client_contact_principal
                            WHERE client_id = :client_id AND utilisateur_id = :utilisateur_id");
        $stmt->execute([
            ':client_id' => $this->id,
            ':utilisateur_id' => $utilisateurId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les clients dont un utilisateur est contact principal
     * @return Client[]
     */
    public static function getClientsByContactPrincipal(PDO $pdo, int $utilisateurId): array
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM client c
                            INNER JOIN client_contact_principal ccp ON c.id = ccp.client_id
                            WHERE ccp.utilisateur_id = :utilisateur_id
                            ORDER BY c.date_creation DESC");
        $stmt->execute([':utilisateur_id' => $utilisateurId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        return $stmt->fetchAll();
    }

    // ====== Méthodes client_utilisateur ======

    /**
     * Associe un utilisateur à ce client
     */
    public function addUtilisateur(PDO $pdo, int $utilisateurId): void
    {
        $stmt = $pdo->prepare("INSERT IGNORE INTO client_utilisateur (client_id, utilisateur_id)
                            VALUES (:client_id, :utilisateur_id)");
        $stmt->execute([
            ':client_id' => $this->id,
            ':utilisateur_id' => $utilisateurId
        ]);
    }

    /**
     * Retire un utilisateur de ce client
     */
    public function removeUtilisateur(PDO $pdo, int $utilisateurId): bool
    {
        $stmt = $pdo->prepare("DELETE FROM client_utilisateur
                            WHERE client_id = :client_id AND utilisateur_id = :utilisateur_id");
        $stmt->execute([
            ':client_id' => $this->id,
            ':utilisateur_id' => $utilisateurId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les utilisateurs associés à ce client
     * @return array
     */
    public function getUtilisateurs(PDO $pdo): array
    {
        $stmt = $pdo->prepare("SELECT u.*
                            FROM client_utilisateur cu
                            INNER JOIN users u ON cu.utilisateur_id = u.id
                            WHERE cu.client_id = :client_id
                            ORDER BY u.name ASC");
        $stmt->execute([':client_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un utilisateur est associé à ce client
     */
    public function hasUtilisateur(PDO $pdo, int $utilisateurId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM client_utilisateur
                            WHERE client_id = :client_id AND utilisateur_id = :utilisateur_id");
        $stmt->execute([
            ':client_id' => $this->id,
            ':utilisateur_id' => $utilisateurId
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les clients d'un utilisateur
     * @return Client[]
     */
    public static function getClientsByUtilisateur(PDO $pdo, int $utilisateurId): array
    {
        $stmt = $pdo->prepare("SELECT c.*
                            FROM client c
                            INNER JOIN client_utilisateur cu ON c.id = cu.client_id
                            WHERE cu.utilisateur_id = :utilisateur_id
                            ORDER BY c.date_creation DESC");
        $stmt->execute([':utilisateur_id' => $utilisateurId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Client');
        return $stmt->fetchAll();
    }
}
