<?php

class LiveMapEngine
{
    private $dbi;
    private $table_prefix;

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
        $info = array();
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


}
