<?php

namespace Livemap\Controllers;

use Livemap\AbstractClass;
use Psr\Log\LoggerInterface;

class ProjectsController extends AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
        $this->template->setTemplate("_project.tpl");
    }

    public function view_project($id)
    {
        $this->template->assign("project", [
            'id'    =>  $id
        ]);
    }

}