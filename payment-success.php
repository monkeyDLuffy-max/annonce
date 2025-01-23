<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Rediriger si pas de succès de paiement
if (!isset($_SESSION['payment_success'])) {
    header('Location: index.php');
    exit();
}

// Supprimer le message de succès pour éviter les rechargements
unset($_SESSION['payment_success']);

$annonce_id = isset($_GET['annonce_id']) ? intval($_GET['annonce_id']) : 0;

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                
                <h2 class="card-title mb-4">Paiement réussi !</h2>
                
                <p class="lead">Votre annonce est maintenant en mode premium.</p>
                
                <p>Elle bénéficie désormais d'une meilleure visibilité et apparaîtra en priorité dans les résultats de recherche.</p>
                
                <div class="mt-4">
                    <a href="annonce.php?id=<?php echo $annonce_id; ?>" class="btn btn-primary me-2">
                        Voir mon annonce
                    </a>
                    <a href="mes-annonces.php" class="btn btn-secondary">
                        Mes annonces
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
