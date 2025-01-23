<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$categories = getCategories();
$search_query = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : null;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : null;
$sort = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'date_desc';

// Construire la requête SQL
$sql = "SELECT a.*, u.username 
        FROM annonces a 
        JOIN users u ON a.user_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND a.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($price_min !== null) {
    $sql .= " AND a.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max !== null) {
    $sql .= " AND a.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

// Tri
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY a.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY a.price DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY a.created_at ASC";
        break;
    case 'date_desc':
    default:
        $sql .= " ORDER BY a.created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$annonces = [];
while ($row = $result->fetch_assoc()) {
    $annonces[] = $row;
}

include 'includes/header.php';
?>

<div class="row">
    <!-- Filtres -->
    <div class="col-md-3">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Filtres</h5>
                <form action="" method="GET" id="filter-form">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Catégorie</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($category == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prix</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" name="price_min" placeholder="Min"
                                       value="<?php echo $price_min !== null ? $price_min : ''; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" name="price_max" placeholder="Max"
                                       value="<?php echo $price_max !== null ? $price_max : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sort" class="form-label">Trier par</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="date_desc" <?php echo ($sort == 'date_desc') ? 'selected' : ''; ?>>Plus récentes</option>
                            <option value="date_asc" <?php echo ($sort == 'date_asc') ? 'selected' : ''; ?>>Plus anciennes</option>
                            <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Prix décroissant</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Résultats -->
    <div class="col-md-9">
        <?php if (!empty($search_query)): ?>
            <h2>Résultats pour "<?php echo htmlspecialchars($search_query); ?>"</h2>
        <?php endif; ?>

        <div class="mb-3">
            <span class="text-muted"><?php echo count($annonces); ?> annonce(s) trouvée(s)</span>
        </div>

        <div class="row">
            <?php if (empty($annonces)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Aucune annonce ne correspond à votre recherche.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($annonces as $annonce): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 <?php echo $annonce['is_premium'] ? 'border-primary' : ''; ?>">
                            <?php if ($annonce['is_premium']): ?>
                                <div class="badge bg-primary position-absolute top-0 end-0 m-2">Premium</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($annonce['image_path'])): ?>
                                <img src="assets/images/annonces/<?php echo htmlspecialchars($annonce['image_path']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($annonce['title']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="assets/images/default-annonce.jpg" class="card-img-top" 
                                     alt="Image par défaut" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($annonce['title']); ?></h5>
                                <p class="card-text"><?php echo substr(htmlspecialchars($annonce['description']), 0, 100) . '...'; ?></p>
                                <p class="card-text">
                                    <strong class="text-primary"><?php echo formatPrice($annonce['price']); ?></strong>
                                </p>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    Par <?php echo htmlspecialchars($annonce['username']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($annonce['created_at'])); ?>
                                </small>
                                <a href="annonce.php?id=<?php echo $annonce['id']; ?>" 
                                   class="btn btn-sm btn-primary float-end">Voir l'annonce</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
