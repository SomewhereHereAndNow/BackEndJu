<?php

// Include Stripe PHP library
require 'vendor/autoload.php';

// Set your secret key (from the Stripe Dashboard)
\Stripe\Stripe::setApiKey('your-secret-key'); // Replace with your Secret Key

header('Content-Type: application/json');

// Retrieve amount from the frontend (e.g., in cents)
$amount = $_POST['amount'];

// Create a PaymentIntent
try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'inr', // Change to your currency
    ]);

    // Send client secret to frontend
    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
