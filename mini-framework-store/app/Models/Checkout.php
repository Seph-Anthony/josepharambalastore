<?php

namespace Aries\MiniFrameworkStore\Models;

use Aries\MiniFrameworkStore\Includes\Database;
use Carbon\Carbon;
use \PDO;
use \PDOException;

class Checkout extends Database
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = $this->getConnection();
    }

    public function guestCheckout($data)
    {
        // Columns in 'orders' table are: customer_id, guest_name, guest_phone, guest_address, total, status_id, created_at, updated_at
        $sql = "INSERT INTO orders (customer_id, guest_name, guest_phone, guest_address, total, status_id, created_at, updated_at) VALUES (:customer_id, :guest_name, :guest_phone, :guest_address, :total, :status_id, :created_at, :updated_at)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'customer_id' => null,
                'guest_name' => $data['name'],
                'guest_phone' => $data['phone'],
                'guest_address' => $data['address'],
                'total' => $data['total'],
                'status_id' => 1, // Assuming 'Pending' status has ID 1
                'created_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Guest checkout error: " . $e->getMessage());
            return false;
        }
    }

    public function userCheckout($data)
    {
        // Columns in 'orders' table are: customer_id, guest_name, guest_phone, guest_address, total, status_id, created_at, updated_at
        $sql = "INSERT INTO orders (customer_id, guest_name, guest_phone, guest_address, total, status_id, created_at, updated_at) VALUES (:customer_id, :guest_name, :guest_phone, :guest_address, :total, :status_id, :created_at, :updated_at)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'customer_id' => $data['user_id'],
                'guest_name' => null,
                'guest_phone' => null,
                'guest_address' => null,
                'total' => $data['total'],
                'status_id' => 1, // Assuming 'Pending' status has ID 1
                'created_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("User checkout error: " . $e->getMessage());
            return false;
        }
    }

    public function saveOrderDetails($data)
    {
        $productModel = new \Aries\MiniFrameworkStore\Models\Product();
        $productDetails = $productModel->getById($data['product_id']);

        $sellerId = null;

        // Corrected: Now checking for 'seller_id' key in $productDetails
        if ($productDetails && isset($productDetails['seller_id'])) {
            $sellerId = $productDetails['seller_id'];
        }

        if (is_null($sellerId)) {
            throw new \Exception("Error: Product ID " . $data['product_id'] . " does not have an associated seller ID. Cannot save order details without a seller.");
        }

        // Columns in 'order_details' table are: order_id, product_id, seller_id, quantity, price, subtotal
        $sql = "INSERT INTO order_details (order_id, product_id, seller_id, quantity, price, subtotal) VALUES (:order_id, :product_id, :seller_id, :quantity, :price, :subtotal)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'order_id' => $data['order_id'],
                'product_id' => $data['product_id'],
                'seller_id' => $sellerId,
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'subtotal' => $data['subtotal']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error saving order details: " . $e->getMessage());
            return false;
        }
    }

    public function getRecentSellerOrders($sellerId, $limit = 10)
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at AS order_date,
                    o.total AS order_total,
                    os.name AS status_name,
                    os.id AS status_id, -- ADDED: Include status_id directly for easier lookup
                    COALESCE(u.name, o.guest_name) AS customer_name,
                    COALESCE(u.email, 'N/A') AS customer_email,
                    COALESCE(u.phone, o.guest_phone) AS customer_phone,
                    oi.quantity,
                    oi.price AS item_price_at_order,
                    oi.subtotal AS item_subtotal,
                    p.name AS product_name,
                    p.image_path AS product_image,
                    oi.seller_id as seller_id
                FROM orders o
                JOIN order_details oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON o.customer_id = u.id
                JOIN order_statuses os ON o.status_id = os.id
                WHERE oi.seller_id = :seller_id
                ORDER BY o.created_at DESC, o.id DESC
                LIMIT :limit";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':seller_id', $sellerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching recent seller orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches all recent orders, including customer details, product details, and seller details, for admin view.
     * @param int $limit The maximum number of orders to retrieve.
     * @return array An array of order details.
     */
    public function getAllRecentOrders($limit = 50)
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at AS order_date,
                    o.total AS order_total,
                    os.name AS status_name,
                    os.id AS status_id, -- ADDED: Include status_id directly for easier lookup
                    COALESCE(u.name, o.guest_name) AS customer_name,
                    COALESCE(u.email, 'N/A') AS customer_email,
                    COALESCE(u.phone, o.guest_phone) AS customer_phone,
                    oi.quantity,
                    oi.price AS item_price_at_order,
                    oi.subtotal AS item_subtotal,
                    p.name AS product_name,
                    p.image_path AS product_image,
                    s.name AS seller_name,
                    oi.seller_id as seller_id
                FROM orders o
                JOIN order_details oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON o.customer_id = u.id
                JOIN order_statuses os ON o.status_id = os.id
                JOIN users s ON oi.seller_id = s.id
                ORDER BY o.created_at DESC, o.id DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all recent orders: " . $e->getMessage());
            return [];
        }
    }

    public function getOrderDetailsById($orderId) {
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at as order_date,
                    o.total AS order_total,
                    os.name AS status_name,
                    os.id AS status_id,
                    COALESCE(u.name, o.guest_name) AS customer_name,
                    COALESCE(u.email, 'N/A') AS customer_email,
                    COALESCE(u.phone, o.guest_phone) AS customer_phone,
                    COALESCE(u.address, o.guest_address) AS customer_address,
                    p.name AS product_name,
                    p.image_path AS product_image,
                    oi.quantity,
                    oi.price AS item_price_at_order,
                    oi.subtotal AS item_subtotal,
                    s.name AS seller_name,
                    oi.seller_id
                FROM
                    orders o
                JOIN
                    order_details oi ON o.id = oi.order_id
                JOIN
                    products p ON oi.product_id = p.id
                LEFT JOIN
                    users u ON o.customer_id = u.id
                JOIN
                    order_statuses os ON o.status_id = os.id
                JOIN
                    users s ON oi.seller_id = s.id
                WHERE
                    o.id = :order_id
                ORDER BY
                    p.name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order details for order ID " . $orderId . ": " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches all orders for a specific user ID (customer, seller, or admin acting as customer).
     * This method assumes 'customer_id' in the 'orders' table stores the user ID.
     * @param int $userId The ID of the user.
     * @return array An array of order details.
     */
    public function getOrdersByUserId($userId) // Renamed from getCustomerOrders
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at AS order_date,
                    o.total AS order_total,
                    os.name AS status_name,
                    oi.quantity,
                    oi.price AS item_price_at_order,
                    oi.subtotal AS item_subtotal,
                    p.name AS product_name,
                    p.image_path AS product_image
                FROM orders o
                JOIN order_details oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                JOIN order_statuses os ON o.status_id = os.id
                WHERE o.customer_id = :user_id -- Using :user_id to match parameter name
                ORDER BY o.created_at DESC, o.id DESC"; // Order by most recent first

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT); // Bind to :user_id
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching orders for user ID " . $userId . ": " . $e->getMessage());
            return [];
        }
    }

    public function updateOrderStatus($orderId, $newStatusId) {
        // Columns in 'orders' table are: status_id, updated_at, id
        $sql = "UPDATE orders SET status_id = :status_id, updated_at = :updated_at WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            // --- IMPORTANT LOGGING START ---
            error_log("Checkout.php::updateOrderStatus: Preparing to execute UPDATE for Order ID: " . $orderId . " to Status ID: " . $newStatusId);
            // --- IMPORTANT LOGGING END ---
            $stmt->execute([
                'status_id' => $newStatusId,
                'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
                'id' => $orderId
            ]);
            $rowCount = $stmt->rowCount();
            // --- IMPORTANT LOGGING START ---
            error_log("Checkout.php::updateOrderStatus: UPDATE query executed. Rows affected: " . $rowCount . " for Order ID: " . $orderId);
            // --- IMPORTANT LOGGING END ---
            return $rowCount;
        } catch (PDOException $e) {
            // --- IMPORTANT LOGGING START ---
            error_log("Error updating order status for order ID " . $orderId . ": " . $e->getMessage());
            // --- IMPORTANT LOGGING END ---
            return false;
        }
    }

    public function getOrderStatuses() {
        // Columns in 'order_statuses' table are: id, name
        $sql = "SELECT id, name FROM order_statuses ORDER BY id ASC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching order statuses: " . $e->getMessage());
            return [];
        }
    }
}