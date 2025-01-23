<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

$errors = [];
$success = false;
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $price = floatval(str_replace(',', '.', $_POST['price']));
    $category = cleanInput($_POST['category']);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;

    // Validation
    if (empty($title)) {
        $errors[] = "Le titre est requis";
    }
    if (empty($description)) {
        $errors[] = "La description est requise";
    }
    if ($price < 0) {
        $errors[] = "Le prix ne peut pas être négatif";
    }
    if (!array_key_exists($category, $categories)) {
        $errors[] = "Catégorie invalide";
    }

    // Traitement de l'image
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_result = uploadImage($_FILES['image'], 'assets/images/annonces/');
        if ($upload_result['success']) {
            $image_path = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // Si pas d'erreurs, enregistrer l'annonce
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO annonces (user_id, title, description, price, category, is_premium, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $price, $category, $is_premium, $image_path]);

        if ($stmt) {
            $success = true;
            header("Location: mes-annonces.php");
            exit();
        } else {
            $errors[] = "Erreur lors de la création de l'annonce";
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Déposer une annonce</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre de l'annonce</label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Catégorie</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Choisir une catégorie</option>
                                <?php foreach ($categories as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Photo de l'annonce</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Format accepté : JPG, PNG, GIF. Taille maximale : 5 Mo</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_premium" name="is_premium"
                               <?php echo isset($_POST['is_premium']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_premium">
                            Annonce Premium (mise en avant, +5€)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Publier l'annonce</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>