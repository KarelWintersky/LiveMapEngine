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
    public function __construct(\DBConnectionLite $dbi)
    {
        $this->dbi = $dbi;
        $this->table_prefix = $dbi->get_table_prefix();
    }

    /**
     * Возвращает массив регионов, имеющих информацию. Массив содержит id региона и название, отсортирован по id_region
     * Входные параметры: алиас проекта и алиас карты
     *
     * @param $map_alias
     * @return array
     */
    public function getRegionsWithInfo($map_alias)
    {
        $table = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');
        $query = "
    SELECT `id_region`, `title`, `edit_date` FROM
    (
	  SELECT `id_region`, `title`, `edit_date`
	  FROM {$table}
	  WHERE
    	`alias_map` = :alias_map
      ORDER BY `edit_date` DESC
    ) AS t1 GROUP BY `id_region`
        ";
        $all_regions = array();
        try {
            $sth = $this->dbi->getconnection()->prepare($query);
            $sth->execute(array(
                'alias_map'        =>  $map_alias
            ));

            $all_regions = $sth->fetchAll();
        } catch (\PDOException $e) {
        }
        return $all_regions;
    }

    /**
     * @param $regions_array
     * @return string
     */
    public function convertRegionsWithInfo_to_IDs_String($regions_array)
    {
        return implode(", ", array_map(function($item){
            return "'" . $item['id_region'] . "'";
        }, $regions_array));

    }

    /**
     * @param $map_alias
     * @param $id_region
     * @return array
     */
    public function getMapRegionData($map_alias, $id_region)
    {
        $table = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');

        try {
            $query = "
            SELECT title, content, edit_date
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
                $info = array(
                    'is_present'    =>  1,
                    'content'       =>  $row['content'],
                    'title'         =>  $row['title'],
                    'edit_date'     =>  $row['edit_date']
                );
            } else {
                $info = array(
                    'is_present'    =>  0,
                    'title'         =>  $id_region,
                    'content'       =>  ''
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
    public function getRegionRevisions($map_alias, $id_region)
    {
        $table_data     = $this->table_prefix . LMEConfig::get_mainconfig()->get('tables/map_data_regions');
        $table_users    = LMEConfig::get_authconfig()->table_users;
        $query = "
SELECT
  table_data.id_region AS id_region,
  table_data.edit_date AS edit_date,
  table_users.username AS edit_name,
  INET_NTOA(`edit_ipv4`) AS ipv4,
  title
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


    public function getTileRevisions( $alias_map, $id_region )
    {

    }

    /**
     * Сохраняет информацию по региону для SVG-карты.
     * Для сохранения данных по региону на тайловой карте нужна другая функция (похожая)
     * @param $region_data
     * @return array
     */
    public function storeMapRegionData($region_data)
    {
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
    public function getIP()
    {
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }



}
