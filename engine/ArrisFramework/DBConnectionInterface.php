<?php

namespace Arris;

/**
 * Interface DBConnectionInterface
 */
interface DBConnectionInterface
{
    /**
     * Predicted (early) initialization
     *
     * @param null $suffix
     * @param $config
     */
    public static function init($suffix, Array $config);

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = NULL): array;

    /**
     * Set connection config
     *
     * @param array $config
     * @param null $suffix
     */
    public static function setConfig(Array $config, $suffix = NULL);

    /**
     * Alias: get PDO connection
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getConnection($suffix = NULL): \PDO;

    /**
     * Set current connection key for internal calls === setContext() ?
     *
     * @param $suffix
     */
    public static function setConnection($suffix);

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getInstance($suffix = NULL):\PDO;

    /**
     * Get tables prefix for given connection
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix($suffix = NULL);

    /**
     *
     * @param $query
     * @param null $suffix
     * @return mixed
     */
    public static function query($query, $suffix = NULL);

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL);

    /**
     * Аналог rowcound, только дает возможность выбрать поле выборки и условие
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL);

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     */
    public static function getLastInsertId($suffix = NULL);

    /**
     * Build INSERT-query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset);

    /**
     * Build UPDATE query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '');

}
