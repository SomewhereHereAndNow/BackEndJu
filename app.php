 <?php

// Autoload dependencies
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Slim\Factory\AppFactory;
use MongoDB\Client as MongoClient;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Razorpay\Api\Api;
 

// Start the session
session_start();
 

$razorpayApiKey = "rzp_test_SGmdC8LUxtlgND";   
$razorpayApiSecret = "T3pymnZ9BZk81wpuoAsLgyOC";  
$razorpay = new Api($razorpayApiKey, $razorpayApiSecret);
$user_name;
function CheckSuperUserData1($userCollection, $data, $response) {
    // Extract name and password from data
    $userId = $data['name'] ?? null;
    $userPassword = $data['password'] ?? null;

    // Check if both name and password are provided
    if (!$userId || !$userPassword) {
        $response->getBody()->write(json_encode(['error' => 'User name/password is required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if the provided name and password match the required superuser credentials
    if ($userId === 'Inventory' && $userPassword === 'Access') {
      

        $response->getBody()->write(json_encode(['message' => 'Login successful']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } else {
        // Respond with an error if credentials are incorrect
        $response->getBody()->write(json_encode(['error' => 'Invalid username or password']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
function CheckSuperUserData($userCollection, $data, $response) {
    // Extract name and password from data
    $userId = $data['name'] ?? null;
    $userPassword = $data['password'] ?? null;

    // Check if both name and password are provided
    if (!$userId || !$userPassword) {
        $response->getBody()->write(json_encode(['error' => 'User name/password is required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if the provided name and password match the required superuser credentials
    if ($userId === 'Master' && $userPassword === 'Access') {
      

        $response->getBody()->write(json_encode(['message' => 'Login successful']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } else {
        // Respond with an error if credentials are incorrect
        $response->getBody()->write(json_encode(['error' => 'Invalid username or password']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
 
function SetData( $name)
{

    // Static variable to retain value across function calls
    static $babaJi = null;

 

    // If a new name is provided, update static and session variables
    if ($name !== null) {
        $babaJi = $name;
   
    }

    // If static variable is null, fall back to session value
    
 

    return $babaJi;
}



function AddUserData($userCollection,$data,$response)
{
    $userId=$data['name']??null;
    $userPassword=$data['password']??null;
    $userAddress=$data['address']??null; 
    $userContactNo=$data['contactno']??null;
   
   
    if(!$userId||!$userPassword){
       $response->getBody()->write(json_encode(['error' => 'User name/password is required']));
       return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    };

    $document=[
        "name"=>$userId,
        "password"=>$userPassword,
        "address"=>$userAddress,
        "contactno"=>$userContactNo
    ];

  try {
        $userCollection->insertOne($document);

        // Return success response
        $response->getBody()->write(json_encode(['message' => 'User created successfully']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        // In case of an error during the insertion
        $response->getBody()->write(json_encode(['error' => 'Error creating user: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    
}

function CheckData($userCollection, $data, $response)
{
    $userId = $data['name'] ?? null;
    $userPassword = $data['password'] ?? null;

    if (!$userId || !$userPassword) {
        // If username or password is missing, return a 400 error
        $response->getBody()->write(json_encode(['error' => 'User name/password is required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Fetch user data from the database
    $user = $userCollection->findOne(["name" => $userId]);

    if ($user) {
        $storedPassword = $user['password'];
        if ($userPassword == $storedPassword) {
            // Store the username in session
            $_SESSION['user_name'] = $userId;

            // Send the successful login response with name in the body
            $response->getBody()->write(json_encode([
                'message' => 'Login successful',
                'name' => $userId // Send the username as part of the response
            ]));

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } else {
            // If password does not match, return an error
            $response->getBody()->write(json_encode(['error' => 'Invalid password']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    } else {
        // If user not found in the database
        $response->getBody()->write(json_encode(['error' => 'User Not Found']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}

 

// Function to add a product to the MongoDB collection
function addProduct($productCollection, $data) {
    try {
        // Insert the product into the collection
        $result = $productCollection->insertOne($data);

        // Return success response with inserted ID
        return [
            'status' => 201, // HTTP status for created
            'body' => [
                'message' => 'Product added successfully',
                'product_id' => $result->getInsertedId() // Return the inserted product's ID
            ]
        ];
    } catch (Exception $e) {
        // Log and return error response in case of failure
        error_log("Error inserting product: " . $e->getMessage());
        return [
            'status' => 500, // HTTP status for internal server error
            'body' => ['error' => 'Error inserting product']
        ];
    }
}

// Create the Slim app
$app = AppFactory::create();

// Enable the body parsing middleware (for JSON, form data, etc.)
$app->addBodyParsingMiddleware();
 
 


 $app->post('/api/razorpay/create-order', function (Request $request, Response $response) use ($razorpay) {
    // Check if the user is logged in
    if (!isset($_SESSION["user_id"])) {  // Assuming 'user_id' is stored in session after login
        $response->getBody()->write(json_encode(['error' => 'Please log in to continue']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // Proceed with Razorpay order creation if user is logged in
    $data = $request->getParsedBody();
    $amount = $data['amount'] ?? 0;

    // Validate the amount
    if ($amount <= 0) {
        $response->getBody()->write(json_encode(['error' => 'Invalid amount']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        // Create a Razorpay order
        $order = $razorpay->order->create([
            'receipt' => 'order_rcptid_11',
            'amount' => $amount * 100,  // Amount in paise
            'currency' => 'INR',
            'payment_capture' => 1  // Auto-capture payment
        ]);

        // Return the order details
        $response->getBody()->write(json_encode(['order_id' => $order['id']]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Error creating order: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});
 

$app->options('/api/razorpay/create-order', function (Request $request, Response $response) use ($razorpay){
    return addCorsHeaders($response)->withStatus(200);
});
$app->options('/api/razorpay/verify', function (Request $request, Response $response) use ($razorpay){
    return addCorsHeaders($response)->withStatus(200);
});
// Route to handle Razorpay webhook for payment verification
$app->post('/api/razorpay/verify', function (Request $request, Response $response) use ($razorpay) {
    $data = $request->getParsedBody();

    $orderId = $data['order_id'] ?? null;
    $paymentId = $data['payment_id'] ?? null;
    $signature = $data['signature'] ?? null;

    // Check if all required data is present
    if (!$orderId || !$paymentId || !$signature) {
        $response->getBody()->write(json_encode(['error' => 'Incomplete data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        // Verify payment signature
        $attributes = [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature
        ];

        $isValidSignature = $razorpay->utility->verifyPaymentSignature($attributes);

        if ($isValidSignature) {
            $response->getBody()->write(json_encode(['message' => 'Payment verified successfully']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Payment verification failed']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Verification error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});
 
 
// CORS Middleware: Allow all origins and set the proper headers
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
 

// DELETE route for deleting a product by ID
 // DELETE route for deleting a product by ID
 


// Custom session middleware to ensure session is started
$app->add(function ($request, $handler) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $handler->handle($request);
});

// MongoDB connection
$mongoClient = new MongoClient("mongodb+srv://Maitreya:killdill12@cluster0.sk6ugig.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0");
$db = $mongoClient->selectDatabase('my_database');
$productCollection = $db->selectCollection('products');
$db1=$mongoClient->selectDatabase('User_Database');
$userCollection = $db1->selectCollection('Users');

$app->options("/send_email",function($request,$response){
    return addCorsHeaders($response)->withStatus(200);
});
 
$app->map(['OPTIONS', 'POST'], "/update_stock", function ($request, $response) use ($productCollection) {
    if ($request->getMethod() === 'OPTIONS') {
        // Handle the CORS preflight request
        return addCorsHeaders($response)->withStatus(200);
    }

    // Handle the POST request for updating stock
    $data = $request->getParsedBody(); // Parse JSON body
    if (!isset($data['products'])) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid request: No products data provided']), JSON_PRETTY_PRINT);
        return addCorsHeaders($response)->withStatus(400);
    }

    $products = $data['products'];

    foreach ($products as $product) {
        if (!isset($product['_id'], $product['quantity'])) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid product data']), JSON_PRETTY_PRINT);
            return addCorsHeaders($response)->withStatus(400);
        }

        // Convert `_id` to a string if it's an array
        if (is_array($product['_id'])) {
            $product['_id'] = implode('', $product['_id']); // Flatten the array into a string
        }

        // Ensure `_id` is now a string
        if (!is_string($product['_id'])) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid _id format']), JSON_PRETTY_PRINT);
            return addCorsHeaders($response)->withStatus(400);
        }

        $productId = $product['_id']; // Product ID
        $quantityPurchased = $product['quantity']; // Quantity purchased

        try {
            $objectId = new MongoDB\BSON\ObjectId($productId);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Invalid ObjectId: ' . $e->getMessage()]), JSON_PRETTY_PRINT);
            return addCorsHeaders($response)->withStatus(400);
        }

        // Find the product in the collection
        $existingProduct = $productCollection->findOne(['_id' => $objectId]);

        if (!$existingProduct) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => "Product with ID $productId not found"]), JSON_PRETTY_PRINT);
            return addCorsHeaders($response)->withStatus(404);
        }

        $currentStock = $existingProduct['availableQuant'];

        if ($quantityPurchased > $currentStock) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => "Product '{$existingProduct['name']}' is out of stock. Available: $currentStock"
            ]), JSON_PRETTY_PRINT);
            return addCorsHeaders($response)->withStatus(400);
        }

        // Update stock
        $newStock = $currentStock - $quantityPurchased;
        $productCollection->updateOne(
            ['_id' => $objectId],
            ['$set' => ['availableQuant' => $newStock]]
        );
    }

    // Stock updated successfully
    $response->getBody()->write(json_encode(['success' => true, 'message' => 'Stock updated successfully']), JSON_PRETTY_PRINT);
    return addCorsHeaders($response)->withStatus(200);
});

$app->options('/get_products',function($request,$response){
    return addCorsHeaders($response)->withStatus(200);
});
$app->options('/update_product_stock',function($request,$response){
    return addCorsHeaders($response)->withStatus(200);
});
// Route to fetch all products from the ProductCollection
$app->get('/get_products', function ($request, $response) use ($productCollection) {
    // Retrieve all products from the collection
    $products = $productCollection->find([]);

    $productArray = [];
    foreach ($products as $product) {
        $productArray[] = [
            '_id' => (string)$product['_id'],
            'name' => $product['name'],
            'availableQuant' => $product['availableQuant'],
            'price' => $product['price'],
            'type' => $product['type'],
        ];
    }

     $response->getBody()->write(json_encode(($productArray)));
     return addCorsHeaders($response)->withHeader('Content-Type', 'application/json');
});

// Route to update the available quantity of a product
$app->post('/update_product_stock', function ($request, $response) use ($productCollection) {
    $data = $request->getParsedBody();
    $productId = $data['_id'];
    $newQuantity = $data['availableQuant'];

    try {
        $objectId = new MongoDB\BSON\ObjectId($productId);
    } catch (Exception $e) {
        return $response->withJson(['success' => false, 'message' => 'Invalid ObjectId'], 400);
    }

    // Update product's available quantity
    $updateResult = $productCollection->updateOne(
        ['_id' => $objectId],
        ['$set' => ['availableQuant' => $newQuantity]]
    );

    if ($updateResult->getModifiedCount() > 0) {
        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Stock updated successfully']));
        return addCorsHeaders($response)->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'No changes made']));
        return addCorsHeaders($response)->withHeader('Content-Type', 'application/json');
    }
});


$app->get("/send_email", function ($request, $response) use ($userCollection) {
    // Extract query parameters from the URL
    $params = $request->getQueryParams();

    // Extract username and emailPayload from query parameters
    $username = $params['username'] ?? 'Guest'; // Default to 'Guest' if username is not provided
    $emailPayload = $params['emailPayload'] ?? null;

    // Check if the emailPayload contains necessary fields
    if (!$emailPayload) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Both email and orderDetails are required in emailPayload'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Decode the emailPayload from JSON (assuming it's passed as a stringified JSON)
    $emailPayloadDecoded = json_decode($emailPayload, true);
    if (!$emailPayloadDecoded || !isset($emailPayloadDecoded['email']) || !isset($emailPayloadDecoded['orderDetails'])) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'emailPayload must contain both email and orderDetails fields'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

 
    $recipient = $emailPayloadDecoded['email'];
    $orderDetails = $emailPayloadDecoded['orderDetails'];
 
 
 
    try {
        $user = $userCollection->findOne(['name' => $username]);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Add the user's address and contact to the order details
        $userAddress = $user['address'];
        $userContact = $user['contactno'];
        $orderDetailsWithUser = "Order placed by: $username\nAddress: $userAddress\nContact: $userContact\n\n$orderDetails";

        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'maitreyaguptaa@gmail.com'; // Your Gmail address
            $mail->Password = 'atkx kvlg injl ilps'; // Your Gmail password (use an app-specific password if 2FA is enabled)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('maitreyaguptaa@gmail.com', 'BroSeeds');
            $mail->addAddress($recipient); // Use the email from the payload

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation';
            $mail->Body = "
                <h2>Your Order Details</h2>
                <pre>$orderDetailsWithUser</pre>
            ";

            // Send the email
            $mail->send();

            // Send success response
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Email sent successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            // Send failure response
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $mail->ErrorInfo
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request.'
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

 
// Seed the database (optional, can be commented out after seeding)
function seedDatabase($productCollection) {
    try {
        $productCollection->deleteMany([]); // Clear the collection
        $products = [
            [
                "name" => "Tomato",
                "type" => "Aloo",
                "description" => "Tomatoe",
                "price" => 350.00,
                "image" => "https://media.istockphoto.com/id/1132371208/photo/three-ripe-tomatoes-on-green-branch.jpg?s=612x612&w=0&k=20&c=qVjDb5Tk3-UccV-E9gqvoz97PTsP1QmBftw27qA9kEo="
            ],
            [
                "name" => "Pawaz",
                "type" => "Pawaz",
                "description" => "Onions",
                "price" => 2500.00,
                "image" => "https://images.herzindagi.info/image/2023/Dec/benefits-of-eating-onions-in-winter-add-to-diet-anti-ageing.jpg"
            ]
        ];
        $productCollection->insertMany($products); // Insert sample data
    } catch (Exception $e) {
        echo "Error Seeding Database: " . $e->getMessage() . "\n";
    }
}

 

// Define routes

// GET route for fetching all products
 

$app->get('/api/products', function (Request $request, Response $response) use ($productCollection) {
    $products = $productCollection->find()->toArray();
    $response->getBody()->write(json_encode($products));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST route for handling form submissions
 // POST route for handling form submissions
$app->post('/api/products', function ($request,$response) use ($productCollection) {
    $data = $request->getParsedBody();  

 
    error_log(print_r($data, true));  

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
$app->delete('/api/products/{name}/{type}', function ($request, $response, $args) use ($productCollection) {
    $productName = $args['name'];  // Get the product name from the URL
    $productType = $args['type'];  // Get the product type from the URL

    try {
        // Delete the product by its name and type
        $result = $productCollection->deleteOne([
            'name' => $productName,
            'type' => $productType
        ]);

        if ($result->getDeletedCount() === 1) {
            $response->getBody()->write(json_encode(['message' => 'Product deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Error deleting product: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS,DELETE')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
    }
});
$app->options('/api/products/{name}/{type}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*') // Allow all origins, adjust as needed
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE') // Allow DELETE method
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization') // Allow required headers
        ->withStatus(200); // Return 200 status for OPTIONS request
});


$app->options('/api/products', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS,DELETE')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});

// Home route for testing
$app->get('/', function ($request, $response) {
    $response->getBody()->write("Home Route Reached Successfully");
    return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
        ->withStatus(200);;
});
$app->options('/', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});
$app->options('/submit', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
        ->withStatus(200);
});
$app->post('/submit', function ($request, $response) use ($productCollection) {
    $data = $request->getParsedBody();
    $result = addProduct($productCollection, $data,$response);
   

    // Set CORS headers
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')  // Your frontend origin
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type');

    // Write the response based on the result from addProduct
    $response->getBody()->write(json_encode($result['body']));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result['status']);
});
 
$app->post('/login', function ($request, $response) use ($userCollection) {
 

    // Get parsed body data
    $data = $request->getParsedBody();
     
    $name = $data['name'];
 
    // Store the 'name' in Slim's container
    if ($name) {
        // Save the username in the session
        $_SESSION['user_name'] = $name;
    }
     
     
    $responseData = [
        'message' => $name ? 'Login successful' : 'Login failed',
        'user_name' => $_SESSION['user_name'] ?? 'Guest'
    ];
     

    $response=CheckData($userCollection, $data, $response);
    return $response
    ->withHeader('Access-Control-Allow-Origin', '*')  // Allow all origins, adjust if needed
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
});

$app->post('/signup',function($request , $response) use ($userCollection){
    $data=$request->getParsedBody();

    $response=AddUserData($userCollection,$data,$response);
    return $response
    ->withHeader('Access-Control-Allow-Origin', '*')  // Allow all origins, adjust if needed
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
});
 

$app->options('/login', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});

$app->options('/signup', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});
$app->options('/LogInMayukh', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});
$app->options('/LogInInventory', function($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});
$app->post('/LogInInventory',function($request,$response) use ($userCollection){
    $data=$request->getParsedBody();

    $response=CheckSuperUserData1($userCollection,$data,$response);
    return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});
 

$app->post('/LogInMayukh',function($request,$response) use ($userCollection){
    $data=$request->getParsedBody();

    $response=CheckSuperUserData($userCollection,$data,$response);
    return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});
 
 // Helper function to apply CORS headers
function addCorsHeaders($response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*') // Allow all origins; replace '*' with specific origin if needed
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
}

 

$app->post("/api/products/modify", function($request, $response) use ($productCollection) {
    // Set CORS headers
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    // Handle OPTIONS request
    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    // Get the input data from the request body (JSON payload)
    $data = $request->getParsedBody();

    // Extract the values from the POST data
    $name = isset($data['name']) ? $data['name'] : null;
    $type = isset($data['type']) ? $data['type'] : null;
    $description = isset($data['description']) ? $data['description'] : null;
    $price = isset($data['price']) ? $data['price'] : null;

    // Validate the incoming data
    if (empty($name) || empty($type) || empty($description) || empty($price)) {
        // Return error as JSON response
        $response->getBody()->write(json_encode(['error' => 'All fields are required.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Ensure the price is a valid number
    if (!is_numeric($price)) {
        // Return error as JSON response
        $response->getBody()->write(json_encode(['error' => 'Price must be a valid number.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        // Search for the product by name and type
        $product = $productCollection->findOne(['name' => $name, 'type' => $type]);

        if (!$product) {
            // Return error as JSON response
            $response->getBody()->write(json_encode(['error' => 'Product not found.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Prepare the data to update
        $updatedProduct = [
            'description' => $description,
            'price' => (float)$price,  // Cast to float for price
        ];

        // Update the product in the collection
        $updateResult = $productCollection->updateOne(
            ['name' => $name, 'type' => $type],
            ['$set' => $updatedProduct]
        );

        if ($updateResult->getModifiedCount() == 0) {
            // Return error as JSON response
            $response->getBody()->write(json_encode(['error' => 'No changes were made.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Return success message as JSON response
        $response->getBody()->write(json_encode(['message' => 'Product updated successfully.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
    } catch (Exception $e) {
        // Return error as JSON response
        $response->getBody()->write(json_encode(['error' => 'An error occurred: ' . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


// OPTIONS handler to respond to preflight requests
$app->options("/api/products/modify", function($request, $response) {
    return addCorsHeaders($response)->withStatus(200);
});

 
 
 
 
 
// Run the Slim app
$app->run();