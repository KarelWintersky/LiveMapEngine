<?php
/**
 * User: Arris
 * Date: 01.07.2017, time: 15:06
 */

class UnitPrototype {
    private $template;

    public function __construct( )
    {

    }

    public function run()
    {
    }

    public function headers()
    {

    }

    public function content()
    {
        if (method_exists($this->template, 'render'))
            return $this->template->render();
    }
}
 
