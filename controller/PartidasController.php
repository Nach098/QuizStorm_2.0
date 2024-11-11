<?php

class PartidasController
{

    private $presenter;
    private $usersModel;

    public function __construct($presenter, $partidasModel, $usersModel, )
    {
        $this->presenter = $presenter;
        $this->usersModel = $usersModel;
        $this->partidasModel = $partidasModel;
    }

    public function getPartidas()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 3) {
            // Redirigir a la página de home si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }
        $username = isset($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $userId = isset($_SESSION['user']) ? $_SESSION['user'][0]['_id'] : null;

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 13;
        $offset = ($page - 1) * $limit;

        $partidas = $this->partidasModel->getPartidasByUser($userId, $offset, $limit);
        $totalPartidas = $this->partidasModel->countPartidasByUser($userId);
        $totalPages = ceil($totalPartidas / $limit);

        $this->presenter->render("view/PartidasView.mustache", [
            'username' => $username,
            'partidas' => $partidas,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'prevPage' => $page > 1 ? $page - 1 : null,
            'nextPage' => $page < $totalPages ? $page + 1 : null
        ]);
    }



    public function reportarPregunta()
    {
        if (isset($_POST['pregunta-id'])){
            $preguntaId = $_POST['pregunta-id'];
            $this->partidasModel->reportarPregunta($preguntaId);
            unset($_SESSION['pregunta']);
            $_SESSION['reporte'] = 'Pregunta reportada exitosamente';
            header("Location: /Game/getQuestion");
        }
    }
}