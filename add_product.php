<?php
require 'vendor/autoload.php'; // Composer autoload
use Slim\Factory\AppFactory;
use MongoDB\Client as MongoClient;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$app = AppFactory::create();

// Enable the body parsing middleware (for JSON, form data, etc.)
$app->addBodyParsingMiddleware();
// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    if ($request->getMethod() === 'OPTIONS') {
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*') // Adjust as necessary for security
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withStatus(200); // Return 200 status for OPTIONS request
    }
    // Set the CORS headers for the response
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*') // Allow all origins, adjust as needed
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

     

    return $response;
});
$mongoClient = new MongoClient("mongodb+srv://Maitreya:killdill12@cluster0.sk6ugig.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0");
$db = $mongoClient->selectDatabase('my_database');
$productCollection = $db->selectCollection('products');
$userCollection = $db->selectCollection('Users');
// Custom session middleware to ensure session is started
$app->add(function ($request, $handler) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $handler->handle($request);
});
$mongoUri = "mongodb://localhost:27017"; // Change to your MongoDB URI
$client = new MongoDB\Client($mongoUri);
$db = $client->shop; // Database name
$productsCollection = $db->products; // Collection name
// Validate input data
if (
    isset($data['name']) && !empty($data['name']) &&
    isset($data['description']) && !empty($data['description']) &&
    isset($data['price']) && !empty($data['price'])
) {
    // MongoDB connection URI
   

    try {
        // Prepare the product data
        $product = [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'image' => $data['image'] ?? null // Optional image field
        ];

        // Insert the product into MongoDB
        $result = $productsCollection->insertOne($product);

        // Send response
        echo json_encode([
            'message' => 'Product added successfully',
            'productId' => (string) $result->getInsertedId()
        ]);
    } catch (Exception $e) {
        // Return error if something goes wrong
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Return error if validation fails
    echo json_encode(['error' => 'Invalid data']);
}


$app->get('/api/products', function (Request $request, Response $response) use ($productCollection) {
    $products = $productCollection->find()->toArray();
    $response->getBody()->write(json_encode($products));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST route for handling form submissions
 // POST route for handling form submissions
$app->post('/api/products', function (Request $request, Response $response) use ($productCollection) {
    $data = $request->getParsedBody(); // Get data from the request body

    // Debugging: Log the incoming data
    error_log(print_r($data, true)); // This will log the received data to the PHP error log

    // Validation (e.g., check required fields)
    if (empty($data['name']) || empty($data['type']) || empty($data['price'])) {
        // Send an error response if data is invalid
        $response->getBody()->write(json_encode(['error' => 'Invalid input']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        // Insert product into MongoDB
        $result = $productCollection->insertOne($data);

        // Log the result of the insert operation
        error_log("Product inserted with ID: " . $result->getInsertedId());

        // Send success response
        $response->getBody()->write(json_encode(['message' => 'Product added successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        // Log any errors
        error_log("Error inserting product: " . $e->getMessage());

        // Send error response
        $response->getBody()->write(json_encode(['error' => 'Error inserting product']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();

?>
 
