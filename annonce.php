<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$annonce_id = intval($_GET['id']);
$categories = getCategories();

// Récupérer les détails de l'annonce
$stmt = $conn->prepare("
    SELECT a.*, u.username, u.email 
    FROM annonces a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ?
");
$stmt->bind_param("i", $annonce_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$annonce = $result->fetch_assoc();

// Récupérer d'autres annonces du même vendeur
$stmt = $conn->prepare("
    SELECT * FROM annonces 
    WHERE user_id = ? AND id != ? 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->bind_param("ii", $annonce['user_id'], $annonce_id);
$stmt->execute();
$autres_annonces = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Gérer l'envoi de message
$message_sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = "annonce.php?id=" . $annonce_id;
        header('Location: login.php');
        exit();
    }

    $message = cleanInput($_POST['message']);
    
    if (empty($message)) {
        $errors[] = "Le message ne peut pas être vide";
    } else {
        // Insérer le message dans la base de données
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, annonce_id, message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $_SESSION['user_id'], $annonce['user_id'], $annonce_id, $message);
        
        if ($stmt->execute()) {
            $message_sent = true;
        } else {
            $errors[] = "Erreur lors de l'envoi du message";
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <!-- Annonce -->
        <div class="card shadow-sm mb-4">
            <?php if ($annonce['is_premium']): ?>
                <div class="badge bg-primary position-absolute top-0 end-0 m-3">Premium</div>
            <?php endif; ?>

            <?php if (!empty($annonce['image_path'])): ?>
                <img src="assets/images/annonces/<?php echo htmlspecialchars($annonce['image_path']); ?>" 
                     class="card-img-top" alt="<?php echo htmlspecialchars($annonce['title']); ?>"
                     style="max-height: 400px; object-fit: contain;">
            <?php endif; ?>

            <div class="card-body">
                <h1 class="card-title h2"><?php echo htmlspecialchars($annonce['title']); ?></h1>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 text-primary mb-0"><?php echo formatPrice($annonce['price']); ?></h2>
                    <span class="badge bg-secondary"><?php echo $categories[$annonce['category']]; ?></span>
                </div>

                <p class="card-text"><?php echo nl2br(htmlspecialchars($annonce['description'])); ?></p>

                <div class="mt-4">
                    <h3 class="h5">Informations sur le vendeur</h3>
                    <p>
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($annonce['username']); ?><br>
                        <i class="bi bi-calendar"></i> Membre depuis : <?php echo date('d/m/Y', strtotime($annonce['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire de contact -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="card-title h4">Contacter le vendeur</h3>

                <?php if ($message_sent): ?>
                    <div class="alert alert-success">
                        Votre message a été envoyé avec succès !
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['user_id'] != $annonce['user_id']): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="message" class="form-label">Votre message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer le message</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            C'est votre annonce. Vous pouvez la <a href="modifier-annonce.php?id=<?php echo $annonce_id; ?>">modifier</a> 
                            ou la <a href="supprimer-annonce.php?id=<?php echo $annonce_id; ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')">supprimer</a>.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Vous devez être <a href="login.php">connecté</a> pour contacter le vendeur.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Autres annonces du vendeur -->
        <?php if (!empty($autres_annonces)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="card-title h4">Autres annonces de <?php echo htmlspecialchars($annonce['username']); ?></h3>
                    
                    <?php foreach ($autres_annonces as $autre_annonce): ?>
                        <div class="card mb-2">
                            <?php if (!empty($autre_annonce['image_path'])): ?>
                                <img src="assets/images/annonces/<?php echo htmlspecialchars($autre_annonce['image_path']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($autre_annonce['title']); ?>"
                                     style="height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title h6"><?php echo htmlspecialchars($autre_annonce['title']); ?></h5>
                                <p class="card-text">
                                    <strong class="text-primary"><?php echo formatPrice($autre_annonce['price']); ?></strong>
                                </p>
                                <a href="annonce.php?id=<?php echo $autre_annonce['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">Voir l'annonce</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Annonces similaires -->
        <?php
        $stmt = $conn->prepare("
            SELECT a.*, u.username 
            FROM annonces a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.category = ? AND a.id != ? 
            ORDER BY a.created_at DESC 
            LIMIT 3
        ");
        $stmt->bind_param("si", $annonce['category'], $annonce_id);
        $stmt->execute();
        $annonces_similaires = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <?php if (!empty($annonces_similaires)): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title h4">Annonces similaires</h3>
                    
                    <?php foreach ($annonces_similaires as $annonce_similaire): ?>
                        <div class="card mb-2">
                            <?php if (!empty($annonce_similaire['image_path'])): ?>
                                <img src="assets/images/annonces/<?php echo htmlspecialchars($annonce_similaire['image_path']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($annonce_similaire['title']); ?>"
                                     style="height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title h6"><?php echo htmlspecialchars($annonce_similaire['title']); ?></h5>
                                <p class="card-text">
                                    <strong class="text-primary"><?php echo formatPrice($annonce_similaire['price']); ?></strong><br>
                                    <small class="text-muted">Par <?php echo htmlspecialchars($annonce_similaire['username']); ?></small>
                                </p>
                                <a href="annonce.php?id=<?php echo $annonce_similaire['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">Voir l'annonce</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>