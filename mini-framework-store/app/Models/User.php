<?php

namespace Aries\MiniFrameworkStore\Models;

use Aries\MiniFrameworkStore\Includes\Database;
use \PDO; // Make sure PDO class is accessible for fetch types
use \PDOException; // Include PDOException for error handling


class User extends Database {
    private $db;

    public function __construct() {
        parent::__construct(); // Call the parent constructor to establish the connection
        $this->db = $this->getConnection(); // Get the connection instance
    }

    /**
     * Authenticates a user based on email and password.
     * @param string $email The user's email.
     * @param string $password The plain-text password provided by the user.
     * @return array|false The user's data (including user_type_name) on success, or false on failure.
     */
    public function authenticate($email, $password) {
        // Fetch user data including their user_type_id and user_type name
        $sql = "SELECT u.*, ut.name AS user_type_name
                FROM users u
                JOIN user_types ut ON u.user_type_id = ut.id
                WHERE u.email = :email";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR); // Use bindParam for security
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Use PDO::FETCH_ASSOC for clarity and consistency

            // Verify the password
            if ($user && password_verify($password, $user['password'])) {
                return $user; // Password is correct, return the user data
            } else {
                return false; // Invalid credentials
            }
        } catch (PDOException $e) {
            error_log("Authentication query error: " . $e->getMessage());
            return false; // Return false on error
        }
    }

    /**
     * Registers a new user with the specified user type ID.
     * @param array $data User data to register (name, email, password, address, phone, birthdate, user_type_id, created_at, updated_at)
     * @return int|false The ID of the newly registered user, or false on failure.
     */
    public function register($data) {
        $sql = "INSERT INTO users (name, email, password, address, phone, birthdate, user_type_id, created_at, updated_at)
                VALUES (:name, :email, :password, :address, :phone, :birthdate, :user_type_id, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'address' => $data['address'],
                'phone' => $data['phone'],
                'birthdate' => $data['birthdate'],
                'user_type_id' => $data['user_type_id'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at']
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            throw $e; // Re-throw to be caught in register.php for specific error messages
        }
    }

    public function update($data) {
        $sql = "UPDATE users SET name = :name, email = :email, address = :address, phone = :phone, birthdate = :birthdate, updated_at = :updated_at WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'birthdate' => $data['birthdate'],
                'updated_at' => date('Y-m-d H:i:s') // Automatically update timestamp
            ]);
            return $stmt->rowCount(); // Return number of affected rows
        } catch (PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id
            ]);
            return $stmt->rowCount(); // Return number of affected rows
        } catch (PDOException $e) {
            error_log("User deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper method to get user_type_id by name
     * @param string $typeName The name of the user type (e.g., 'Customer', 'Seller')
     * @return int|null The ID of the user type, or null if not found
     */
    public function getUserTypeId($typeName) {
        $sql = "SELECT id FROM user_types WHERE name = :name";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['name' => $typeName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching user type ID: " . $e->getMessage());
            return null;
        }
    }

    // You can add a method to get a user by ID if needed, e.g., for profile pages
    public function getUserById($userId) {
        $sql = "SELECT u.*, ut.name AS user_type_name
                FROM users u
                JOIN user_types ut ON u.user_type_id = ut.id
                WHERE u.id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return false;
        }
    }
}