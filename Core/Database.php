<?php

namespace Core;

use PDO;
use PDOException;
class Database{

  

    public function getConnection():PDO
    {

        $host="localhost";
        $dbname="actual2";
        
        $user="root";
        $password="0710";

        $dsn="mysql:host={$host};dbname={$dbname};charset=utf8";

       
        try {
            // Attempt to establish a database connection
            $pdo = new PDO($dsn, $user, $password);

            // Set PDO error mode to exception for easier error handling
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Return the PDO instance
            return $pdo;
        } catch (PDOException $e) {
            // If an exception occurs, handle the error
           
            echo "Database connection failed: " . $e->getMessage();
            // Optionally, log the error or handle it in a different way
            // For example, you could throw a custom exception to propagate the error to the caller
            throw $e;
        }

         
        
    }

}
