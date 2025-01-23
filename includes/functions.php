<?php
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validatePassword($password) {
    // Au moins 8 caractères, une majuscule, une minuscule et un chiffre
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password);
}

function uploadImage($file, $destination) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Fichier trop volumineux'];
    }

    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $destination . $fileName;

    $target_dir = 'assets/images/annonces/';
    $target_file = $target_dir . basename($_FILES['image']['name']);
    
    // Crée le dossier s'il n'existe pas
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Crée le dossier avec les permissions appropriées
    }
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        echo "Le fichier a été téléchargé avec succès.";
    } else {
        echo "Erreur lors du téléchargement du fichier.";
    }

    return ['success' => false, 'message' => 'Erreur lors du téléchargement'];
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function getCategories() {
    return [
        'sports' => 'Sports et Loisirs',
        'bureau' => 'Fournitures de Bureau',
        'electronique' => 'Électronique',
        'maison' => 'Maison et Jardin',
        'mode' => 'Mode et Accessoires',
        'auto' => 'Auto-Moto',
        'multimedia' => 'Multimédia',
        'services' => 'Services'
    ];
}
?>
