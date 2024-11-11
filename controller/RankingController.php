<?php

class RankingController
{

    private $presenter;
    private $usersModel;

    public function __construct($presenter, $usersModel)
    {
        $this->presenter = $presenter;
        $this->usersModel = $usersModel;
    }

    public function getRanking()
    {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 3) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;
        $maxScore = $this->usersModel->getMaxScore($userId);
        $topUsers = $this->usersModel->getTopUsers();
        $this->presenter->render("view/RankingView.mustache", [
            'username' => $username,
            'maxScore' => $maxScore,
            'topUsers' => $topUsers
        ]);
    }
}