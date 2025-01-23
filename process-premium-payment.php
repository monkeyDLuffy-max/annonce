<?php
session_start();
require_once 'config/database.php';
require_once 'config/stripe_config.php';
require_once 'includes/functions.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

if (!isset($_GET['annonce_id']) || !isset($_GET['payment_intent'])) {
    header('Location: index.php');
    exit();
}

$annonce_id = intval($_GET['annonce_id']);
$payment_intent_id = $_GET['payment_intent'];

try {
    // Récupérer les détails du paiement depuis Stripe
    $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    
    if ($payment_intent->status === 'succeeded') {
        // Début de la transaction
        $conn->begin_transaction();
        
        try {
            // Mettre à jour le statut premium de l'annonce
            $stmt = $conn->prepare("
                UPDATE annonces 
                SET is_premium = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $annonce_id, $_SESSION['user_id']);
            $stmt->execute();

            // Enregistrer le paiement
            $stmt = $conn->prepare("
                INSERT INTO payments (user_id, annonce_id, amount, stripe_payment_id, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $amount = PREMIUM_PRICE;
            $status = 'completed';
            $stmt->bind_param("iidss", $_SESSION['user_id'], $annonce_id, $amount, $payment_intent_id, $status);
            $stmt->execute();

            // Valider la transaction
            $conn->commit();

            // Rediriger vers la page de succès
            $_SESSION['payment_success'] = true;
            header('Location: payment-success.php?annonce_id=' . $annonce_id);
            exit();

        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception('Le paiement n\'a pas été validé.');
    }
} catch (Exception $e) {
    $_SESSION['payment_error'] = $e->getMessage();
    header('Location: payment-error.php?annonce_id=' . $annonce_id);
    exit();
}
?>
