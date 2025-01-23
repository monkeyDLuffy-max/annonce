<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header('Location: mes-annonces.php');
    exit();
}

$annonce_id = intval($_GET['id']);

// Vérifier si l'annonce appartient à l'utilisateur
$stmt = $conn->prepare("SELECT image_path FROM annonces WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $annonce_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $annonce = $result->fetch_assoc();
    
    // Supprimer l'image si elle existe
    if (!empty($annonce['image_path'])) {
        $image_path = 'assets/images/annonces/' . $annonce['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Supprimer l'annonce
    $stmt = $conn->prepare("DELETE FROM annonces WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $annonce_id, $_SESSION['user_id']);
    $stmt->execute();
}

header('Location: mes-annonces.php');
exit();
?>
