<?php
/**
 * User: Arris
 *
 * Class MapView
 * Namespace: LME\Units
 *
 * Date: 14.10.2018, time: 20:24
 */

namespace LME\Units;

/**
 * Class MapView
 * @package LME\Units
 *
 * Реализует методы отображения различных видов карт и данных по тайлам
 */
class MapView
{

    /**
     * Отрисовывает карту классического полноэкранного типа
     *
     * @param $map_alias
     * @return string
     */
    public function view_map_fullscreen($map_alias) {
        return "Show fullscreen map <strong>{$map_alias}</strong> as MAP";
    }

    /**
     * Отрисовывает карту в ифрейме: попап сделан через колорбокс по середине экрана
     *
     * @param $map_alias
     * @return string
     */
    public function view_map_iframe($map_alias) {
        return "Show fullscreen map <strong>{$map_alias}</strong> in IFRAME";
    }


    /**
     * Отрисовывает in-folio карту - без информационных окон
     *
     * @param $map_alias
     * @return string
     */
    public function view_map_folio($map_alias) {
        return "Show fullscreen map <strong>{$map_alias}</strong> as FOLIO";
    }


}