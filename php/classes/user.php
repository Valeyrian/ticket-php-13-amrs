<?php

class User
{
    private ?int $id;
    private string $name;
    private string $surname;
    private string $email;
    private string $password;
    private string $role;
    private ?string $creationDate;
    private ?string $state;
    private ?string $company;

    public function __construct(
        ?int $id = null,
        string $name = '',
        string $surname = '',
        string $email = '',
        string $password = '',
        string $role = 'client',
        ?string $creationDate = null,
        string $state = '',
        string $company = ''


    ) {
        $this->id = $id;
        $this->name = $name;
        $this->surname = $surname;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->creationDate = $creationDate;
        $this->state = $state;
        $this->company = $company;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function setRole(string $role): void
    {
        $allowedRoles = ['client', 'admin', 'collaborator'];
        if (!in_array($role, $allowedRoles)) {
            throw new InvalidArgumentException("Rôle invalide : $role");
        }
        $this->role = $role;
    }

    public function setCreationDate(string $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    // Méthodes métier
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCollaborateur(): bool
    {
        return $this->role === 'collaborateur';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }


    //methodes link a la base de données

    //creation d'un nouvel utilisateur dans la base de données
    public function registerNewUser(PDO $pdo): void
    {
        $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, password, role, creationDate, state, company) 
                            VALUES (:name, :surname, :email, :password, :role, :creationDate, :state, :company)");

        $stmt->execute([
            ':name' => $this->name,
            ':surname' => $this->surname,
            ':email' => $this->email,
            ':password' => $this->password,
            ':role' => $this->role,
            ':creationDate' => $this->creationDate ?? '',
            ':state' => $this->state ?? 'active',
            ':company' => $this->company ?? ''
        ]);

        // On récupère l'ID généré par MySQL pour mettre à jour l'objet
        $this->id = (int) $pdo->lastInsertId();
    }

    public static function login(PDO $pdo, string $email, string $password): ?User
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);

        // FETCH_PROPS_LATE : les propriétés sont assignées APRÈS le constructeur
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        $user = $stmt->fetch();

        // 2. Si l'utilisateur existe, on vérifie le mot de passe
        if ($user && password_verify($password, $user->password)) {
            // Le mot de passe correspond ! On retourne l'objet utilisateur
            return $user;
        }
        // Identifiants incorrects
        return null;
    }

    public static function checkEmailExists(PDO $pdo, string $email): bool
    {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Récupère tous les utilisateurs de la base de données
     * @return User[]
     */

    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY name ASC");
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        return $stmt->fetchAll();
    }


    /**
     * Retourne les initiales (ex: "JD" pour Jean Dupont)
     */
    public function getInitials(): string
    {
        $first = mb_strtoupper(mb_substr($this->name, 0, 1));
        $last = mb_strtoupper(mb_substr($this->surname, 0, 1));
        return $first . $last;
    }

    /**
     * Récupère un utilisateur par son ID
     */
    public static function getById(PDO $pdo, int $id): ?User
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'User');
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Met à jour un utilisateur dans la base de données
     */
    public function update(PDO $pdo): void
    {
        $stmt = $pdo->prepare("UPDATE users SET name = :name, surname = :surname, email = :email, 
                               role = :role, state = :state, company = :company WHERE id = :id");
        $stmt->execute([
            ':name' => $this->name,
            ':surname' => $this->surname,
            ':email' => $this->email,
            ':role' => $this->role,
            ':state' => $this->state,
            ':company' => $this->company ?? '',
            ':id' => $this->id
        ]);
    }

    /**
     * Supprime un utilisateur par son ID
     */
    public static function deleteById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function isAtLeastCollaboratorById(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && in_array($result['role'], ['admin', 'collaborateur']);

    }
}