<?php

namespace Controller;

use Core\Database;
use PDO;
use PDOException;
use Firebase\JWT\JWT;
class Login{

private PDO $pdo;

public function __construct(){
    $db = new Database();
   
   $this->pdo = $db->getConnection();
}




public function loginuser() {
    $secretKey = 'jhdfhuheruhuurehhjldu';
    

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

    }
        if (isset($input['username']) && isset($input['password'])) {
            $username = $input['username'];
            $password = $input['password'];

        }

    if (!empty($username) && !empty($password)) {
        try {
            

            // Query for admin login
            $stmt = $this->pdo->prepare("SELECT * FROM Admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                if ($admin['password'] === $password) {
                    // Admin authenticated
                    $accessToken = JWT::encode([
                        "UserInfo" => [
                            "user" => $username,
                            "roles" => "admin"
                        ]
                    ], $secretKey, 'HS256');
                    http_response_code(200);
                    header('content-type:application/json');
                    echo json_encode($accessToken);
                } else {
                    http_response_code(201);
                    echo "password wrong";
                }
            } else {
                // Query for student login
                $stmt = $this->pdo->prepare("SELECT * FROM Student WHERE id = ?");
                $stmt->execute([$username]);
                $studentUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($studentUser) {
                    if ($studentUser['id'] === $password) {
                        // Student authenticated
                        $accessToken = JWT::encode([
                            "UserInfo" => [
                                "user" => $username,
                                "roles" => "student"
                            ]
                        ], $secretKey, 'HS256');
                        http_response_code(200);
                        header('content-type:application/json');
                        echo json_encode($accessToken);
                    } else {
                        http_response_code(201);
                        echo "password wrong";
                    }
                } else {
                    http_response_code(204);
                    echo "user not found";
                }
            }
        } catch (PDOException $e) {
           
            echo "Error: " . $e->getMessage();
        }
    } else {
        http_response_code(203);
        echo "Invalid username or password";
    }
}









}

