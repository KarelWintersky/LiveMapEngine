#!/usr/bin/php
<?php
/**
 * postinst.php
 *
 * Postinst script, генерирует /robots.txt
 */
use Arris\App;
use Arris\CLIConsole;
use Arris\Path;
use Dotenv\Dotenv;

define('PATH_ROOT', dirname(__DIR__, 1));
define('__PATH_CONFIG__', '/etc/arris/livemap/');

$cli_options = getopt('h', ['make:robots', 'help']);
$options = [
    'help'          =>  array_key_exists('help', $cli_options),
    'starttime'     =>  microtime(true),

    'make:robots'   =>  array_key_exists('make:robots', $cli_options),
];

require_once PATH_ROOT . '/vendor/autoload.php';

try {
    foreach (['site.conf' ] as $file) { Dotenv::create( PATH_CONFIG, $file )->load(); }

    if (empty($cli_options) || $options['help']) {
        CLIConsole::say(<<<HOWTOUSE
Possible settings:
  <font color='yellow'>--make:robots</font>      - create robots.txt
  <font color='yellow'>--clear:smarty</font>     - clear Smarty Cache
  <font color='yellow'>--help</font>             - this help
HOWTOUSE
        );
        die (2);
    }

    /**
     * @var PDO $PDO
     */
    $PDO = App::factory()->get('pdo');

    $_path_install = Path::create( getenv('PATH.INSTALL'));

    if ($options['make:robots']) {
        $host = getenv('DOMAIN');
        $fqdn = getenv('DOMAIN.FQDN');

        $source = $_path_install->join('public')->join('templates')->join('_system')->joinName('robots.txt')->toString();
        $target = $_path_install->join('public')->joinName('robots.txt')->toString();

        $template = file_get_contents($source);

        if (empty($template)) {
            CLIConsole::say(" <font color='red'>Error:</font> template file `{$source}` NOT FOUND. ");
            die(129);
        }

        $template = str_replace(['%%fqdn%%', '%%host%%'], [$fqdn, $host], $template);

        $f = fopen($target, 'w+');
        fwrite($f, $template);
        fclose($f);
        CLIConsole::say(" <font color='green'>{$target} file generated</font>");
    }

} catch (Exception $e) {
    CLIConsole::say(" <font color='red'>{$e->getMessage()}</font>");
    die(3);
}









