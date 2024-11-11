<?php

class PreguntasController
{
    private $presenter;
    private $preguntasModel;

    public function __construct($presenter, $preguntasModel)
    {
        $this->presenter = $presenter;
        $this->preguntasModel = $preguntasModel;
    }

    public function getCrearPregunta()
    {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 3 && $roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $this->presenter->render("view/CrearPreguntaView.mustache");

    }

    public function agregarPregunta()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;
        $roleId1 = $roleId == 1;
        $roleId3 = $roleId == 3;
        $idEstado = $roleId == 1 ? 2 : 1; // Si el rol es 1 (editor), el id_estado es 2. De lo contrario, es 1.
        $required = ['idCategoria', 'descripcion', 'opcionA', 'opcionB', 'opcionC', 'opcionD', 'respuestaCorrecta'];
        $errorMessage = "Error: Todos los campos son obligatorios";

        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                echo $errorMessage;
                return;
            }
        }
        $idCategoria = $_POST['idCategoria'];
        $descripcion = $_POST['descripcion'];
        $opcionA = $_POST['opcionA'];
        $opcionB = $_POST['opcionB'];
        $opcionC = $_POST['opcionC'];
        $opcionD = $_POST['opcionD'];
        $respuestaCorrecta = $_POST['respuestaCorrecta'];

        if (!$this->preguntasModel->buscarPreguntaPorDescripcion($descripcion)) {
            $this->preguntasModel->agregarPregunta($idCategoria, $descripcion, $opcionA, $opcionB, $opcionC, $opcionD, $respuestaCorrecta, $idEstado);
            $this->presenter->render("view/nuevaPreguntaExitosaView.mustache", ['roleId1' => $roleId1, 'roleId3' => $roleId3]);
        } else {
            $_SESSION['error'] = "Esta pregunta ya existe! intenta con otra";
            $this->presenter->render("view/CrearPreguntaView.mustache", ['error' => $_SESSION['error']]);
        }
    }

    public function getPreguntasAceptadas()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }
        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $data = [
            'username' => $username,
            'preguntasAceptadas' => $this->preguntasModel->getPreguntasAceptadas(),
            'edit' => true
        ];

        $this->presenter->render("view/PreguntasView.mustache", $data);
    }

    public function getPreguntasReportadas()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $data = [
            'username' => $username,
            'preguntasReportadas' => $this->preguntasModel->getPreguntasReportadas(),
            'repport' => true
        ];

        $this->presenter->render("view/PreguntasView.mustache", $data);
    }

    public function getPreguntasSugeridas()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }
        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $data = [
            'username' => $username,
            'preguntasSugeridas' => $this->preguntasModel->getPreguntasSugeridas(),
            'suggested' => true
        ];

        $this->presenter->render("view/PreguntasView.mustache", $data);
    }

    private function redirectToQuestionPage($idEstado)
    {
        switch ($idEstado) {
            case 1:
                $location = "/Preguntas/getPreguntasSugeridas";
                break;
            case 2:
                $location = "/Preguntas/getPreguntasAceptadas";
                break;
            case 3:
                $location = "/Preguntas/getPreguntasReportadas";
                break;
            default:
                $location = "/Preguntas/getPreguntasAceptadas";
                break;
        }
        header("Location: " . $location);
        exit();
    }

    public function borrar()
    {
        $id = $_POST["id"];
        $idEstado = $this->preguntasModel->getPregunta($id);
        $this->preguntasModel->borrarPregunta($id);
        $this->redirectToQuestionPage($idEstado[0]["id_estado"]);
    }

    public function aceptar()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }
        $id = $_POST["id"];
        $idEstado = $this->preguntasModel->getPregunta($id);
        $this->preguntasModel->aceptarPregunta($id);
        $this->redirectToQuestionPage($idEstado[0]["id_estado"]);
    }

    public function irAEditarPregunta(){

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $id = $_POST["id"];
        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $data = [
            'username' => $username,
            'edit' => true
        ];

        if ($id !== null){
            $data['pregunta'] =  $this->preguntasModel->getPreguntaYRespuestas($id);
        }
        $this->presenter->render("view/EditarPreguntaView.mustache", $data);
    }

    public function editar(){

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 1) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $requiredParams = ['id', 'descripcion', 'id_categoria', 'opcionA', 'opcionB', 'opcionC', 'opcionD', 'resp_correcta'];

        foreach ($requiredParams as $param) {
            if (!isset($_POST[$param]) || empty($_POST[$param])) {
                echo "No se pudo editar, falta el parámetro: " . $param;
            }
        }
        $id = $_POST['id'];
        $descripcion = $_POST['descripcion'];
        $idCategoria = $_POST['id_categoria'];
        $opcionA = $_POST['opcionA'];
        $opcionB = $_POST['opcionB'];
        $opcionC = $_POST['opcionC'];
        $opcionD = $_POST['opcionD'];
        $respCorrecta = $_POST['resp_correcta'];
        $idEstado=$this->preguntasModel->getPregunta($id);

        $this->preguntasModel->editarPregunta($id, $descripcion, $idCategoria, $opcionA, $opcionB, $opcionC, $opcionD, $respCorrecta);

        // Restablece los parámetros a su estado original
        foreach ($requiredParams as $param) {
            unset($_POST[$param]);
        }

        $this->redirectToQuestionPage($idEstado[0]["id_estado"]);

    }
}