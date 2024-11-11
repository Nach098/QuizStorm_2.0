<?php
session_start();
include_once ("Configuration.php");
$controllerName = isset($_GET["controller"]) ? $_GET["controller"] : "" ;
$actionName = isset($_GET["action"]) ? $_GET["action"] : "" ;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controllerName = $_POST["controller"];
    $actionName = $_POST["action"];
}


if (!isset($_SESSION['user']) && $controllerName != "Users") {
    // Si el usuario no está logueado, redirige al controlador de inicio de sesión
    $controllerName = "Users";
    $actionName = "getLogin";
}

$router = Configuration::getRouter();
$router->route($controllerName, $actionName);

