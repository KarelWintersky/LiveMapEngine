<?php

namespace Livemap\Units;

use Arris\Entity\Result;
use Arris\Helpers\Server;
use Livemap\App;
use Livemap\Exceptions\AccessDeniedException;
use PDO;
use Psr\Log\LoggerInterface;

class MapLegacy extends \Livemap\AbstractClass
{
    const allowed_cursors = [
        'auto', 'default', 'none', 'context-menu', 'help', 'pointer', 'progress', 'wait', 'cell', 'crosshair',
        'text', 'vertical-text', 'alias', 'copy', 'move', 'no-drop', 'not-allowed', 'all-scroll', 'col-resize',
        'row-resize', 'n-resize', 's-resize', 'e-resize', 'w-resize', 'ns-resize', 'ew-resize', 'ne-resize',
        'nw-resize', 'se-resize', 'sw-resize', 'nesw-resize', 'nwse-resize'
    ];
    const valid_view_modes = [
        'colorbox', 'tabled:colorbox',
        'folio',
        'iframe', 'iframe:colorbox',
        'wide:infobox>regionbox', 'wide:regionbox>infobox',
        'infobox>regionbox', 'regionbox>infobox'
    ];

    /**
     * @var array
     */
    public $mapRegionsWithInfo_IDS;

    /**
     * @var array
     */
    public $mapRegionWithInfoOrderByTitle;

    /**
     * @var array
     */
    public $mapRegionWithInfoOrderByDate;

    /**
     * @var array
     */
    public $mapRegionsWithInfo;

    /**
     * @var string
     */
    public $mapViewMode;

    /**
     * @var \stdClass
     */
    public $mapConfig;

    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        $this->mapConfig = new \stdClass();
    }

    public function loadConfig($map_alias)
    {
        $this->mapConfig = (new MapConfig($map_alias))->loadConfig()->getConfig();
    }

    public function loadMap($map_alias)
    {
        if (!empty($this->mapConfig->display->viewmode)) {
            $viewmode = $this->mapConfig->display->viewmode;
        }

        $viewmode = filter_array_for_allowed($_GET, 'viewmode', self::valid_view_modes, $viewmode);
        $viewmode = filter_array_for_allowed($_GET, 'view',     self::valid_view_modes, $viewmode);

        $this->mapViewMode = $viewmode;

        // извлекаем все регионы с информацией
        $this->mapRegionsWithInfo = self::getRegionsWithInfo( $map_alias, []);

        // фильтруем по доступности пользователю (is_publicity)
        //@todo
        // $this->mapRegionsWithInfo = Map::checkRegionsVisibleByCurrentUser($this->mapRegionsWithInfo, $map_alias);

        // фильтруем по visibility ????
        $this->mapRegionsWithInfo = MapLegacy::removeExcludedFromRegionsList($this->mapRegionsWithInfo);

        $this->mapRegionsWithInfo_IDS = self::convertRegionsWithInfo_to_IDs_String($this->mapRegionsWithInfo);

        $this->mapRegionWithInfoOrderByTitle = $this->mapRegionsWithInfo;
        usort($this->mapRegionWithInfoOrderByTitle, static function($value1, $value2){
            return ($value1['title'] > $value2['title']);
        });

        $this->mapRegionWithInfoOrderByDate = $this->mapRegionsWithInfo;
        usort($this->mapRegionWithInfoOrderByDate, static function($value1, $value2){
            return ($value1['edit_date'] > $value2['edit_date']);
        });
    }

    /**
     * Возвращает массив регионов, имеющих информацию. Массив содержит id региона и название, отсортирован по id_region
     * Входные параметры: алиас карты и список айдишников.
     *
     * @param $map_alias
     * @param $ids_list
     * @return array
     */
    public static function getRegionsWithInfo($map_alias, $ids_list = '')
    {
        $pdo = App::$pdo;

        $in_subquery = !empty($ids_list) ? " AND id_region IN ({$ids_list})" : "";
        try {
            $query = "
SELECT id FROM map_data_regions AS mdr1
 WHERE `alias_map` = :alias_map
   AND id = ( SELECT MAX(id) FROM map_data_regions AS mdr2 WHERE mdr1.id_region = mdr2.id_region )
  {$in_subquery}
 ORDER BY id_region";
            $sth = $pdo->prepare($query);
            $sth->bindValue('alias_map', $map_alias, PDO::PARAM_STR);
            $sth->execute();
            $all_ids = $sth->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($all_ids)) {
                return [];
            }

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
    FROM map_data_regions
    WHERE `id` IN ({$all_ids_string})
        ";

            $all_regions = [];

            $sth = $pdo->prepare($query_data);
            $sth->execute([
                'alias_map' =>  $map_alias
            ]);

            //@todo: HINT (преобразование PDO->fetchAll() в асс.массив, где индекс - значение определенного столбца каждой строки)
            array_map( static function($row) use (&$all_regions) {
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
            dd($e);
        }

        return $all_regions;
    }

    /**
     * Проходит по массиву регионов и видимость региона для текущего пользователя на основе прав доступа к контенту
     *
     * Не реализовано
     *
     * @param $regions_list
     * @param $map_alias
     * @return mixed
     */
    public static function checkRegionsVisibleByCurrentUser($regions_list, $map_alias)
    {
        /*$user_id = Auth::getCurrentUser();
        $user_id
            = $user_id
            ? $user_id['uid']
            : ACL::USERID_SUPERADMIN;

        $current_role = ACL::getRole($user_id, $map_alias);

        return array_filter($regions_list, static function ($row) use ($current_role){
            return (bool)ACL::isValidRole( $current_role, $row[ 'is_publicity' ] );
        });*/
        return $regions_list;
    }

    /**
     * Временная функция, фильтрующая массив регионов с данными.
     * Фильтр не проходят регионы, имеющие is_excludelists отличный от NEVER
     *
     * @param $regions_list
     * @return array
     */
    public static function removeExcludedFromRegionsList($regions_list)
    {
        return array_filter($regions_list, static function($row) {
            return (bool)($row[ 'is_excludelists' ] === 'N');
        });
    }

    /**
     *
     * @param $regions_array
     * @return string
     */
    public static function convertRegionsWithInfo_to_IDs_String($regions_array)
    {
        return implode(", ", array_map( static function($item) {
            return "'{$item['id_region']}'";
        }, $regions_array));
    }

    /**
     * @param $map_alias
     * @param $id_region
     * @return array
     * @throws \RuntimeException
     */
    public function getMapRegionData($map_alias, $id_region):array
    {
        /*$user_id = \Livemap\_\Auth::getCurrentUser();
        $user_id
            = $user_id
            ? $user_id['uid']
            : ACL::USERID_SUPERADMIN;
        $role = ACL::getRole($user_id, $map_alias);

        $role_can_edit = ACL::isValidRole( $role, 'EDITOR');*/

        $this->loadConfig($map_alias);
        $admin_emails = getenv('AUTH.ADMIN_EMAILS') ? explode(' ', getenv('AUTH.ADMIN_EMAILS')) : [];
        $allowed_editors = array_merge($this->mapConfig->can_edit, $admin_emails);
        $role_can_edit = !is_null(config('auth.email')) && in_array(config('auth.email'), $allowed_editors);

        $info = [];

        $query = "
            SELECT `title`, `content`, `content_restricted`, `edit_date`, `is_publicity`, `is_excludelists`
            FROM map_data_regions
            WHERE
                id_region     = :id_region
            AND alias_map     = :alias_map
            ORDER BY edit_date DESC
            LIMIT 1
            ";

        try {
            $sth = $this->pdo->prepare($query);
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
                    'content'           =>  $row['content'],
                    'content_restricted'    =>  $row['content_restricted']
                ];

                /*if (ACL::isValidRole( $role, $row['is_publicity'])) {
                    $info['content'] = $row['content'];
                } else {
                    $info['content'] = $row['content_restricted'] ?? "Доступ ограничен"; // "Доступ ограничен" - брать из конфига карты/слоя
                }*/

            } else {
                $info = [
                    'is_present'    =>  0,
                    'title'         =>  $id_region,
                    'content'       =>  '',
                    'can_edit'      =>  $role_can_edit
                ];
            }
        } catch (\RuntimeException | \PDOException $e) {
            $this->logger->debug(__METHOD__ . " reports : " . $e->getMessage());
        }

        return $info;
    }

    /**
     * Сохраняет информацию по региону для карты со слоем разметки.
     *
     * @param string $map_alias
     * @param string $region_id
     * @param array $request
     * @return Result
     */
    public function storeMapRegionData(string $map_alias, string $region_id, array $request):Result
    {
        $result = new Result();

        $this->loadConfig($map_alias);
        $admin_emails = getenv('AUTH.ADMIN_EMAILS') ? explode(' ', getenv('AUTH.ADMIN_EMAILS')) : [];
        $allowed_editors = array_merge($this->mapConfig->can_edit, $admin_emails);
        $role_can_edit = !is_null(config('auth.email')) && in_array(config('auth.email'), $allowed_editors);

        if (false == $role_can_edit) {
            throw new AccessDeniedException("Обновление региона недоступно, недостаточно прав доступа");
        }

        $query = "
        INSERT INTO map_data_regions
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
        $data = [
            'id_map'        =>  $request['edit:id:map'],
            'alias_map'     =>  $request['edit:alias:map'],
            'edit_whois'    =>  0,
            'edit_ipv4'     =>  ip2long(Server::getIP()),
            'id_region'     =>  $request['edit:id:region'],
            'title'         =>  $request['edit:region:title'],
            'content'       =>  $request['edit:region:content'],
            'content_restricted'    =>  $request['edit:region:content_restricted'],
            'edit_comment'  =>  $request['edit:region:comment'],
            'is_excludelists'   =>  $request['edit:is:excludelists'],
            'is_publicity'  =>  $request['edit:is:publicity']
        ];

        try {
            $sth = $this->pdo->prepare($query);
            $sth->execute($data);
        } catch (\PDOException $e) {
            $result->error($e->getMessage());

        }
        return $result;
    }

    /**
     * Получает массив ревизий (версий контента) региона для карты
     *
     * @todo: переделать
     *
     * @param $map_alias
     * @param $region_id
     * @param int $revisions_depth
     * @return array|false
     */
    public function getRegionRevisions($map_alias, $region_id, int $revisions_depth = 0)
    {
        $query_limit = ($revisions_depth !== 0) ? " LIMIT {$revisions_depth} " : "";

        $query = "
SELECT
  table_data.id_region AS id_region,
  table_data.edit_date AS edit_date,
  table_users.username AS edit_name,
  INET_NTOA(`edit_ipv4`) AS ipv4,
  table_data.title AS title,
  table_data.edit_comment AS edit_comment
FROM
  map_data_regions AS table_data,
  phpauth_users AS table_users
WHERE
    alias_map = :alias_map
AND id_region = :id_region
AND table_data.edit_whois = table_users.id
ORDER BY edit_date {$query_limit};
        ";

        try {
            $sth = $this->pdo->prepare($query);

            $sth->execute([
                'alias_map' =>  $map_alias,
                'id_region' =>  $region_id
            ]);

            $all_revisions = $sth->fetchAll();
        } catch (\Exception | \PDOException $e) {
            $this->logger->debug(__METHOD__ . " reports : " . $e->getMessage());
            $all_revisions = FALSE;
        }

        return $all_revisions;
    }


}