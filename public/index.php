<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';

$app = new \Slim\App;

$key = 'server_hack'; 

function getDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library"; 
    return new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

// Middleware for token authentication
$authenticate = function ($request, $response, $next) use ($key) {
    $authHeader = $request->getHeader('Authorization');

    if (!$authHeader) {
        return $response->withStatus(401)->write(json_encode([
            "status" => "fail",
            "data" => ["title" => "Missing Authorization Header"]
        ]));
    }

    $token = explode(' ', $authHeader[0])[1]; // Extract the token

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM revoked_tokens WHERE token = :token"); 
        $stmt->execute(['token' => $token]);

        if ($stmt->rowCount() > 0) {
            return $response->withStatus(401)->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Token has been revoked"]
            ]));
        }

        $request = $request->withAttribute('user', $decoded->data);
        $response = $next($request, $response);

        $stmt = $conn->prepare("INSERT INTO revoked_tokens (token) VALUES (:token)"); // Updated to 'revoked_tokens'
        $stmt->execute(['token' => $token]);

        return $response;
    } catch (Exception $e) {
        return $response->withStatus(401)->write(json_encode([
            "status" => "fail",
            "data" => ["title" => "Invalid Token"]
        ]));
    }
};

// User registration
$app->post('/user/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'username' => $usr,
            'password' => hash('SHA256', $pass),
        ]);

        $response->getBody()->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
});

// User authentication (Generate new token)
$app->post('/user/authenticate', function (Request $request, Response $response) use ($key) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE username = :username AND password = :password"
        );
        $stmt->execute([
            'username' => $usr,
            'password' => hash('SHA256', $pass),
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600, // Token valid for 1 hour
                'data' => ["userid" => $user['userid']]
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');

            $response->getBody()->write(json_encode([
                "status" => "success",
                "token" => $jwt,
                "data" => null
            ]));
        } else {
            $response->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Authentication Failed!"]
            ]));
        }
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
});

// Add a new author (protected route, requires authentication)
$app->post('/authors/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $name = $data->name;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO authors (name) VALUES (:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $name]);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); // Apply the authentication middleware

// Get list of authors (protected route, requires authentication)
$app->get('/authors', function (Request $request, Response $response) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM authors");
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "status" => "success",
            "data" => $authors
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate); // Authentication middleware applied

// Add a new book (protected route, requires authentication)
$app->post('/books/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;

    try {
        $conn = getDBConnection();
        $sql = "INSERT INTO books (title, authorid) VALUES (:title, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':authorid', $authorid);
        $stmt->execute();

        $response->getBody()->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    return $response;
})->add($authenticate); // Apply middleware here

// Add a new entry to books_authors (protected route, requires authentication)
$app->post('/books_authors/add', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;

    try {
        $conn = getDBConnection();

        // Check if bookid exists
        $stmt = $conn->prepare("SELECT * FROM books WHERE bookid = :bookid");
        $stmt->bindParam(':bookid', $bookid);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            return $response->withStatus(400)->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Invalid book ID"]
            ]));
        }

        // Check if authorid exists
        $stmt = $conn->prepare("SELECT * FROM authors WHERE authorid = :authorid");
        $stmt->bindParam(':authorid', $authorid);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            return $response->withStatus(400)->write(json_encode([
                "status" => "fail", 
                "data" => ["title" => "Invalid author ID"]
            ]));
        }

        // Insert into books_authors
        $sql = "INSERT INTO books_authors (bookid, authorid) VALUES (:bookid, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid]);

        $response->getBody()->write(json_encode([
            "status" => "success", 
            "data" => null
        ]));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "status" => "fail", 
            "data" => ["title" => $e->getMessage()]
        ]));
    }

    return $response;
})->add($authenticate);

// Run the app
$app->run();
