<?php

namespace Aries\MiniFrameworkStore\Models;

use Aries\MiniFrameworkStore\Includes\Database; // Adjust if your Database.php is in 'Framework'
use Carbon\Carbon;
use \PDO;

class Product extends Database
{
    protected $table = 'products';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert($data)
    {
        $sql = "INSERT INTO {$this->table} (name, description, price, slug, image_path, category_id, seller_id, created_at, updated_at) VALUES (:name, :description, :price, :slug, :image_path, :category_id, :seller_id, :created_at, :updated_at)";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'slug' => $data['slug'],
            'image_path' => $data['image_path'],
            'category_id' => $data['category_id'],
            'seller_id' => $data['seller_id'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at']
        ]);
    }

    public function getAll()
    {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS seller_name
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.seller_id = u.id
                ORDER BY p.created_at DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS seller_name
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug)
    {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS seller_name
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.slug = :slug";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCategoryId($categoryId)
    {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS seller_name
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.category_id = :category_id
                ORDER BY p.created_at DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NEW METHOD FOR SEARCH FUNCTIONALITY ---
    public function searchProducts($query)
    {
        $sql = "SELECT p.*, c.name AS category_name, u.name AS seller_name
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE p.name LIKE :query OR p.description LIKE :query
                ORDER BY p.created_at DESC"; // Order by most recent matches
        $stmt = $this->getConnection()->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($data)
    {
        $sql = "UPDATE {$this->table} SET name = :name, description = :description, price = :price, slug = :slug, image_path = :image_path, category_id = :category_id, updated_at = :updated_at WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'slug' => $data['slug'],
            'image_path' => $data['image_path'],
            'category_id' => $data['category_id'],
            'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
        ]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}