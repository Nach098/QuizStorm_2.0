<?php

class GameController
{
    private $presenter;
    private $preguntasModel;
    private $partidasModel;
    private $usersModel;

    public function __construct($presenter, $preguntasModel, $partidasModel, $usersModel)
    {
        $this->presenter = $presenter;
        $this->preguntasModel = $preguntasModel;
        $this->partidasModel = $partidasModel;
        $this->usersModel = $usersModel;
    }

    public function getQuestion()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId !== 3) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $partidaId = $_SESSION['partidaId'];
        $puntos = $this->partidasModel->getPuntaje($partidaId);
        $promedioJugador = $this->usersModel->getPromedioAciertos($userId);
        $mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : null;
        $respuestaCorrecta = isset($_SESSION['respuestaCorrecta']) ? $_SESSION['respuestaCorrecta'] : null;

        if ($mensaje == 'Perdiste') {
            header("Location: /Game/endGame");
            exit();
        }

        if (isset($_SESSION['pregunta'])) {
            $preguntaRandom = $_SESSION['pregunta'];
        } else {
            $preguntaRandom = $this->preguntasModel->getPreguntaRandom($userId, $promedioJugador);
            $_SESSION['pregunta'] = $preguntaRandom;
        }

        $preguntaId = $preguntaRandom['_id'];
        $colorCategoria = $this->preguntasModel->getColorCategoria($preguntaId);
        $nombreCategoria= $this->preguntasModel->getNombreCategoria($preguntaId);
        $respuestas = $this->preguntasModel->getRespuestas($preguntaId);


        $reporte = null;
        if (isset($_SESSION['reporte'])) {
            $reporte = $_SESSION['reporte'];
            unset($_SESSION['reporte']); // Borra la variable de sesión después de usarla
        }

            $this->presenter->render("view/GameView.mustache", [
            'nombreCategoria' => $nombreCategoria,
            'colorCategoria' => $colorCategoria,
            'pregunta' => $preguntaRandom,
            'respuestas' => $respuestas,
            'puntos' => $puntos[0]['puntaje'],
            'mensaje' => $mensaje,
            'respuestaCorrecta' => $respuestaCorrecta, 'reporte' => $reporte
        ]);
        $_SESSION['mensaje'] = null;
        $_SESSION['respuestaCorrecta'] = null;
    }

    public function startGame()
    {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;


        if ($roleId !== 3) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $partidaId = $this->partidasModel->addPartida($userId);
        $_SESSION['partidaId'] = $partidaId;
        $this->getQuestion();
    }

    public function postAnswer()
    {
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $preguntaId = $_POST['preguntaId'];
        $respuestaUsuarioId = $_POST['respuestaId'];

        $esCorrecta = $this->preguntasModel->comprobarRespuesta($respuestaUsuarioId);

        $this->preguntasModel->agregarVezJugada($preguntaId);
        $this->usersModel->agregarPreguntaJugada($userId);

        if ($esCorrecta) {
            $this->preguntasModel->agregarVezCorrecta($preguntaId);
            $this->usersModel->agregarPreguntaCorrecta($userId);
        }

        $partidaId = $_SESSION['partidaId'];
        $puntos = $this->partidasModel->getPuntaje($partidaId);
        $puntos = $esCorrecta ? $puntos[0]['puntaje'] + 1 : $puntos[0]['puntaje'];
        $this->partidasModel->actualizarPuntosPartida($partidaId, $puntos);

        $this->preguntasModel->addPreguntaJugada($userId, $preguntaId, $esCorrecta);

        unset($_SESSION['pregunta']);

        if ($esCorrecta) {
            header("Location: /Game/getQuestion");
        } else {
            $respuestas = $this->preguntasModel->getRespuestas($preguntaId);
            foreach ($respuestas as &$respuesta) {
                $respuesta['esRespuestaSeleccionada'] = $respuesta['_id'] == $respuestaUsuarioId;
                $respuesta['esRespuestaCorrecta'] = $this->preguntasModel->comprobarRespuesta($respuesta['_id']);
            }

            $_SESSION['pregunta'] = $this->preguntasModel->getPregunta($preguntaId);
            $_SESSION['respuestas'] = $respuestas;
            $_SESSION['respuestaSeleccionada'] = $this->preguntasModel->getRespuesta($respuestaUsuarioId);
            $_SESSION['respuestaCorrecta'] = $this->preguntasModel->getRespuestaCorrecta($preguntaId);

            header("Location: /Game/endGame");
            exit();
        }
    }

    public function timeUp()
    {
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $partidaId = $_SESSION['partidaId'];
        $puntos = $this->partidasModel->getPuntaje($partidaId);
        $puntos = $puntos[0]['puntaje'];

        $pregunta = $_SESSION['pregunta'];
        $preguntaId = $pregunta['_id'];
        $respuestas = $this->preguntasModel->getRespuestas($preguntaId);

        foreach ($respuestas as &$respuesta) {
            $respuesta['esRespuestaSeleccionada'] = false;
            $respuesta['esRespuestaCorrecta'] = $this->preguntasModel->comprobarRespuesta($respuesta['_id']);
        }

        $_SESSION['mensaje'] = 'Perdiste';
        $_SESSION['puntaje'] = $puntos;
        $_SESSION['respuestas'] = $respuestas;
        $_SESSION['pregunta'] = $pregunta; // Asegurarse de que la pregunta está en la sesión

        header("Location: /Game/endGame");
        exit();
    }


    public function endGame()

    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;


        if ($roleId !== 3) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $partidaId = $_SESSION['partidaId'];
        $puntos = $this->partidasModel->getPuntaje($partidaId);
        $puntos = $puntos[0]['puntaje'];

        $pregunta = $_SESSION['pregunta'];
        $respuestas = $_SESSION['respuestas'] ?? [];
        $respuestaCorrecta = $_SESSION['respuestaCorrecta'] ?? null;

        // Renderiza la vista de fin de juego con el puntaje final
        $this->presenter->render("view/EndGameView.mustache", [
            'puntos' => $puntos,
            'pregunta' => $pregunta,
            'respuestas' => $respuestas,
            'respuestaCorrecta' => $respuestaCorrecta
        ]);

        // Limpia las variables de sesión relacionadas con el juego
        unset($_SESSION['partidaId']);
        unset($_SESSION['pregunta']);
        unset($_SESSION['respuestas']);
        unset($_SESSION['respuestaCorrecta']);
        unset($_SESSION['mensaje']);
    }



    public function getPartidaId()
    {
        echo $_SESSION['partidaId'];
    }
}
