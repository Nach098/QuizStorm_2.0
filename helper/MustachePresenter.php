<?php

class MustachePresenter{
    private $mustache;
    private $partialsPathLoader;

    public function __construct($partialsPathLoader){
        Mustache_Autoloader::register();
        $this->mustache = new Mustache_Engine(
            array(
                'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsPathLoader )
            ));
        $this->partialsPathLoader = $partialsPathLoader;
    }

    private function getCommonData($data = []) {
        $commonData = [
            'isLoggedIn' => isset($_SESSION['user']),
            // Aquí puedes agregar más datos que quieras incluir en cada renderizado
        ];

        return array_merge($commonData, $data);
    }

    public function render($contentFile , $data = array() ){
        try {
            echo  $this->generateHtml($contentFile, $this->getCommonData($data));
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function generateHtml($contentFile, $data = array()) {

        $contentAsString = file_get_contents(  $this->partialsPathLoader .'/header.mustache');
        $contentAsString .= file_get_contents( $contentFile );
        $contentAsString .= file_get_contents($this->partialsPathLoader . '/footer.mustache');

        return $this->mustache->render($contentAsString, $this->getCommonData($data));
    }
}