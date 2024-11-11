<?php

class HomeController
{

    private $presenter;
    private $usersModel;

    public function __construct($presenter, $usersModel)
    {
        $this->presenter = $presenter;
        $this->usersModel = $usersModel;
    }

    public function get()
    {
        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;
        $maxScore = $this->usersModel->getMaxScore($userId);
        $topUsers = $this->usersModel->getTopUsers();

        $mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : null;
        $puntaje = isset($_SESSION['puntaje']) ? $_SESSION['puntaje'] : null;

        // Redirige a la vista correspondiente basada en el rol del usuario
        if ($roleId == 1) { // Editor
            $this->presenter->render("view/HomeEditorView.mustache", [
                'username' => $username,
                'maxScore' => $maxScore,
                'topUsers' => $topUsers,
                'mensaje' => $mensaje,
                'puntaje' => $puntaje,
            ]);
        } else if ($roleId == 3) { // Jugador
            $this->presenter->render("view/HomeView.mustache", [
                'username' => $username,
                'maxScore' => $maxScore,
                'topUsers' => $topUsers,
                'mensaje' => $mensaje,
                'puntaje' => $puntaje,
            ]);
        } else if ($roleId == 2) { // Admin
            $this->presenter->render("view/HomeAdminView.mustache", [
                'username' => $username,
                'maxScore' => $maxScore,
                'topUsers' => $topUsers,
                'mensaje' => $mensaje,
                'puntaje' => $puntaje,
            ]);
        }
        $_SESSION['mensaje'] = null;
    }
}