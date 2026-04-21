public function create()
{
    $query = "INSERT INTO payments
             (order_id,amount,method,status)
             VALUES (?,?,?,?)";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        $this->order_id,
        $this->amount,
        $this->method,
        $this->status
    ]);
}

public function getByOrder($orderId)
{
    $stmt = $this->conn->prepare(
        "SELECT * FROM payments WHERE order_id=?"
    );

    $stmt->execute([$orderId]);
    return $stmt;
}