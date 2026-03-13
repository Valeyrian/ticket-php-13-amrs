<?php
// On définit les paramètres de connexion
$host = 'localhost';
$db   = 'vector';
$user = 'root';
$pass = 'root'; 
$charset = 'utf8mb4';

// Le DSN (Data Source Name) : la "carte d'identité" de la BDD
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Options PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
     // Si la connexion échoue, on arrête tout et on affiche l'erreur
     die("Erreur de connexion : " . $e->getMessage());
}