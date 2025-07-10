```sql
SELECT id, id_region  FROM map_data_regions
WHERE `alias_map` = 'maxdragon.kingsworld'
GROUP BY id_region
HAVING id = MAX(id)  
ORDER BY id_region
```

Этот запрос оптимальнее, чем двойной в MapLegacy::getRegionsWithInfo(map, ids_list)
