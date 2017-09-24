<?php
/**
 * User: Arris
 * Date: 22.09.2017, time: 19:50
 */
if (php_sapi_name() !== 'cli') die();

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/classes/class.DBConnectionLite.php');
require_once (__ROOT__ . '/engine/classes/class.ParseSVG.php');
require_once (__ROOT__ . '/engine/classes/class.CLIConsole.php');

/* ==== CHECK ARGS ==== */
// hint message
CLIConsole::echo_status("<strong>SVG Map importer</strong><hr> ");

// echo help
if ($argc < 2) {
    CLIConsole::echo_status(<<<HINT
<font color='white'>{$argv[0]} <action> <sourcefile> </font>
Where <font color='yellow'><action></font> is one of:
 a - add
 u - update
 r - replace
and <font color='yellow'><sourcefile></font> is valid XML/SVG file.
HINT
 );
    die;
}

// test action
$arg_action = strtolower($argv[1]);
if (!strpbrk($arg_action, 'aru')) {
    CLIConsole::echo_status("Unknown action {$arg_action}, must be one of a(dd), u(pdate) or r(eplace)");
    die;
}

// check for sourcefile
if ($argc < 3) {
    CLIConsole::echo_status('Source file not defined. ');
    die;
}

// check sourcefile exists
$arg_source = $argv[2];
if (!file_exists($arg_source)) {
    CLIConsole::echo_status("File {$arg_source} not found.");
    die;
}

// check sourcefile is valid SVG
$svg_filecontent = file_get_contents($arg_source);
$svg = new ParseSVG($svg_filecontent);

if ($svg->svg_parsing_error) {
    $message = $svg->svg_parsing_error['message'];
    CLIConsole::echo_status("<font color='red'>[ERROR]</font> {$message}");
    die;
}

/* ==== MAIN ==== */

CLIConsole::echo_status("<strong>Parsing {$arg_source} file...</strong>" . PHP_EOL);

$image_dims_default = array(
    'width'     =>  0,
    'height'    =>  0,
    'ox'        =>  0,
    'oy'        =>  0
);



// Уточняем тип изображения карты - image / vector
$image_type = CLIConsole::readline('Уточните тип изображения [vector|bitmap] : ', '/^vector|bitmap$/');
CLIConsole::echo_status("<font color='cyan'>[NOTE]</font> Тип изображения принят как  `<strong>{$image_type}</strong>`");

// Имя слоя с разметкой регионов
$svg_layer_paths_name = CLIConsole::readline_default(PHP_EOL . "Уточните имя слоя с разметкой регионов [Paths] : ", "Paths");
CLIConsole::echo_status("<font color='cyan'>[NOTE]</font> Имя слоя с разметкой регионов установлено в `<strong>{$svg_layer_paths_name}</strong>`");

// имя слоя с информацией об изображениях (подложка карты)
if ($image_type === 'bitmap') {
    $svg_layer_images_name = CLIConsole::readline_default(PHP_EOL . "Уточните имя слоя с информацией об изображениях [Image] : ", "Image");
} else {
    $svg_layer_images_name = "";
}
CLIConsole::echo_status("<font color='cyan'>[NOTE]</font> Имя слоя с информацией об изображениях установлено в `<strong>{$svg_layer_images_name}</strong>`");

// парсим SVG
$svg->parse($svg_layer_paths_name, $svg_layer_images_name);


// выводим сообщение о связанном изображении
$image_dims_actual = $svg->getImageDefinition() ?? $image_dims_default;
if (!$image_dims_actual) {
    CLIConsole::echo_status(PHP_EOL . "<font color='yellow'>[INFO]</font> Файл разметки определен как не содержащий информации о связанном изображении. "
    ."Скорее всего он прилагается к векторному изображению. Нужно уточнить несколько значений: "
    .PHP_EOL);
} else {
    CLIConsole::echo_status(PHP_EOL . "<font color='yellow'>[INFO]</font> В файле разметки обнаружена информация о связанном изображении. "
        ."Давайте уточним несколько значений: "
        .PHP_EOL);
}
unset($image_dims_actual['xhref']);

// уточняем параметры связанного изображения (подложки)
array_walk($image_dims_actual, function($dim_value, $dim_id) use ($image_type){
    $msg = "Уточните значение <strong>{$dim_id}</strong> [$dim_value] : ";

    if ($dim_value !== 0) {
        $result = CLIConsole::readline_default($msg, $dim_value);
    } else {
        $result = CLIConsole::readline($msg, '/^\d+$/');
    }
    CLIConsole::echo_status("<font color='cyan'>[NOTE]</font> Значение `<strong>{$dim_id}</strong>` установлено в `<strong>{$result}</strong>`");

    return $result;
});


