public function getAll()
{
    return $this->conn->query("SELECT * FROM orders");
}

public function getByUser($userId)
{
    $stmt = $this->conn->prepare(
        "SELECT * FROM orders WHERE user_id=?"
    );

    $stmt->execute([$userId]);
    return $stmt;
}

public function create()
{
    $query = "INSERT INTO orders
             (user_id,total_price,status)
             VALUES (?,?,?)";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        $this->user_id,
        $this->total_price,
        $this->status
    ]);
}

public function updateStatus()
{
    $query = "UPDATE orders
              SET status=?
              WHERE order_id=?";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        $this->status,
        $this->order_id
    ]);
}

public function delete()
{
    $stmt = $this->conn->prepare(
        "DELETE FROM orders WHERE order_id=?"
    );

    return $stmt->execute([$this->order_id]);
}