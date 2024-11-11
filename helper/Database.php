<?php

class Database
{
    private $conn;

    public function __construct($servername, $username, $password, $dbname)
    {
        $this->conn = mysqli_connect($servername, $username, $password, $dbname);

        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
    }

    public function query($sql){
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function prepare($sql){
        $stmt = $this->conn->prepare($sql);
        return $stmt;
    }

    public function getInsertId() {
        return $this->conn->insert_id;
    }

    public function execute($stmt, $params = null){
        if ($params) {
            $stmt->bind_param(...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        // si es un select, es decir un conjunto de resultados,  devuelvo el resultado, sino devuelvo true o false
        if ($result instanceof mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return $result;
        }
    }

    public function __destruct()
    {
        mysqli_close($this->conn);
    }

}