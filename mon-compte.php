<?php
session_start();
require_once 'config/database.php';

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = :id"; // Utilisation de :id pour le binding
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $errorInfo = $conn->errorInfo();
    die("Erreur lors de la préparation de la requête : " . $errorInfo[2]);
}

$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur introuvable.");
}

include 'includes/header.php';
?>

<div class="container">
    <h2>Mon Compte</h2>
    <p>Bienvenue, <?php echo htmlspecialchars($user['username']); ?>!</p>
    <h3>Informations personnelles</h3>
    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
    <h3>Modifier mes informations</h3>
    <form method="POST" action="update-account.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
