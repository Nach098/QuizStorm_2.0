<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ajusta las rutas según la estructura de tu proyecto
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
class UsersModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function register($username, $password, $rep_password, $email, $name, $surname, $hash, $profile_pic, $birth_year, $gender, $country, $city, $latitude, $longitude)
    {
        if ($this->userExists($username, $email)) {
            $_SESSION['error'] = "El usuario o email ya existe";
            return false;
        }

        if ($password != $rep_password) {
            $_SESSION['error'] = "Las contraseñas no coinciden";
            return false;
        }

        if($profile_pic == null) {
            $_SESSION['error'] = "Debe cargar una foto de perfil válida";
        }

        $roleId = 3; // Establece el rol por defecto a 3

        $stmt = $this->database->prepare("INSERT INTO `USUARIOS`(`USERNAME`, `PASSWORD`, `EMAIL`, `NAME`, `SURNAME`,`HASH`,`PROFILE_PIC`,`BIRTH_YEAR`, `GENDER`, `COUNTRY`, `CITY`, `LATITUDE`, `LONGITUDE`, `ROL`, `fecha_creacion`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $this->database->execute($stmt, ["sssssssisssddi", $username, $password, $email, $name, $surname, $hash, $profile_pic, $birth_year, $gender, $country, $city, $latitude, $longitude, $roleId]);
        return true;
    }

    public function sendEmail($email, $username, $hash) {

        //$config = json_decode(file_get_contents('config/email_config.json'), true);

        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host =  "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "ivonecorletol@gmail.com";
            $mail->Password = "sgyz rtsx neeo yqro";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port =587;

            // Remitente y destinatarios
            $mail->setFrom("ivonecorletol@gmail.com", "QuizArg");
            $mail->addAddress($email);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Validación de correo electrónico';
            $mail->Body    = 'Haz clic en el siguiente enlace para validar tu correo electrónico: <a href="http://localhost/Users/validateEmail?hash=' . $hash . '">Validar Email</a>';

            $mail->send();
            return true;
        } catch (Exception $e) {
            $_SESSION['error'] = "El correo no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

    public function setEmailValidated($hash)
    {
        $stmt = $this->database->prepare("UPDATE USUARIOS SET EMAIL_VALIDATED = 1 WHERE HASH = ?");
        $this->database->execute($stmt, ["i", $hash]);
    }

    private function getEmailConfig() {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/email_config.json'), true);
        return $config;
    }

    public function login($usernameOrEmail, $password)
    {
        $stmt = $this->database->prepare("SELECT * FROM USUARIOS WHERE (USERNAME = ? OR EMAIL = ?) AND PASSWORD = ?");
        return $this->database->execute($stmt, ["sss", $usernameOrEmail, $usernameOrEmail, $password]);
    }

    public function agregarPreguntaJugada($userId)
    {
        $stmt = $this->database->prepare("UPDATE USUARIOS SET PREGUNTAS_JUGADAS = PREGUNTAS_JUGADAS + 1 WHERE _ID = ?");
        $this->database->execute($stmt, ["i", $userId]);

        $this->actualizarDificultad($userId);
    }

    public function agregarPreguntaCorrecta($userId)
    {
        $stmt = $this->database->prepare("UPDATE USUARIOS SET PREGUNTAS_ACERTADAS = PREGUNTAS_ACERTADAS + 1 WHERE _ID = ?");
        $this->database->execute($stmt, ["i", $userId]);

        $this->actualizarDificultad($userId);
    }

    public function actualizarDificultad($userId)
    {
        // Obtén el número de veces que el usuario ha respondido preguntas
        $stmt = $this->database->prepare("SELECT PREGUNTAS_JUGADAS FROM USUARIOS WHERE _ID = ?");
        $preguntasJugadas = $this->database->execute($stmt, ["i", $userId])[0]['PREGUNTAS_JUGADAS'];

        // Obtén el número de veces que el usuario ha respondido correctamente
        $stmt = $this->database->prepare("SELECT PREGUNTAS_ACERTADAS FROM USUARIOS WHERE _ID = ?");
        $preguntasAcertadas = $this->database->execute($stmt, ["i", $userId])[0]['PREGUNTAS_ACERTADAS'];

        // Calcula el porcentaje de aciertos
        $porcentajeAciertos = ($preguntasAcertadas / $preguntasJugadas) * 100;

        // Actualiza la dificultad del usuario en la base de datos
        $stmt = $this->database->prepare("UPDATE USUARIOS SET DIFICULTAD = ? WHERE _ID = ?");
        $this->database->execute($stmt, ["di", $porcentajeAciertos, $userId]);
    }

    public function getPromedioAciertos($userId)
    {
        $stmt = $this->database->prepare("SELECT DIFICULTAD FROM USUARIOS WHERE _ID = ?");
        return $this->database->execute($stmt, ["i", $userId])[0]['DIFICULTAD'];
    }

    public function userExists($username, $email)
    {
        $stmt = $this->database->prepare("SELECT * FROM USUARIOS WHERE USERNAME = ? OR EMAIL = ?");
        $result = $this->database->execute($stmt, ["ss", $username, $email]);
        return !empty($result);
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->database->prepare("SELECT * FROM USUARIOS WHERE USERNAME = ?");
        return $this->database->execute($stmt, ["s", $username]);
    }

    public function getUserById($userId)
    {
        $stmt = $this->database->prepare("SELECT * FROM USUARIOS WHERE _ID = ?");
        return $this->database->execute($stmt, ["i", $userId]);
    }

    public function getMaxScore($userId)
    {
        $stmt = $this->database->prepare("SELECT MAX(puntaje) as MAX_SCORE FROM PARTIDAS WHERE USER_ID = ?");
        return $this->database->execute($stmt, ["i", $userId])[0]['MAX_SCORE'];
    }

    public function getTopUsers()
    {
        $stmt = $this->database->prepare("SELECT USERNAME, SUM(puntaje) as TOTAL_SCORE FROM USUARIOS JOIN PARTIDAS ON USUARIOS._ID = PARTIDAS.USER_ID GROUP BY USERNAME ORDER BY TOTAL_SCORE DESC");
        return $this->database->execute($stmt);
    }

    // Funciones para obtener estadísticas

    public function getCantidadJugadores($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total_jugadores FROM USUARIOS WHERE fecha_creacion BETWEEN ? AND ?");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin])[0]['total_jugadores'];
    }

    public function getCantidadPartidas($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total_partidas FROM PARTIDAS WHERE fecha_creacion BETWEEN ? AND ?");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin])[0]['total_partidas'];
    }

    public function getCantidadPreguntas()
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total_preguntas FROM PREGUNTAS");
        return $this->database->execute($stmt)[0]['total_preguntas'];
    }

    public function getCantidadPreguntasCreadas($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total_preguntas_creadas FROM PREGUNTAS WHERE fecha_creacion BETWEEN ? AND ?");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin])[0]['total_preguntas_creadas'];
    }

    public function getCantidadUsuariosNuevos($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT COUNT(*) as total_usuarios_nuevos FROM USUARIOS WHERE fecha_creacion BETWEEN ? AND ?");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin])[0]['total_usuarios_nuevos'];
    }




    public function getCantidadUsuariosPorPais($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT COUNTRY, COUNT(*) as total_usuarios FROM USUARIOS WHERE fecha_creacion BETWEEN ? AND ? GROUP BY COUNTRY");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin]);
    }

    public function getCantidadUsuariosPorSexo($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("SELECT GENDER, COUNT(*) as total_usuarios FROM USUARIOS WHERE fecha_creacion BETWEEN ? AND ? GROUP BY GENDER ");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin]);
    }

    public function getCantidadUsuariosPorGrupoEdad($fecha_inicio, $fecha_fin)
    {
        $stmt = $this->database->prepare("
            SELECT 
                CASE 
                    WHEN YEAR(CURDATE()) - birth_year < 18 THEN 'Menores'
                    WHEN YEAR(CURDATE()) - birth_year > 60 THEN 'Jubilados'
                    ELSE 'Medio'
                END as grupo_edad,
                COUNT(*) as total_usuarios 
            FROM USUARIOS 
            WHERE fecha_creacion BETWEEN ? AND ?
            GROUP BY grupo_edad
        ");
        return $this->database->execute($stmt, ["ss", $fecha_inicio, $fecha_fin]);    }

    public function getDatosJugadoresConPorcentajeAciertos($fecha_inicio, $fecha_fin) {
        $stmt = $this->database->prepare("
        SELECT
            _ID,
            USERNAME,
            PREGUNTAS_JUGADAS,
            PREGUNTAS_ACERTADAS,
            (PREGUNTAS_ACERTADAS / PREGUNTAS_JUGADAS) * 100 AS PORCENTAJE_ACIERTOS
        FROM USUARIOS
        WHERE ROL = 3
    ");
        return $this->database->execute($stmt);
    }

    public function getPrintTotalUsersByGenre()
    {
        $query = "SELECT GENDER, COUNT(*) AS total_usuarios
              FROM USUARIOS WHERE ROL =3
               ";

        $query .= " GROUP BY GENDER";

        return $this->database->print($query);
    }

    public function getEmailValidado($username)
    {
        $stmt = $this->database->prepare("SELECT EMAIL_VALIDATED FROM USUARIOS WHERE USERNAME = ?");
        return $this->database->execute($stmt, ["s", $username])[0]['EMAIL_VALIDATED'];
    }

}
?>

