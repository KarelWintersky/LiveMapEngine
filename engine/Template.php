<?php

/*
Как использовать?

== Работаем с глобальным шаблоном ==

А) через инклюд подшаблона

`{include file=$inner_template}`

// пробрасываем переменные, которые используются в banners_left.tpl
Template::assign("inner_template", "frontpage/banners_left.tpl");

Б) через ассайн переменной в подшаблоне

Template:assing("inner_content", Template::render("frontpage/banners_left.tpl");

== Работаем с локальным шаблоном ==

В) через прямой вывод шаблона в stdout

Задаем Global Template как NULL (не устанавливаем его)

echo Template::render("banners.tpl");

после этого, после диспетчера делаем Template::render() - оно не печатает НИЧЕГО

Г) Через глобальный шаблон, устанавливаем его

Template::setGlobalTemplate("banners.tpl")

после диспетчера

Template::render()

 */
namespace Livemap;

use Arris\Core\Dot;
use Exception;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Smarty;
use function Arris\setOption;

class Template
{
    const VERSION = "3.1";
    /**
     * @var Logger
     */
    private static $logger;

    /**
     * @var
     */
    private static $JSON = null;

    /**
     * @var Smarty
     */
    private static $smarty = null;

    /**
     * @var string
     */
    private static $response = null;

    /**
     * @var string
     */
    private static $template_global_file = null;

    /**
     * Разделитель для элементов заголовка (UNUSED)
     * @var string
     */
    private static $title_delimeter;

    /**
     * MIME-тип по умолчанию
     *
     * @var string
     */
    private static $mime_type = null;

    /**
     *
     * @param $smarty_instance
     * @param array $options
     * @param null $logger
     * @throws Exception
     */
    public static function init($smarty_instance, $options = [], $logger = null)
    {
        if (is_null($smarty_instance) || !($smarty_instance instanceof Smarty)) {
            throw new Exception("Can't initialize template with null renderer");
        }

        self::$smarty = $smarty_instance;

        self::$template_global_file = $options[0] ?? null;

        self::$title_delimeter = " " . setOption($options, 'title_delimeter', '&#8250;') . " ";
        
        self::$JSON = new Dot();

        self::$logger
            = $logger instanceof Logger
            ? $logger
            : new NullLogger();
    }

    /**
     * Устанавливает имя файла главного шаблона
     *
     * @param null $template_file
     * @throws Exception
     */
    public static function setGlobalTemplate($template_file = null)
    {
        if (is_null(self::$smarty)) {
            throw new Exception("Template is not initialized");
        }

        if (is_null( $template_file )) {
            throw new Exception("Can't set empty global file template");
        }

        self::$template_global_file = $template_file;
    }


    /**
     * @param $keys
     * @param null $value
     * @param bool $nocache
     */
    public static function assign($keys, $value = null, $nocache = false)
    {
        self::$smarty->assign($keys, $value, $nocache);
        self::$JSON[ $keys ] = $value;
    }

    /**
     * Рендерит локальный или глобальный шаблон. Всегда возвращает результат, который нужно печатать.
     *
     * @param null $template_file
     * @param bool $clean
     * @return string
     * @throws Exception
     */
    public static function render($template_file = null, $clean = false)
    {
        if (is_null(self::$smarty)) {
            throw new Exception("Template is not initialized");
        }

        $result = null;

        if (!is_null($template_file)) {
            $return = self::$smarty->fetch( $template_file );
        } else {
            // позволяет не делать ничего если файл глобального шаблона NULL
            $return = is_null( self::$template_global_file) ? '' : self::$smarty->fetch( self::$template_global_file);

            /*if (is_null( self::$template_global_file ))
                throw new Exception("Can't render empty global file template");

            $return = self::$smarty->fetch( self::$template_global_file );*/
        }

        if ($clean) {
            self::$smarty->clear_all_assign();
        }

        if (self::$mime_type) {
            header( "Content-Type: ".self::$mime_type );
        }

        return $return;
    }


    /**
     * Присваивает JSON-датасет по сложным правилам
     * @todo: ПЕРЕПИСАТЬ
     *
     * @todo: если передан 1 аргумент и это асс.массив - надо передать его в конструктор Adbar\Dot
     * Если передано одно значение - его нужно передать в конструктор
     * Если передано два значения (@todo обратная несовместимость) - данные -> для ключа
     *
     * Assign JSON dataset
     * @param $key
     * @param null $value
     */
    public static function assignJSON($key = null, $value = null)
    {
        if (is_null(self::$JSON)) {
            self::$JSON = new Dot();
        }

        self::$JSON[ $key ] = $value;
    }

    /**
     * Устанавливает в self::JSON переменную с указанным значением
     * Используется для простой (или последовательной) установки значений в JSON Dataset
     *
     * @param $key
     * @param null $value
     */
    public static function setJSON($key, $value = null)
    {
        if (is_null(self::$JSON)) {
            self::$JSON = new Dot();
        }

        // @todo KW : NEW
        /*if (is_null($value)) {
            self::$JSON = new \Adbar\Dot($key);
        } else {
            self::$JSON[ $key ] = $value;
        }*/


        self::$JSON[ $key ] = $value;
    }

    /**
     * Возвращает JSON-датасет
     *
     * @return false|string
     */
    public static function renderJSON()
    {
        return json_encode(self::$JSON);
    }

    /**
     * Устанавливает MIME-тип, который выводится в хедере Content-Type
     *
     * text/html по умолчанию, его переопределять не надо
     *
     * @param string $mime_type
     */
    public static function setContentType($mime_type = null)
    {
        self::$mime_type = $mime_type;
    }

}