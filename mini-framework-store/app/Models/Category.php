<?php

namespace Aries\MiniFrameworkStore\Models;

// IMPORTANT: Double-check if your Database class is in 'Includes' or 'Framework' namespace.
// Based on typical frameworks, 'Framework' is more common for core components.
// If your Database.php file is at src/Framework/Database.php, use this:
use Aries\MiniFrameworkStore\Includes\Database;
// If your Database.php file is at src/Includes/Database.php, use this:
// use Aries\MiniFrameworkStore\Includes\Database;

use PDO; // Add PDO for type hinting and constants

class Category extends Database
{
    protected $table = 'product_categories'; // Define the table name here for consistency

    public function __construct()
    {
        parent::__construct(); // Call the parent constructor to establish the connection
        // The parent Database class should hold the connection internally,
        // so you generally don't need a separate $this->db property in children.
        // The $this->getConnection() method will return the PDO instance from the parent.
    }

    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC"; // Added ORDER BY for consistent sorting
        $stmt = $this->getConnection()->prepare($sql); // Use getConnection() directly
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Use PDO::FETCH_ASSOC for clarity
    }

    /**
     * Retrieves a single category by its ID.
     * @param int $id The ID of the category.
     * @return array|false The category data as an associative array, or false if not found.
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Bind as integer
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single row
    }

    /**
     * Retrieves a single category by its slug.
     * @param string $slug The slug of the category.
     * @return array|false The category data as an associative array, or false if not found.
     */
    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR); // Bind as string
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single row
    }

    // You might also want methods for adding, updating, and deleting categories for an admin panel.
}