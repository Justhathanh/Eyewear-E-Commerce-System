<?php

class User
{
    private $conn;
    private $table = "users";

    public $user_id;
    public $name;
    public $email;
    public $password;
    public $role;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // CREATE
    public function create()
    {
        $query = "INSERT INTO users
                 (name,email,password,role)
                 VALUES (?,?,?,?)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $this->name,
            $this->email,
            $this->password,
            $this->role
        ]);
    }

    // READ
    public function getAll()
    {
        return $this->conn->query("SELECT * FROM users");
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE user_id=?"
        );

        $stmt->execute([$id]);
        return $stmt;
    }

    // UPDATE
    public function update()
    {
        $query = "UPDATE users
                  SET name=?, email=?, role=?
                  WHERE user_id=?";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $this->name,
            $this->email,
            $this->role,
            $this->user_id
        ]);
    }

    // DELETE
    public function delete()
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM users WHERE user_id=?"
        );

        return $stmt->execute([$this->user_id]);
    }
}