<?php

namespace App\Controllers;

use App\AbstractClass;
use Psr\Log\LoggerInterface;

class JSController extends AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
    }


}