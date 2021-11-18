<?php

namespace Livemap\Units;

use Arris\AppLogger;
use Arris\Helpers\Server;
use Exception;
use Livemap\App;
use Livemap\Interfaces\MapInterface;
use PDO;
use PDOException;
use RuntimeException;

class Map implements MapInterface
{
    /**
     * @var PDO
     */
    private $pdo;
    
    /**
     * @var AppLogger
     */
    private $logger;
    
    public function __construct()
    {
        $this->pdo = App::factory()->pdo;
        $this->logger = AppLogger::scope('main');
    }
    
    /**
     * @param $map_alias
     * @param $id_region
     * @return array
     * @throws Exception
     */
    public function getMapRegionData($map_alias, $id_region):array
    {
        $user_id = Auth::getCurrentUser()['uid'];
        $role = ACL::getRole($user_id, $map_alias);

        $role_can_edit = ACL::isValidRole( $role, 'EDITOR');

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
                    'content'           =>  '',
                    'content_restricted'    =>  $row['content_restricted']
                ];

                if (ACL::isValidRole( $role, $row['is_publicity'])) {
                    $info['content'] = $row['content'];
                } else {
                    $info['content'] = $row['content_restricted'] ?? "Доступ ограничен"; // "Доступ ограничен" - брать из конфига карты/слоя
                }

            } else {
                $info = [
                    'is_present'    =>  0,
                    'title'         =>  $id_region,
                    'content'       =>  '',
                    'can_edit'      =>  $role_can_edit
                ];
            }
        } catch (RuntimeException | PDOException $e) {
            $this->logger->debug(__METHOD__ . " reports : " . $e->getMessage());
        }

        return $info;
    }

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
            $sth = $this->pdo->prepare($query_limit);
        
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

    public static function checkRegionsVisibleByCurrentUser($regions_list, $map_alias)
    {
        $user_id = Auth::getCurrentUser()['uid'];
        $current_role = ACL::getRole($user_id, $map_alias);
    
        return array_filter($regions_list, static function ($row) use ($current_role){
            return (bool)ACL::isValidRole( $current_role, $row[ 'is_publicity' ] );
        });
    }
    
    /**
     * @param $data
     * @param $map_alias
     * @param $id_region
     * @return array
     */
    public function storeMapRegionData($data, $map_alias, $id_region)
    {
        $success = array(
            'state'     =>  FALSE,
            'message'   =>  ''
        );
    
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
    
        $data['edit_ipv4'] = ip2long(Server::getIP());
        $data['alias_map'] = $map_alias;
        $data['id_region'] = $id_region;
    
        try {
            $sth = $this->pdo->prepare($query);
            $success['state'] = $sth->execute($data);
        
        } catch (\Exception | \PDOException $e) {
            $success['state'] = FALSE;
            $success['message'] = $e->getMessage();
            $this->logger->error(__METHOD__ . " reports : " . $e->getMessage());
        }
        return $success;
    }

    public function getRegionsWithInfo($map_alias, $ids_list = '')
    {
        $in_subquery = !empty($ids_list) ? " AND id_region IN ({$ids_list})" : "";
        $query_get_id = "
 SELECT id
 FROM map_data_regions AS mdr1
 WHERE `alias_map` = :alias_map AND
 `id` = ( SELECT MAX(id) FROM map_data_regions AS mdr2 WHERE mdr1.id_region = mdr2.id_region )
 {$in_subquery}
 ORDER BY id_region
        ";
    
        try {
            $sth = $this->pdo->prepare($query_get_id);
            $sth->execute([
                'alias_map' =>  $map_alias
            ]);
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
        
            $sth = $this->pdo->prepare($query_data);
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
    
    /**
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
    
}