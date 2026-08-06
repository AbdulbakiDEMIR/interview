<?php

require_once 'home.php';

class Main
{
    public $router;

    public function __construct()
    {
        global $lang, $smarty;

        // DUZELTILDI: 'lang' key'i yoksa hata veriyordu. ?? operatoru eklendi. 
        // isset() fonskiyonunu da kullanabilirdim ancak ?? operatorunu tercih ettim.
        $lang = $_SESSION['lang'] ?? 'tr';

        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
        }

        require_once __DIR__ . "/../languages/{$lang}.php";

        $smarty = new Smarty\Smarty();
        $this->router = new \Bramus\Router\Router();

        $smarty->setTemplateDir('src/templates');

        // DUZELTILDI: Sabit '/tmp' yolu Windows ve farklı sunucu ortamlarında hata ürettiği için 
        // cross-platform uyumlu proje içi 'templates_c' dizini ile değiştirildi.
        $smarty->setCompileDir(__DIR__ . '/../../templates_c');

        $smarty->assign('LANG', $lang);
        $smarty->assign('langs', ['tr' => 'Türkçe', 'en' => 'English']);
    }

    public function run()
    {
        global $smarty;

        $this->router->get('/', function () {
            $home = new Home();
            $home->index();
        });

        $this->router->run();
        $smarty->display('index.html');
    }
}
