<?php

/**
 * Class LiveMapEngine
 */
class LiveMapEngine
{
    private $dbi;
    private $table_prefix;

    /**
     * @param DBConnectionLite $dbi
     */
    //@todo: создаваться инстанс должен на map_alias, а коннект получаем из конфига

    public function __construct(\DBConnectionLite $dbi) {
        $this->dbi = $dbi;
        $this->table_prefix = $dbi->get_table_prefix();


        // $role = $this->ACL_getRole($map_alias);
    }

    /**
     * Простая проверка роли
     * ВАЖНО: пользователь с идентификатором 1 может ВСЁ ВСЕГДА
     *
     * @param $user_id
     * @param $map_alias
     * @param string $role
     * @return bool
     */
    public function ACL_checkRole($user_id, $map_alias, $role = 'edit') {

        if ($user_id == 1) return true;         //@todo: HARDCODE, userid 1 can EVERYTHING
        if ($user_id === null) return false;
        if ($user_id === false) return false;

        $table = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/settings_acl');

        $query = "
        SELECT `{$role}` FROM {$table} WHERE `user_id` = {$user_id} AND `map_alias` = '{$map_alias}'
        ";

        $sth = $this->dbi->getconnection()->query($query);

        return ($sth && $sth->fetchColumn() == 'Y') ? true : false;
    }


    public function ACL_getRole($map_alias) {

        // if (LMEAuth::$uid === 1) return 'ROOT'; //@todo: Пользователь 1 имеет права root

        if (!LMEAuth::$is_logged) return 'ANYONE';

        $table = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/settings_acl');

        $query = "
        SELECT user_id, owner, edit, view FROM {$table} WHERE `user_id` = :user_id AND `map_alias` = :map_alias
        ";
        $sth = $this->dbi->getconnection()->prepare($query);
        $sth->execute([
            'user_id'   =>  LMEAuth::$uid,
            'map_alias' =>  $map_alias
        ]);

        $acl = $sth->fetch();

        if (!$acl) return 'ANYONE';

        if ($acl['owner'] == 'Y') return 'OWNER';

        if ($acl['edit'] == 'Y') return 'EDITOR';

        if ($acl['view'] == 'Y') return 'VISITOR';

        return 'ANYONE';
    }

    public function ACL_isValidRole($first_role, $second_role) {
        $ROLE_TO_POWER = array(
            'ANYONE'        =>  0,
            'VISITOR'       =>  1,
            'EDITOR'        =>  2,
            'OWNER'         =>  3,
            'ROOT'          =>  4
        );

        if (!array_key_exists($first_role, $ROLE_TO_POWER)) return false;
        if (!array_key_exists($second_role, $ROLE_TO_POWER)) return false;

        return ( $ROLE_TO_POWER[$first_role] >= $ROLE_TO_POWER[$second_role] );
    }

    /**

     *
     * @param $map_alias
     * @return array
     */
    /**
     * Возвращает массив регионов, имеющих информацию. Массив содержит id региона и название, отсортирован по id_region
     * Входные параметры: алиас проекта и алиас карты
     *
     * @param string $map_alias
     * @param string $ids_list
     * @return array
     */
    public function getRegionsWithInfo($map_alias, $ids_list = '') {
        $table = $this->table_prefix . 'map_data_regions';

        if ($ids_list != '') {
            $in_subquery = "AND `id_region` IN ({$ids_list})";
        } else {
            $in_subquery = '';
        }

        $query = "
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
    FROM
    (
	  SELECT *
	  FROM {$table}
	  WHERE
    	`alias_map` = :alias_map
    	{$in_subquery}
      ORDER BY `edit_date` DESC
    ) AS t1 GROUP BY `id_region`
        ";

        $all_regions = array();
        try {
            $sth = $this->dbi->getconnection()->prepare($query);
            $sth->execute(array(
                'alias_map'        =>  $map_alias
            ));

            $current_role = $this->ACL_getRole($map_alias);

            //@HINT (преобразование PDO->fetchAll() в асс.массив, где индекс - значение определенного столбца каждой строки)
            array_map(function($row) use (&$all_regions, $current_role) {
                // проверка прав: может ли текущий пользователь иметь инфу по этому региону?

                if ($this->ACL_isValidRole($current_role, $row['is_publicity'])) {
                    $all_regions[ $row['id_region'] ] = $row;
                }

                // $all_regions[ $row['id_region'] ] = $row; // старое поведение

            }, $sth->fetchAll());

        } catch (\PDOException $e) {
        }
        return $all_regions;
    }

    /**
     * Выделяет из массива регионов
     * @param $regions_array
     * @return string
     */
    public function convertRegionsWithInfo_to_IDs_String($regions_array) {
        return implode(", ", array_map(function($item){
            return "'" . $item['id_region'] . "'";
        }, $regions_array));

    }

    /**
     * Здесь же надо проверить права доступа к региону
     *
     *
     * @param $map_alias
     * @param $id_region
     * @return array
     */
    public function getMapRegionData($map_alias, $id_region) {
        $table = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');

        $role = $this->ACL_getRole($map_alias);

        $role_can_edit = $this->ACL_isValidRole( $role, 'EDITOR');

        $info = array();

        try {
            $query = "
            SELECT title, content, edit_date, is_publicity
            FROM {$table}
            WHERE
                id_region     = :id_region
            AND alias_map     = :alias_map
            ORDER BY edit_date DESC
            LIMIT 1
            ";

            $sth = $this->dbi->getconnection()->prepare($query);
            $sth->execute(array(
                'id_region'     =>  $id_region,
                'alias_map'     =>  $map_alias
            ));
            $row = $sth->fetch();

            if ($row) {

                if ($this->ACL_isValidRole( $role, $row['is_publicity'] )) {
                    $info = array(
                        'is_present'    =>  1,
                        'content'       =>  $row['content'],
                        'title'         =>  $row['title'],
                        'edit_date'     =>  $row['edit_date'],
                        'can_edit'      =>  $role_can_edit
                    );
                } else {
                    $info = array(
                        'is_present'    =>  1,
                        'content'       =>  'Limited access',       //@todo: move to map config
                        'title'         =>  $row['title'],          //@todo: move to map config
                        'edit_date'     =>  $row['edit_date'],
                        'can_edit'      =>  $role_can_edit
                    );
                }
            } else {
                $info = array(
                    'is_present'    =>  0,
                    'title'         =>  $id_region,
                    'content'       =>  '',
                    'can_edit'      =>  $role_can_edit
                );
            }

        } catch (\PDOException $e) {
            die('Method: ' . __FUNCTION__ . ' <br/> Message:  ' .$e->getMessage());
        }
        return $info;
    }

    /**
     * Получает массив ревизий региона для leaflet-карты
     * @param $map_alias
     * @param $id_region
     * @return array|bool
     */
    public function getRegionRevisions($map_alias, $id_region) {
        $table_data     = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');
        $table_users    = LMEConfig::get_authconfig()->table_users;
        $query = "
SELECT
  table_data.id_region AS id_region,
  table_data.edit_date AS edit_date,
  table_users.username AS edit_name,
  INET_NTOA(`edit_ipv4`) AS ipv4,
  table_data.title AS title,
  table_data.edit_comment AS edit_comment
FROM
  {$table_data} AS table_data,
  {$table_users} AS table_users
WHERE
    alias_map = :alias_map
AND id_region = :id_region
AND table_data.edit_whois = table_users.id
ORDER BY edit_date ;
        ";
        try {
            $sth = $this->dbi->getconnection()->prepare($query);
            $sth->execute(array(
                'alias_map'     =>  $map_alias,
                'id_region'     =>  $id_region
            ));

            $all_revisions = $sth->fetchAll();
        } catch (\PDOException $e) {
            $all_revisions = FALSE;
        }
        return $all_revisions;


    }

    /**
     * Сохраняет информацию по региону для SVG-карты.
     * Для сохранения данных по региону на тайловой карте нужна другая функция (похожая)
     * @param $region_data
     * @return array
     */
    public function storeMapRegionData($region_data) {
        $success = array(
            'state'     =>  FALSE,
            'message'   =>  ''
        );
        $table_data     = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');

        $query = "
        INSERT INTO {$table_data}
         (id_map, alias_map, edit_whois, edit_ipv4, id_region, title, content, edit_comment)
         VALUES
         (:id_map, :alias_map, :edit_whois, INET_ATON(:edit_ipv4), :id_region, :title, :content, :edit_comment)
        ";

        $data = array(
            'id_map'        =>  $region_data['id_map'],
            'alias_map'     =>  $region_data['alias_map'],
            'edit_whois'    =>  $region_data['edit_whois'],
            'edit_ipv4'     =>  $this->getIP(),
            'id_region'     =>  $region_data['id_region'],
            'title'         =>  $region_data['title'],
            'content'       =>  $region_data['content'],
            'edit_comment'  =>  $region_data['edit_comment']
        );

        try {
            $sth = $this->dbi->getconnection()->prepare($query);
            $success['state'] = $sth->execute($data);
        } catch (\PDOException $e) {
            $success['state'] = FALSE;
            $success['message'] = $e->getMessage();
        }
        return $success;

    }

    /**
     * Полная копия приватного метода класса PHPAuth. Отдает айпишник.
     * @return mixed
     */
    public function getIP() {
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    //@todo: тайтл карты и настройки мы должны брать из таблицы settings_map
    // но сейчас она не заполняется никак и все данные берутся из json-файла настроек или SVG-файла разметки
    public function getMapInfo($map_alias) {
        return null;
    }


}
