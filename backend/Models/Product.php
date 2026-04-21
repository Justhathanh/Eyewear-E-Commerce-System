<?php

class Product
{
    private $conn;
    private $table = "products";

    public $product_id;
    public $name;
    public $description;
    public $price;
    public $stock;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // CREATE
    public function create()
    {
        $query = "INSERT INTO products
                 (name,description,price,stock)
                 VALUES (?,?,?,?)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $this->name,
            $this->description,
            $this->price,
            $this->stock
        ]);
    }

    // READ ALL
    public function getAll()
    {
        $query = "SELECT * FROM products
                  ORDER BY created_at DESC";

        return $this->conn->query($query);
    }

    // READ BY ID
    public function getById($id)
    {
        $query = "SELECT * FROM products
                  WHERE product_id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt;
    }

    // UPDATE
    public function update()
    {
        $query = "UPDATE products
                  SET name=?,
                      description=?,
                      price=?,
                      stock=?
                  WHERE product_id=?";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $this->name,
            $this->description,
            $this->price,
            $this->stock,
            $this->product_id
        ]);
    }

    // DELETE
    public function delete()
    {
        $query = "DELETE FROM products
                  WHERE product_id=?";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([$this->product_id]);
    }
}