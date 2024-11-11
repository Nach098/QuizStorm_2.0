<?php

class PartidasModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPartidas($userId){
        $stmt = $this->database->prepare("SELECT * FROM PARTIDAS WHERE USER_ID = ?");
        return $this->database->execute($stmt, ["i", $userId]);
    }

    public function getPartida($partidaId){
        $stmt = $this->database->prepare("SELECT * FROM PARTIDAS WHERE _ID = ?");
        return $this->database->execute($stmt, ["i", $partidaId]);
    }

    public function getPuntaje($partidaId){
        $stmt = $this->database->prepare("SELECT puntaje FROM PARTIDAS WHERE _ID = ?");
        return $this->database->execute($stmt, ["i", $partidaId]);
    }

    public function addPartida($userId){
        $stmt = $this->database->prepare("INSERT INTO PARTIDAS (USER_ID) VALUES (?)");
        $this->database->execute($stmt, ["i", $userId]);
        return $this->database->getInsertId();
    }

    public function actualizarPuntosPartida($partidaId, $puntos){
        $stmt = $this->database->prepare("UPDATE PARTIDAS SET puntaje = ? WHERE _ID = ?");
        $this->database->execute($stmt, ["ii", $puntos, $partidaId]);
    }

    public function getPartidasByUser($userId, $offset = 0, $limit = 14) //CAMBIADO
    {
        $stmt = $this->database->prepare("SELECT fecha_creacion, puntaje FROM PARTIDAS WHERE USER_ID = ? ORDER BY fecha_creacion DESC LIMIT ?, ?");
        $stmt->bind_param("iii", $userId, $offset, $limit);
        return $this->database->execute($stmt);
    }

    public function countPartidasByUser($userId)
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total FROM PARTIDAS WHERE USER_ID = ?");
        $stmt->bind_param("i", $userId);
        $result = $this->database->execute($stmt);
        return $result[0]['total'];
    }



    public function reportarPregunta($idPregunta)
    {
        $stmt = $this->database->prepare("UPDATE PREGUNTAS SET id_estado = 3 WHERE _ID = ?");
        $this->database->execute($stmt, ["i", $idPregunta]);
    }

}