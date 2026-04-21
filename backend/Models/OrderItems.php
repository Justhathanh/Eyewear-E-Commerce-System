public function create()
{
    $query = "INSERT INTO order_items
             (order_id,product_id,quantity,price)
             VALUES (?,?,?,?)";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        $this->order_id,
        $this->product_id,
        $this->quantity,
        $this->price
    ]);
}

public function getByOrder($orderId)
{
    $query = "SELECT oi.*, p.name
              FROM order_items oi
              JOIN products p
              ON oi.product_id = p.product_id
              WHERE order_id=?";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([$orderId]);

    return $stmt;
}

public function delete()
{
    $stmt = $this->conn->prepare(
        "DELETE FROM order_items WHERE id=?"
    );

    return $stmt->execute([$this->id]);
}