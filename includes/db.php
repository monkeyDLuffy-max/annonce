<?php
// db.php

$host = 'localhost'; // Adresse du serveur MySQL
$dbname = 'specialannonces'; // Nom de la base de données
$user = 'root'; // Nom d'utilisateur MySQL
$password = ''; // Mot de passe MySQL (laisser vide si vous n'avez pas de mot de passe)

try {
    // Créer une connexion PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    // Définir le mode d'erreur PDO sur Exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage());
}
?>