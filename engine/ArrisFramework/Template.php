<?php

namespace Arris;

/**
 * User: Karel Wintersky <karel.wintersky@gmail.com>
 *
 * Class Template
 *
 * Date: 19.09.2018, time: 20:41
 */
class Template
{
    const VERSION = '1.3';

    const ALLOWED_RENDERS = array('html', 'json', 'null');

    const GLUE = '/';

    public $template_file;
    public $template_path;
    public $template_data;
    public $template_real_filepath;

    private $render_type;

    private $cache = [];

    /**
     * @param $file
     * @param $path
     * @param $render_type
     */
    public function __construct( $file , $path , $render_type = 'html')
    {
        $this->template_file = $file;
        $this->template_path = $path;

        $this->template_real_filepath = preg_replace('/^\$/', $_SERVER['DOCUMENT_ROOT'], $path) . '/' . $file;

        $this->template_data = [];
        $this->http_status = 200;
        $this->render_type = 'html';
    }

    public function setRenderType($type)
    {
        return in_array($type, $this::ALLOWED_RENDERS)
            ? $this->render_type = $type
            : "Renderer `{$type}` not allowed.";
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->template_path = $path;
    }


    /**
     * @param $file
     */
    public function setFile( $file )
    {
        $this->template_file = $file;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        // Check if already cached
        if (isset($this->cache[$key])) {
            return true;
        }

        $segments = explode($this::GLUE, $key);
        $root = $this->template_data;

        // nested case
        foreach ($segments as $segment) {
            if (array_key_exists($segment, $root)) {
                $root = $root[$segment];
                continue;
            } else {
                return false;
            }
        }

        // Set cache for the given key
        $this->cache[$key] = $root;
        return true;
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $segs = explode($this::GLUE, $key);

        // remove first element if empty: '/body' === 'body'
        if ($segs[0] == "") unset($segs[0]);

        $root = &$this->template_data;

        $cacheKey = '';
        // Look for the key, creating nested keys if needed
        while ($part = array_shift($segs)) {
            if ($cacheKey != '') {
                $cacheKey .= $this::GLUE;
            }
            $cacheKey .= $part;

            var_dump($cacheKey);

            if (!isset($root[$part]) && count($segs)) {
                $root[$part] = array();
            }
            $root = &$root[$part];
            //Unset all old nested cache
            if (isset($this->cache[$cacheKey])) {
                unset($this->cache[$cacheKey]);
            }
            //Unset all old nested cache in case of array
            if (count($segs) == 0) {
                foreach ($this->cache as $cacheLocalKey => $cacheValue) {
                    if (substr($cacheLocalKey, 0, strlen($cacheKey)) === $cacheKey) {
                        unset($this->cache[$cacheLocalKey]);
                    }
                }
            }
        }

        // Assign value at target node
        $this->cache[$key] = $root = $value;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        }
        return $default;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->template_data;
    }

    /**
     *
     * @param string $type
     * @return string
     */
    public function render($type = 'html')
    {
        $this->render_type = $type;

        if ($this->render_type === 'json') {
            return json_encode( $this->template_data );
        }

        if ($this->render_type === 'html') {

            if ($this->template_path === '' && $this->template_file === '') return false;

            if (!is_readable( $this->template_real_filepath )) {
                return "[ERROR] : {$this->template_real_filepath} not readable ";
            }

            return \websun_parse_template_path( $this->template_data, $this->template_file, $this->template_path );
        }

        return false;
    }

    public function test()
    {
        $t = new Template('', '');

        $t->set('/body', [
            'a' =>  '1',
            'b' =>  '2'
        ]);

        $t->set('head/meta', [
            'c' =>  '3',
            'd' =>  '4'
        ]);

        var_dump($t->all());


    }



}