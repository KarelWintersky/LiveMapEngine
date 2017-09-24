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


}
