
```ini
# DB
DB.DRIVER       = "mysql"
DB.HOST         = "127.0.0.1"
DB.PORT         = 3306
DB.USERNAME     = ""
DB.PASSWORD     = ""
DB.NAME         = ""

DB.SLOW_QUERY_THRESHOLD = 0.1

SEARCH.HOST         =   "127.0.0.1"
SEARCH.PORT         =   9306
SEARCH.USER         =   "root"
SEARCH.PASSWORD     =   ""

# REDIS

REDIS.ENABLED = 0
REDIS.HOST = 'localhost'
REDIS.PORT = 6379
REDIS.PASSWORD = ''
REDIS.DATABASE = 8

# Paths

PATH.INSTALL = /var/www.livemap/kwLME.LiveMap/
PATH.LOGS    = ${PATH.INSTALL}logs/
PATH.STORAGE = ${PATH.INSTALL}public/storage

PATH.SMARTY_TEMPLATES = ${PATH.INSTALL}templates/
PATH.SMARTY_CACHE = ${PATH.INSTALL}cache/

# DB.Tables

DB.TABLE.MAP_DATA_REGIONS = 'map_data_regions'

AUTH.PHPAUTH_ENABLED         = true
AUTH.LOGIN_ENABLED           = true
AUTH.AUTO_ACTIVATION         = true

AUTH.LOGGED.DURATION    = 18000

# [cookies]
AUTH.COOKIES.NEW_REGISTRED_USERNAME  = 'kw_livemap_new_registred_username'
AUTH.COOKIES.LAST_LOGGED_USER        = 'kw_livemap_last_logged_user'

AUTH.COOKIES.FILEMANAGER_STORAGE_PATH = 'kw_livemap_filemanager_storagepath'
AUTH.COOKIES.FILEMANAGER_CURRENT_MAP  = 'kw_livemap_filemanager_current_map'

# [copyright]
COPYRIGHT   = 'Livemap Engine version 0.10.0 "Aerlis"'
```



