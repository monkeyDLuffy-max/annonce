<?php
// Configuration Stripe
define('STRIPE_SECRET_KEY', 'sk_test_51QjVKoArkK6BqYFukH0wq6Cjgubi8HC20uWwrNJwNohVRG6UIoEfqpqQLO7AApqYSpyJjsMryZVqudFnmgOKRJO200gGvOdnAt');
define('STRIPE_PUBLIC_KEY', 'pk_test_51QjVKoArkK6BqYFukH0wq6Cjgubi8HC20uWwrNJwNohVRG6UIoEfqpqQLO7AApqYSpyJjsMryZVqudFnmgOKRJO200gGvOdnAt');
define('PREMIUM_PRICE', 5.00); // Prix en euros pour une annonce premium

// Inclusion de l'autoloader Stripe
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de la clé secrète Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
?>
