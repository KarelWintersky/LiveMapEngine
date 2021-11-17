<?php
/**
 * User: Arris
 *
 * Class Map
 * Namespace: LME\LivemapFramework
 *
 * Date: 15.10.2018, time: 6:47
 */

namespace LivemapFramework;

use Arris\Config;
use Arris\DB;
use Arris\Auth;
use Arris\AppLogger;
use LivemapFramework\ACL;

interface MapManagerInterface {

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
    public function getRegionRevisions($map_alias, $region_id, $revisions_depth = 0);

    /**
     * Проходит по массиву регионов и провеяет доступность региона для текущего пользователя.
     *
     * @param $regions_list
     * @param $map_alias
     * @return array
     * @throws \Exception
     */
    public function checkRegionsVisibleByCurrentUser($regions_list, $map_alias);

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
     * @param string $ids_list
     * @return array
     */
    public function getRegionsWithInfo($map_alias, $ids_list = '');

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

/**
 * Реализует методы работы с контентом карты.
 * Возможно, класс называется некорректно и будет изменен в результате рефакторинга
 *
 * Этот класс находится в неймспейсе LivemapFramework, так как содержит методы фреймворка, а не конкретной
 * реализации проекта. Сюда следует относить все методы, не относящиеся к моделям конкретного проекта.
 *
 * Class MapManager
 * @package LME\LivemapFramework
 */
class MapManager
{
    public function __construct()
    {
    }

    public function getMapRegionData($map_alias, $id_region)
    {
        $table = DB::getTablePrefix() . Config::get('tables/map_data_regions');

        $user_id = Auth::getCurrentUser()['uid'];

        $role = ACL::getRole($user_id, $map_alias);
        $role_can_edit = ACL::isValidRole( $role, 'EDITOR');

        $info = [];

        $query = "
            SELECT `title`, `content`, `content_restricted`, `edit_date`, `is_publicity`, `is_excludelists`
            FROM {$table}
            WHERE
                id_region     = :id_region
            AND alias_map     = :alias_map
            ORDER BY edit_date DESC
            LIMIT 1
            ";

        try {
            $sth = DB::getConnection()->prepare($query);
            $sth->execute([
                'id_region' =>  $id_region,
                'alias_map' =>  $map_alias
            ]);
            $row = $sth->fetch();

            if ($row) {
                $info = [
                    'is_present'        =>  1,
                    'title'             =>  $row['title'],
                    'edit_date'         =>  $row['edit_date'],
                    'can_edit'          =>  $role_can_edit,
                    'is_exludelists'    =>  $row['is_excludelists'],
                    'is_publicity'      =>  $row['is_publicity'],
                    'content'           =>  '',
                    'content_restricted'    =>  $row['content_restricted']
                ];

                if (ACL::isValidRole( $role, $row['is_publicity'])) {
                    $info['content'] = $row['content'];
                } else {
                    $info['content'] = $row['content_restricted'] ?? "Доступ ограничен"; // "Доступ ограничен" - брать из конфига карты/слоя
                }

            } else {
                $info = array(
                    'is_present'    =>  0,
                    'title'         =>  $id_region,
                    'content'       =>  '',
                    'can_edit'      =>  $role_can_edit
                );
            }
        } catch (\Exception | \PDOException $e) {
            AppLogger::error(__METHOD__ . " reports : " . $e->getMessage());
        }

        return $info;
    }

    public function getRegionRevisions($map_alias, $region_id, $revisions_depth = 0)
    {
        $table_regions = DB::getTablePrefix() . Config::get('tables/map_data_regions');
        $table_users = Auth::get('table_users');

        $query_limit = ($revisions_depth != 0) ? " LIMIT {$revisions_depth} " : "";

        $query = "
SELECT
  table_data.id_region AS id_region,
  table_data.edit_date AS edit_date,
  table_users.username AS edit_name,
  INET_NTOA(`edit_ipv4`) AS ipv4,
  table_data.title AS title,
  table_data.edit_comment AS edit_comment
FROM
  {$table_regions} AS table_data,
  {$table_users} AS table_users
WHERE
    alias_map = :alias_map
AND id_region = :id_region
AND table_data.edit_whois = table_users.id
ORDER BY edit_date {$query_limit};
        ";

        try {
            $sth = DB::getConnection()->prepare($query_limit);

            $sth->execute([
                'alias_map' =>  $map_alias,
                'id_region' =>  $region_id
            ]);

            $all_revisions = $sth->fetchAll();
        } catch (\Exception | \PDOException $e) {
            AppLogger::error(__METHOD__ . " reports : " . $e->getMessage());
            $all_revisions = FALSE;
        }

        return $all_revisions;
    }

    public static function removeExcludedFromRegionsList($regions_list) {
        return array_filter($regions_list, function($row) {
            return !!($row['is_excludelists'] == 'N');
        });
    }

    public static function convertRegionsWithInfo_to_IDs_String($regions_array) {
        return implode(", ", array_map(function($item){
            return "'" . $item['id_region'] . "'";
        }, $regions_array));

    }

    public function checkRegionsVisibleByCurrentUser($regions_list, $map_alias)
    {
        $user_id = Auth::getCurrentUser()['uid'];
        $current_role = ACL::getRole($user_id, $map_alias);

        return array_filter($regions_list, function ($row) use ($current_role){
            return !!( ACL::isValidRole($current_role, $row['is_publicity']));
        });
    }

    public function storeMapRegionData($data, $map_alias, $id_region)
    {
        $table = DB::getTablePrefix() . Config::get('tables/map_data_regions');

        $success = array(
            'state'     =>  FALSE,
            'message'   =>  ''
        );

        $query = "
        INSERT INTO {$table}
         (
         `id_map`, `alias_map`, `edit_whois`, `edit_ipv4`,
         `id_region`, `title`, `content`, `content_restricted`,
         `edit_comment`, `is_excludelists`, `is_publicity`
         )
         VALUES
         (
         :id_map, :alias_map, :edit_whois, :edit_ipv4,
         :id_region, :title, :content, :content_restricted,
         :edit_comment, :is_excludelists, :is_publicity
         )
        ";

        $data['edit_ipv4'] = ip2long(getIp());
        $data['alias_map'] = $map_alias;
        $data['id_region'] = $id_region;

        try {
            $sth = DB::getConnection()->prepare($query);
            $success['state'] = $sth->execute($data);

        } catch (\Exception | \PDOException $e) {
            $success['state'] = FALSE;
            $success['message'] = $e->getMessage();
            AppLogger::error(__METHOD__ . " reports : " . $e->getMessage());
        }
        return $success;
    }

    public function getRegionsWithInfo($map_alias, $ids_list = '')
    {
        $table = DB::getTablePrefix() . Config::get('tables/map_data_regions');

        if ($ids_list != '') {
            $in_subquery = "AND `id_region` IN ({$ids_list}) ";
        } else {
            $in_subquery = '';
        }

        $query_get_id = "
 
 SELECT id
 FROM `{$table}` AS `mdr1`
 WHERE `alias_map` = :alias_map AND
 `id` = ( SELECT MAX(id)
          FROM `{$table}` AS `mdr2`
          WHERE `mdr1`.`id_region` = `mdr2`.`id_region`
        )
 
 {$in_subquery}
 ORDER BY id_region
        ";

        if (__DEBUG__) AppLogger::debug('getRegionsWithInfo -> $query_get_id : ' . $query_get_id);

        $all_ids = [];
        $all_regions = [];

        try {
            $sth = DB::getConnection()->prepare($query_get_id);
            $sth->execute([
                'alias_map' =>  $map_alias
            ]);
            $all_ids = $sth->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($all_ids)) return [];

            if (__DEBUG__) AppLogger::debug('getRegionsWithInfo : $all_ids', $all_ids);

            $all_ids_string = implode(', ', $all_ids);

            $query_data = "
SELECT
    `id_region`, `title`, `edit_date`,
    `is_publicity`, `is_excludelists`,

    `region_stroke` AS `stroke`,
    `region_border_color` AS `borderColor`,
    `region_border_width` AS `borderWidth`,
    `region_border_opacity` AS `borderOpacity`,

    `region_fill` AS `fill`,
    `region_fill_color` AS `fillColor`,
    `region_fill_opacity` AS `fillOpacity`
    FROM {$table}
    WHERE `id` IN ({$all_ids_string})
        ";

            if (__DEBUG__) AppLogger::debug('getRegionsWithInfo -> $query_data', $query_data);

            $all_regions = [];

            $sth = DB::getConnection()->prepare($query_data);
            $sth->execute([
                'alias_map' =>  $map_alias
            ]);

            //@todo: HINT (преобразование PDO->fetchAll() в асс.массив, где индекс - значение определенного столбца каждой строки)
            array_map(function($row) use (&$all_regions) {
                $all_regions[ $row['id_region'] ] = $row;
            }, $sth->fetchAll());

            /*
            В оригинале этот код закомментирован. Вероятно, он реализован иначе

            $current_role = $this->ACL_getRole($map_alias);
            array_map(function($row) use (&$all_regions, $current_role) {
                // проверка прав: может ли текущий пользователь иметь инфу по этому региону?

                if ($this->ACL_isValidRole($current_role, $row['is_publicity'])) {
                    $all_regions[ $row['id_region'] ] = $row;
                }

                $all_regions[ $row['id_region'] ] = $row;

            }, $sth->fetchAll());*/


        } catch (\Exception | \PDOException $e) {

        }

        return $all_regions;
    }


}