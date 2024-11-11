<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';
class UsersController
{

    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function getLogin() // Obtener la vista de Login
    {
        $this->presenter->render("view/LoginView.mustache");
    }

    public function getRegister()   // Obtener la vista de Registro
    {
        $this->presenter->render("view/RegisterView.mustache");
    }

    public function postRegister() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $username = $_POST['username'];
        $password = $_POST['password'];
        $rep_password = $_POST['rep_password'];
        $surname = $_POST['surname'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $rutaProfilePic = $_FILES['profile_pic']['tmp_name'];
        $contenidoProfilePic = isset($rutaProfilePic) && $rutaProfilePic != '' ? file_get_contents($rutaProfilePic) : null; // Verifica si se proporciona una imagen
        $birth_year = $_POST['birth_year'];
        $gender = $_POST['gender'];
        $country = $_POST['country'];
        $city = $_POST['city'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        $hash = md5($username . $email . date("Y-m-d"));

        // Verifica si la imagen es nula o vacía
        if ($contenidoProfilePic === null || $rutaProfilePic == '') {
            $_SESSION['error'] = "Debe cargar una foto de perfil válida";
            $this->presenter->render("view/RegisterView.mustache", ['error' => $_SESSION['error']]); // Redirige de vuelta al formulario
            exit();
        }

        $result = $this->model->register($username, $password, $rep_password, $email, $name, $surname, $hash, $contenidoProfilePic, $birth_year, $gender, $country, $city, $latitude, $longitude);

        if ($result) {
            $hash = md5($username . $email . date("Y-m-d"));
            if ($this->model->sendEmail($email, $username, $hash)) {
                $this->presenter->render("view/RegisterSuccessView.mustache", ['link' => '/Users/validateEmail?hash=' . $hash]);
            } else {
                $_SESSION['error'] = "Error al enviar el correo de validación";
                $this->presenter->render("view/RegisterView.mustache", ['error' => $_SESSION['error']]);
            }
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar al usuario";
            $this->presenter->render("view/RegisterView.mustache", ['error' => $_SESSION['error']]);
            exit();
        }
    }

    public function validateEmail() {
        if (isset($_GET['hash'])) {
            $hash = $_GET['hash'];
            $this->model->setEmailValidated($hash);
            $data = array("message" => "Su correo ha sido validado exitosamente.");
            }
        $this->presenter->render("view/LoginView.mustache", $data);
    }

    public function postLogin()  // Procesar el login
    {
        $usernameOrEmail = $_POST['username'];
        $password = $_POST['password'];
        $emailValidado = $this->model->getEmailValidado($usernameOrEmail);

        if ($emailValidado != 1) {
            $_SESSION['error'] = "Debe validar su correo electrónico para poder iniciar sesión";
            $this->presenter->render("view/LoginView.mustache", ['error' => $_SESSION['error']]);
        } else {
            $user = $this->model->login($usernameOrEmail, $password);

            if ($user) {
                $_SESSION['user'] = $user;
                $username = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
                header("Location: /Home");
            } else {
                $_SESSION['error'] = "Usuario o contraseña incorrectos";
                $this->presenter->render("view/LoginView.mustache", ['error' => $_SESSION['error']]);
            }
        }
    }

    public function getProfile()  // Obtener la vista de Perfil
    {
        $username = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'][0]['username'] : null;
        $user = $this->model->getUserByUsername($username);
        $user[0]['profile_pic'] = base64_encode($user[0]['profile_pic']); // Convertir la imagen a base64 para mostrarla en la vista
        $this->presenter->render("view/ProfileView.mustache", ['user' => $user]);
    }

    public function logOut()  // Procesar el logout
    {
        session_destroy();
        header("Location: /Users/getLogin");
    }

    // Métodos para el panel de administración

    public function getAdminDashboard()
    {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        // Obtener las fechas desde la solicitud GET
        $fecha_inicio = $_GET['fecha_inicio'];
        $fecha_fin = $_GET['fecha_fin'];
        $_SESSION['fecha_inicio'] = $fecha_inicio;
        $_SESSION['fecha_fin'] = $fecha_fin;

        // Obtener las estadísticas necesarias
        $cantidadJugadores = $this->model->getCantidadJugadores($fecha_inicio, $fecha_fin);
        $cantidadPartidas = $this->model->getCantidadPartidas($fecha_inicio, $fecha_fin);
        $cantidadPreguntas = $this->model->getCantidadPreguntas();
        $cantidadPreguntasCreadas = $this->model->getCantidadPreguntasCreadas($fecha_inicio, $fecha_fin);
        $usuariosPorPais = $this->model->getCantidadUsuariosPorPais($fecha_inicio, $fecha_fin);
        $usuariosPorSexo = $this->model->getCantidadUsuariosPorSexo($fecha_inicio, $fecha_fin);
        $usuariosPorGrupoEdad = $this->model->getCantidadUsuariosPorGrupoEdad($fecha_inicio, $fecha_fin);

        // Renderizar la vista del panel de administración con los datos obtenidos
        $this->presenter->render("view/AdminDashboardView.mustache", [
            'cantidadJugadores' => $cantidadJugadores,
            'cantidadPartidas' => $cantidadPartidas,
            'cantidadPreguntas' => $cantidadPreguntas,
            'cantidadPreguntasCreadas' => $cantidadPreguntasCreadas,
            'usuariosPorPais' => $usuariosPorPais,
            'usuariosPorSexo' => $usuariosPorSexo,
            'usuariosPorGrupoEdad' => $usuariosPorGrupoEdad
        ]);

    }

    public function getUsuariosNuevos()
    {
        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        // Obtener las fechas desde la solicitud GET
        $fecha_inicio = $_GET['fecha_inicio'];
        $fecha_fin = $_GET['fecha_fin'];
        $_SESSION['fecha_inicio'] = $fecha_inicio;
        $_SESSION['fecha_fin'] = $fecha_fin;


        // Llamar al método del modelo para obtener la cantidad de usuarios nuevos
        $cantidadUsuariosNuevos = $this->model->getCantidadUsuariosNuevos($fecha_inicio, $fecha_fin);

        // Renderizar la vista con los resultados obtenidos
        $this->presenter->render("view/UsuariosNuevosView.mustache", ['cantidadUsuariosNuevos' => $cantidadUsuariosNuevos]);
    }

    public function mostrarPorcentajeAciertos() {

        // Obtener las fechas desde la solicitud GET
        $fecha_inicio = $_GET['fecha_inicio'];
        $fecha_fin = $_GET['fecha_fin'];
        $_SESSION['fecha_inicio'] = $fecha_inicio;
        $_SESSION['fecha_fin'] = $fecha_fin;

        $datosJugadores = $this->model->getDatosJugadoresConPorcentajeAciertos($fecha_inicio, $fecha_fin);
        $this->presenter->render("view/PorcentajeAciertosView.mustache", ["datosJugadores" => $datosJugadores]);
    }

    public function exportarPorcentajeAciertos() {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $fecha_inicio = $_SESSION['fecha_inicio'];
        $fecha_fin = $_SESSION['fecha_fin'];

        $datosJugadores = $this->model->getDatosJugadoresConPorcentajeAciertos($fecha_inicio, $fecha_fin);

        // Incluir el archivo de ayuda
        require_once 'helper\porcentajeDeAciertos.php';

        // Crear un nuevo documento PDF
        $pdf = new PdfHelper();

        // Agregar una página
        $pdf->AddPage();

        // Establecer la fuente
        $pdf->SetFont('Arial', 'B', 9);

        // Agregar la cabecera de la tabla
        $pdf->Cell(10, 10, 'ID', 1);
        $pdf->Cell(60, 10, 'Nombre de Usuario', 1);
        $pdf->Cell(40, 10, 'Preguntas Jugadas', 1);
        $pdf->Cell(40, 10, 'Preguntas Acertadas', 1);
        $pdf->Cell(40, 10, 'Porcentaje de Aciertos', 1);
        $pdf->Ln(); // Nueva línea

        // Agregar las filas de la tabla
        foreach ($datosJugadores as $jugador) {
            $pdf->Cell(10, 10, $jugador['_ID'], 1);
            $pdf->Cell(60, 10, $jugador['USERNAME'], 1);
            $pdf->Cell(40, 10, $jugador['PREGUNTAS_JUGADAS'], 1);
            $pdf->Cell(40, 10, $jugador['PREGUNTAS_ACERTADAS'], 1);
            $pdf->Cell(40, 10, $jugador['PORCENTAJE_ACIERTOS'] . '%', 1);
            $pdf->Ln(); // Nueva línea
        }

        // Enviar el documento PDF al navegador
        $pdf->Output();
    }

    public function exportarAdminDashboard() {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $fecha_inicio = $_SESSION['fecha_inicio'];
        $fecha_fin = $_SESSION['fecha_fin'];

        $cantidadJugadores = $this->model->getCantidadJugadores($fecha_inicio, $fecha_fin);
        $cantidadPartidas = $this->model->getCantidadPartidas($fecha_inicio, $fecha_fin);
        $cantidadPreguntas = $this->model->getCantidadPreguntas();
        $cantidadPreguntasCreadas = $this->model->getCantidadPreguntasCreadas($fecha_inicio, $fecha_fin);
        $usuariosPorPais = $this->model->getCantidadUsuariosPorPais($fecha_inicio, $fecha_fin);
        $usuariosPorSexo = $this->model->getCantidadUsuariosPorSexo($fecha_inicio, $fecha_fin);
        $usuariosPorGrupoEdad = $this->model->getCantidadUsuariosPorGrupoEdad($fecha_inicio, $fecha_fin);

        // Incluir el archivo de ayuda
        require_once 'helper\adminDashboard.php';

        // Crear un nuevo documento PDF
        $pdf = new PdfHelper();

        // Agregar una página
        $pdf->AddPage();

        // Establecer la fuente
        $pdf->SetFont('Arial', 'B', 12);

        // Agregar la cabecera de la tabla
        $pdf->Cell(42, 10, 'Total de Jugadores:');
        $pdf->Cell(60, 10, $cantidadJugadores);
        $pdf->Ln();
        $pdf->Cell(37, 10, 'Total de Partidas:');
        $pdf->Cell(60, 10, $cantidadPartidas);
        $pdf->Ln();
        $pdf->Cell(41, 10, 'Total de Preguntas:');
        $pdf->Cell(60, 10, $cantidadPreguntas);
        $pdf->Ln();
        $pdf->Cell(60, 10, 'Total de Preguntas Creadas:');
        $pdf->Cell(60, 10, $cantidadPreguntasCreadas);
        $pdf->Ln();

        $pdf->Cell(35, 10, 'Usuarios por Pais:');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 9);


        foreach ($usuariosPorPais as $pais) {
            $pdf->Cell(60, 10, $pais['COUNTRY'] . ': ' . $pais['total_usuarios']);
            $pdf->Ln();
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(35, 10, 'Usuarios por Sexo:');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 9);


        foreach ($usuariosPorSexo as $sexo) {
            $pdf->Cell(60, 10, $sexo['GENDER'] . ': ' . $sexo['total_usuarios']);
            $pdf->Ln();
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(35, 10, 'Usuarios por Grupo de Edad:');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 9);


        foreach ($usuariosPorGrupoEdad as $grupoEdad) {
            $pdf->Cell(60, 10, $grupoEdad['grupo_edad'] . ': ' . $grupoEdad['total_usuarios']);
            $pdf->Ln();
        }


        // Enviar el documento PDF al navegador
        $pdf->Output();
    }

    public function exportarUsuariosNuevos(){

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $fecha_inicio = $_SESSION['fecha_inicio'];
        $fecha_fin = $_SESSION['fecha_fin'];

        $cantidadUsuariosNuevos = $this->model->getCantidadUsuariosNuevos($fecha_inicio, $fecha_fin);

        // Incluir el archivo de ayuda
        require_once 'helper/usuariosNuevos.php';

        // Crear un nuevo documento PDF
        $pdf = new PdfHelper();

        // Agregar una página
        $pdf->AddPage();

        // Establecer la fuente
        $pdf->SetFont('Arial', 'B', 12);

        // Agregar la cabecera de la tabla
        $pdf->Cell(60, 10, 'Total de Usuarios Nuevos:');
        $pdf->Cell(60, 10, $cantidadUsuariosNuevos);
        $pdf->Ln();

        // Enviar el documento PDF al navegador
        $pdf->Output();
    }

    public function adminGraficos()
    {

        $roleId = isset($_SESSION['user']) ? $_SESSION['user'][0]['rol'] : null;

        if ($roleId!== 2) {
            // Redirigir a la página de inicio de sesión si no se tiene el rol adecuado
            header("Location: /Home");
            exit();
        }

        $finit = $_SESSION['fecha_inicio'];
        $fend = $_SESSION['fecha_fin'];
        $data = $this->getStatisticsForUsers($finit, $fend);

        // Obtén los datos de género de los usuarios
        $dataGenero = $data['usuariosPorSexo'];
        $dataCountry=$data['usuariosPorPais'];
        $dataAge=$data['usuariosPorEdad'];
        $data["imagePathGenre"]= $this->graficoUserByGenre($dataGenero);
        $data["imagePathCountry"]=$this->graficoUserByCountry($dataCountry);
        $data["imagePathAge"]=$this->graficoUserByAge($dataAge);
        $this->presenter->render("view/graficoView.mustache", $data);
    }
    private function graficoUserByAge($dataAge)
    {
        require_once('vendor/jpgraph/src/jpgraph.php');
        require_once('vendor/jpgraph/src/jpgraph_pie.php');

        $graph = new PieGraph(350, 250);

        $graph->title->Set("Usuarios por Edad");
        $graph->SetBox(true);

        $values = array_column($dataAge, 'total_usuarios');
        $labels = array_column($dataAge, 'grupo_edad');

        $p1 = new PiePlot($values);
        $p1->SetLegends($labels);
        $p1->ShowBorder();
        $p1->SetColor('black');
        $p1->SetSliceColors(array('#1E90FF', '#2E8B57', '#ADFF2F', '#DC143C', '#BA55D3'));

        $graph->Add($p1);

        $uniqueName = 'age_' . date('YmdHis') . '.png';
        $imagePath = 'public/images/' . $uniqueName;

        $graph->Stroke($imagePath);
        if (!file_exists($imagePath)) {
            $graph->Stroke($imagePath);
        }

        return $imagePath;
    }
    private function graficoUserByCountry($dataCountry)
    {

        require_once('vendor/jpgraph/src/jpgraph.php');
        require_once('vendor/jpgraph/src/jpgraph_pie.php');

        $graph = new PieGraph(350, 250);

        $graph->title->Set("Usuarios por País");
        $graph->SetBox(true);

        $values = array_column($dataCountry, 'total_usuarios');
        $labels = array_column($dataCountry, 'COUNTRY');

        $p1 = new PiePlot($values);
        $p1->SetLegends($labels);
        $p1->ShowBorder();
        $p1->SetColor('black');
        $p1->SetSliceColors(array('#1E90FF', '#2E8B57', '#ADFF2F', '#DC143C', '#BA55D3'));

        $graph->Add($p1);

        $uniqueName = 'country_' . date('YmdHis') . '.png';
        $imagePath = 'public/images/' . $uniqueName;

        $graph->Stroke($imagePath);
        if (!file_exists($imagePath)) {
            $graph->Stroke($imagePath);
        }

        return $imagePath;
    }
    private function graficoUserByGenre($dataGenero)
    {

        require_once('vendor/jpgraph/src/jpgraph.php');
        require_once('vendor/jpgraph/src/jpgraph_pie.php');

        $graph = new PieGraph(350, 250);

        $graph->title->Set("Usuarios por género");
        $graph->SetBox(true);

        $values = array_column($dataGenero, 'total_usuarios');
        $labels = array_column($dataGenero, 'GENDER');

        $p1 = new PiePlot($values);
        $p1->SetLegends($labels);
        $p1->ShowBorder();
        $p1->SetColor('black');
        $p1->SetSliceColors(array('#1E90FF', '#2E8B57', '#ADFF2F', '#DC143C', '#BA55D3'));

        $graph->Add($p1);

        $uniqueName = 'genre_' . date('YmdHis') . '.png';
        $imagePath = 'public/images/' . $uniqueName;

        $graph->Stroke($imagePath);
        if (!file_exists($imagePath)) {
            $graph->Stroke($imagePath);
        }

        return $imagePath;
    }

    private function getStatisticsForUsers($finit, $fend)
    {
        $data = array(
            'usuariosPorEdad' => $this->model->getCantidadUsuariosPorGrupoEdad($finit, $fend),
            'usuariosPorSexo' => $this->model->getCantidadUsuariosPorSexo($finit, $fend),
            'usuariosPorPais' => $this->model->getCantidadUsuariosPorPais($finit, $fend)
        );
        if (empty($data['usuariosPorEdad']) && empty($data['usuariosPorSexo']) && empty($data['usuariosPorPais'])) {
            $data['empty'] = true;
        }
        return $data;
    }
}