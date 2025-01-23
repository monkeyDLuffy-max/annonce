<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

$errors = [];
$success = false;
$categories = getCategories();

// Vérifier si l'ID de l'annonce est fourni
if (!isset($_GET['id'])) {
    header('Location: mes-annonces.php');
    exit();
}

$annonce_id = intval($_GET['id']);

// Vérifier si l'annonce appartient à l'utilisateur
$stmt = $conn->prepare("SELECT * FROM annonces WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $annonce_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: mes-annonces.php');
    exit();
}

$annonce = $result->fetch_assoc();

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
    $image_path = $annonce['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_result = uploadImage($_FILES['image'], 'assets/images/annonces/');
        if ($upload_result['success']) {
            // Supprimer l'ancienne image si elle existe
            if (!empty($image_path) && file_exists('assets/images/annonces/' . $image_path)) {
                unlink('assets/images/annonces/' . $image_path);
            }
            $image_path = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // Si pas d'erreurs, mettre à jour l'annonce
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE annonces SET title = ?, description = ?, price = ?, category = ?, is_premium = ?, image_path = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssdsisii", $title, $description, $price, $category, $is_premium, $image_path, $annonce_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = true;
            header("Location: mes-annonces.php");
            exit();
        } else {
            $errors[] = "Erreur lors de la modification de l'annonce";
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Modifier l'annonce</h2>

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
                               value="<?php echo htmlspecialchars($annonce['title']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($annonce['description']); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required
                                   value="<?php echo htmlspecialchars($annonce['price']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Catégorie</label>
                            <select class="form-select" id="category" name="category" required>
                                <?php foreach ($categories as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($annonce['category'] == $key) ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Photo de l'annonce</label>
                        <?php if (!empty($annonce['image_path'])): ?>
                            <div class="mb-2">
                                <img src="assets/images/annonces/<?php echo htmlspecialchars($annonce['image_path']); ?>" 
                                     alt="Image actuelle" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Format accepté : JPG, PNG, GIF. Taille maximale : 5 Mo</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_premium" name="is_premium"
                               <?php echo $annonce['is_premium'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_premium">
                            Annonce Premium (mise en avant, +5€)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="mes-annonces.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
