<?php

class UsuarioPerfilController {

    private $usersModel;
    private $presenter;

    private $partidasModel;

    public function __construct($presenter, $usersModel, $partidasModel)
    {
        $this->presenter = $presenter;
        $this->usersModel = $usersModel;
        $this->partidasModel = $partidasModel;
    }
    public function getProfile() {

        $username = $_GET['username'];
        // Buscar los datos del usuario basándote en el nombre de usuario
        $user = $this->usersModel->getUserByUsername($username);
        $user[0]['profile_pic'] = base64_encode($user[0]['profile_pic']);
        $maxScore = $this->usersModel->getMaxScore($user[0]['_id']);
        $partidas = $this->partidasModel->getPartidasByUser($user[0]['_id']);


        // Pasar los datos del usuario a la vista
        $this->presenter->render("view/UsuarioPerfilView.mustache", ['user' => $user, 'maxScore' => $maxScore, 'partidas' => $partidas]);
    }

}