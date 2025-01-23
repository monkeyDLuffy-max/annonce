<?php
session_start();
require_once 'config/database.php';
require_once 'config/stripe_config.php';
require_once 'includes/functions.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

if (!isset($_GET['annonce_id'])) {
    header('Location: index.php');
    exit();
}

$annonce_id = intval($_GET['annonce_id']);

// Vérifier si l'annonce appartient à l'utilisateur
$stmt = $conn->prepare("SELECT * FROM annonces WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $annonce_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$annonce = $result->fetch_assoc();

// Vérifier si l'annonce n'est pas déjà premium
if ($annonce['is_premium']) {
    header('Location: mes-annonces.php');
    exit();
}

$error = null;
$payment_intent = null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Créer l'intention de paiement Stripe
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => PREMIUM_PRICE * 100, // Stripe utilise les centimes
            'currency' => 'eur',
            'metadata' => [
                'annonce_id' => $annonce_id,
                'user_id' => $_SESSION['user_id']
            ]
        ]);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Passer votre annonce en premium</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <h3 class="h4">Annonce : <?php echo htmlspecialchars($annonce['title']); ?></h3>
                    <p class="lead"><?php echo formatPrice(PREMIUM_PRICE); ?></p>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="h5">Avantages Premium</h4>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Meilleure visibilité dans les résultats</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Badge "Premium" sur votre annonce</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Apparition en premier dans les recherches</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Plus de chances de vendre rapidement</li>
                        </ul>
                    </div>
                </div>

                <form id="payment-form" method="POST">
                    <div id="payment-element" class="mb-3">
                        <!-- Stripe Elements sera injecté ici -->
                    </div>

                    <button id="submit-button" class="btn btn-primary w-100">
                        Payer <?php echo formatPrice(PREMIUM_PRICE); ?>
                    </button>

                    <div id="payment-message" class="alert alert-danger mt-3" style="display: none;"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
const elements = stripe.elements();
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');

const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');
const paymentMessage = document.getElementById('payment-message');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    submitButton.disabled = true;

    try {
        const {paymentIntent, error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.origin + '/process-premium-payment.php?annonce_id=<?php echo $annonce_id; ?>',
            },
        });

        if (error) {
            paymentMessage.textContent = error.message;
            paymentMessage.style.display = 'block';
            submitButton.disabled = false;
        }
    } catch (e) {
        paymentMessage.textContent = "Une erreur est survenue lors du paiement.";
        paymentMessage.style.display = 'block';
        submitButton.disabled = false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
