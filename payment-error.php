<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error_message = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : 'Une erreur est survenue lors du paiement.';
unset($_SESSION['payment_error']);

$annonce_id = isset($_GET['annonce_id']) ? intval($_GET['annonce_id']) : 0;

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                </div>
                
                <h2 class="card-title mb-4">Erreur de paiement</h2>
                
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                
                <p>Le paiement n'a pas pu être traité. Aucun montant n'a été débité de votre compte.</p>
                
                <div class="mt-4">
                    <a href="premium-payment.php?annonce_id=<?php echo $annonce_id; ?>" class="btn btn-primary me-2">
                        Réessayer
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
