<?php

namespace App;

use Arris\AppLogger;
use Arris\Presenter\Template;
use PDO;
use Psr\Log\LoggerInterface;

class AbstractClass
{
    public App $app;

    public PDO $pdo;

    public Template $template;

    public array $options = [];

    public $tables;

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $logger;

    public bool $is_internal_request;

    public function __construct($options = [], LoggerInterface $logger = null)
    {
        $this->app = App::factory();
        $this->pdo = $this->app->getService('pdo');
        $this->template = $this->app->getService(Template::class);
        $this->logger = AppLogger::scope('main');

        $this->options = $options;

        // $this->tables = new DBConfigTables();

        $this->is_internal_request = array_key_exists('mode', $_GET) && $_GET['mode'] == 'internal';
    }

}