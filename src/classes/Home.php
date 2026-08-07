<?php

class Home
{
    public function index()
    {
        global $smarty;

        $smarty->assign('template', 'home.html');
    }
}
