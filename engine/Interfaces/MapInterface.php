<?php

namespace Livemap\Interfaces;

interface MapInterface
{
    /* === DYNAMIC METHODS === */

    public function getMapRegionData($map_alias, $id_region);

    /**
     * Получает массив ревизий региона для карты
     *
     * @param $map_alias
     * @param $region_id
     * @param int $revisions_depth
     * @return array|bool
     */
    public function getRegionRevisions($map_alias, $region_id, int $revisions_depth = 0);

    /**
     * Проходит по массиву регионов и провеяет доступность региона для текущего пользователя.
     *
     * @param $regions_list
     * @param $map_alias
     * @return array
     * @throws \Exception
     */
    public static function checkRegionsVisibleByCurrentUser($regions_list, $map_alias);

    // legacy
    /**
     * Сохраняет информацию по региону для SVG-карты.
     * Для сохранения данных по региону на тайловой карте нужна другая функция (похожая)
     * @param $region_data
     * @return array
     */
    public function storeMapRegionData($data, $map_alias, $id_region);

    /**
     * Возвращает массив регионов, имеющих информацию. Массив содержит id региона и название, отсортирован по id_region
     * Входные параметры: алиас проекта и алиас карты
     *
     * @param string $map_alias
     * @param string|array $ids_list
     * @return array
     */
    public static function getRegionsWithInfo($map_alias, $ids_list = '');

    /* === STATIC METHODS === */

    /**
     * Временная функция, фильтрующая массив регионов с данными.
     * Фильтр не проходят регионы, имеющие is_excludelists отличный от NEVER
     *
     * На самом деле фильтрацию должна выполнять js-функция на фронте (равно как и рисовать списки с регионами)
     *
     * @param $regions_list
     * @return array
     */
    public static function removeExcludedFromRegionsList($regions_list);

    /**
     * ????
     * @param $regions_array
     * @return string
     */
    public static function convertRegionsWithInfo_to_IDs_String($regions_array);

}