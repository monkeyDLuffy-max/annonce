<?php
session_start();
require_once 'config/database.php';
include 'includes/functions.php';

$success_message = ''; // Initialize success message
$error_message = ''; // Initialize error message

// Récupération des annonces récentes
$sql = "SELECT a.*, u.username 
        FROM annonces a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT 12";
$result = $conn->query($sql);
$annonces = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $annonces[] = $row;
        }
    }
} else {
    $error_message = "Erreur lors de l'exécution de la requête : " . $conn->error;
}

// Gérer l'ajout de catégories
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $new_category = cleanInput($_POST['new_category']);
    
    if (!empty($new_category)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $new_category);
        if ($stmt->execute()) {
            $success_message = "Catégorie ajoutée avec succès.";
        } else {
            $error_message = "Erreur lors de l'ajout de la catégorie.";
        }
        $stmt->close();
    } else {
        $error_message = "Le nom de la catégorie ne peut pas être vide.";
    }
}

// Gérer la suppression de catégories
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        $success_message = "Catégorie supprimée avec succès.";
    } else {
        $error_message = "Erreur lors de la suppression de la catégorie.";
    }
    $stmt->close();
}

$sql_categories = "SELECT * FROM categories";
$result_categories = $conn->query($sql_categories);
$categories = [];
if ($result_categories) {
    if ($result_categories->num_rows > 0) {
        while($row = $result_categories->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} else {
    $error_message = "Erreur lors de l'exécution de la requête : " . $conn->error;
}

include 'includes/header.php';
?>

<div class="hero-section text-center py-5 bg-light">
    <h1>Bienvenue sur SpécialAnnonces</h1>
    <p class="lead">Trouvez les meilleures annonces spécialisées</p>
    <div class="search-box mt-4">
        <form action="recherche.php" method="GET" class="d-flex justify-content-center">
            <input type="text" name="q" class="form-control me-2" style="max-width: 500px;" placeholder="Que recherchez-vous ?">
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </form>
    </div>
</div>

<div class="featured-categories my-5">
    <h2 class="text-center mb-4">Catégories populaires</h2>
    <div class="row">
        <?php foreach ($categories as $category): ?>
        <div class="col-md-4 mb-3">
            <div class="category-card text-center p-4 bg-light rounded">
                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                <a href="#" onclick="openModal('modal-<?php echo $category['id']; ?>')" class="btn btn-outline-primary">Voir les annonces</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="recent-listings">
    <h2 class="text-center mb-4">Annonces récentes</h2>
    <div class="row">
        <?php foreach ($annonces as $annonce): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <img src="assets/images/default-annonce.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($annonce['title']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($annonce['title']); ?></h5>
                    <p class="card-text"><?php echo substr(htmlspecialchars($annonce['description']), 0, 100) . '...'; ?></p>
                    <p class="card-text"><strong><?php echo number_format($annonce['price'], 2, ',', ' '); ?> €</strong></p>
                </div>
                <div class="card-footer">
                    <small class="text-muted">Par <?php echo htmlspecialchars($annonce['username']); ?></small>
                    <a href="annonce.php?id=<?php echo $annonce['id']; ?>" class="btn btn-sm btn-primary float-end">Voir l'annonce</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container my-5">
    <h2>Gérer les catégories</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="new_category" class="form-label">Ajouter une nouvelle catégorie</label>
            <input type="text" class="form-control" id="new_category" name="new_category" required>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">Ajouter</button>
    </form>

    <h3>Catégories existantes</h3>
    <ul class="list-group">
        <?php foreach ($categories as $category): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($category['name']); ?>
                <a href="?delete_category=<?php echo $category['id']; ?>" class="btn btn-danger btn-sm">Supprimer</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
