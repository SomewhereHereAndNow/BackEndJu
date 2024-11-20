<?php

// Load the Slim framework and dependencies
require "vendor/autoload.php";

use Slim\Factory\AppFactory;
use MongoDB\Client as MongoClient;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start the session
session_start();

// Create the Slim app
$app = AppFactory::create();
$app->setBasePath('/api/products');

// Enable the body parsing middleware
$app->addBodyParsingMiddleware();

// Custom session middleware
$app->add(function ($request, $handler) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $handler->handle($request);
});

// MongoDB connection using environment variables
$mongoClient = new MongoClient($_ENV['MONGO_DB_CONNECTION_STRING']);
$db = $mongoClient->selectDatabase($_ENV['MONGO_DB_NAME'] ?? 'my_database');
$productCollection = $db->selectCollection('products');

// Seed the database (optional, for initial setup only)
function seedDatabase($productCollection) {
    try {
        $productCollection->deleteMany([]); // Clear the collection
        $products = [
            [
                "name" => "Tomato",
                "type" => "Vegetable",
                "description" => "Fresh tomatoes",
                "price" => 350.00,
                "image" => "https://media.istockphoto.com/id/1132371208/photo/three-ripe-tomatoes-on-green-branch.jpg"
            ],
            [
                "name" => "Onion",
                "type" => "Vegetable",
                "description" => "Red onions",
                "price" => 2500.00,
                "image" => "https://images.herzindagi.info/image/2023/Dec/benefits-of-eating-onions-in-winter-add-to-diet-anti-ageing.jpg"
            ]
        ];
        $productCollection->insertMany($products);
        echo "Database Seeded Successfully\n";
    } catch (Exception $e) {
        echo "Error Seeding Database: " . $e->getMessage() . "\n";
    }
}

// Uncomment to seed the database once if needed
// seedDatabase($productCollection);

// Define routes

// Simple route to check if the server is running
$app->get("/", function ($request, $response) {
    $response->getBody()->write("Running");
    return $response;
});

// GET route for fetching all products (returns JSON)
$app->get('/list', function ($request, $response) use ($productCollection) {
    $allProducts = $productCollection->find()->toArray();
    $response->getBody()->write(json_encode($allProducts));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST route for submitting a new product
$app->post('/submit', function ($request, $response) use ($productCollection) {
    $data = $request->getParsedBody();

    // Basic validation
    $name = $data['name'] ?? null;
    $description = $data['description'] ?? null;
    $price = isset($data['price']) ? (float) $data['price'] : null;
    $image = $data['image'] ?? null;
    $type = $data['type'] ?? 'Default Type';

    if (!$name || !$description || !$price) {
        return $response
            ->withStatus(400)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['error' => 'Name, description, and price are required fields']));
    }

    // Insert product into MongoDB
    $product = [
        "name" => $name,
        "type" => $type,
        "description" => $description,
        "price" => $price,
        "image" => $image,
    ];

    try {
        $productCollection->insertOne($product);
        $response->getBody()->write(json_encode(['message' => 'Product added successfully!', 'product' => $product]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['error' => 'Failed to add product', 'details' => $e->getMessage()]));
    }
});

// GET route for rendering the SignUpForm
$app->get('/views/SignUpForm', function ($request, $response) {
    $renderer = new PhpRenderer(__DIR__ . '/resources/views'); // Adjusted for Slim's path
    return $renderer->render($response, "SignUpForm.php");
});

// POST route for handling form submissions (redirects to form)
$app->post('/views/SignUpForm', function ($request, $response) {
    return $response->withHeader('Location', '/views/SignUpForm')->withStatus(302);
});

// Run the Slim app
$app->run();
