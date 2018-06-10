<?php

if (!class_exists('websun')) require_once(__ROOT__ . '/engine/thirdparty/websun.php');

/**
 * Class Template
 */
class Template {
    private $render_type;

    const ALLOWED_RENDERS = array('html', 'json', 'null');

    public $template_file;
    public $template_path;
    public $template_data;
    private $http_headers = array();
    private $http_status;

    /**
     * @param $file
     * @param $path
     */
    public function __construct( $file , $path )
    {
        $this->template_file = $file;
        $this->template_path = $path;
        $this->data = array();
        $this->http_status = 200;
        $this->render_type = 'html';
    }

    /**
     * @param string $type
     */
    public function setRender( $type = 'html' )
    {
        if ( in_array($type, $this::ALLOWED_RENDERS))
            $this->render_type = $type;
    }

    /**
     * @return mixed|null|string
     */
    public function render()
    {
        if ($this->render_type === 'html') {

            if ($this->template_path === '' && $this->template_file === '') return false;

            return websun_parse_template_path( $this->template_data, $this->template_file, $this->template_path );

        } elseif ($this->render_type === 'json') {
            return json_encode( $this->template_data );
        } else return null;
    }

    /**
     * @param $path
     */
    public function setTemplatePath( $path )
    {
        $this->template_path = $path;
    }

    /**
     * @param $file
     */
    public function setTemplateFile( $file )
    {
        $this->template_file = $file;
    }

    /**
     * @param $path
     * @param $value
     * @return bool
     */
    public function set($path, $value)
    {
        $result = &$this->path_to_array( $path );

        if ($path != '/') {
            $result = $value;
        } else {
            if (!is_array($value)) {
                return false;
            } else {
                $result = array_merge_recursive($result, $value);
            }
        }
    }

    /**
     * @param $path
     * @return array
     */
    public function get( $path )
    {
        return $this->path_to_array( $path );
    }


    /* === PRIVATE === */

    /**
     *
     * @param $path
     * @return array
     */
    private function &path_to_array($path)
    {
        $path_array = explode('/', $path);
        $result = &$this->template_data;

        foreach ($path_array as $value) {
            if (!empty($value)) {
                if (!is_array($result)) {
                    $result[$value] = array();
                }
                $result =& $result[$value];
            }
        }
        return $result;
    }

    /* === TEST === */

    /**
     * @return mixed
     */
    public function test()
    {
        return ( $this->template_data );
    }

    /**
     *
     */
    public function dump()
    {
        var_dump( $this->template_data );
    }

    /**
     *
     */
    public function debug()
    {
        var_dump( 'Called debug() ');
        var_dump( $this->template_file );
        var_dump( $this->template_data );
        var_dump( $this->template_path );
    }

    /**
     *
     */
    public function sendHTTPHeader()
    {

    }

    /**
     * @param $status
     */
    public function setHTTPStatus( $status )
    {

    }

}

