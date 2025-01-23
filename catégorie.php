<?php
// Fichier pour gérer les catégories
require_once 'config/database.php';

$sql = "CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    slug VARCHAR(255) NOT NULL UNIQUE,
    popularity INT DEFAULT 0
) ";

if ($conn->query($sql) === TRUE) {
    echo "Table 'categories' créée avec succès.";
} else {
    echo "Erreur lors de la création de la table: " . $conn->error;
}

$conn->close();
?>

<script>
    alert('test');
</script>