# Шаблоны конфигов

## common.conf

```ini
# DB
DB.DRIVER       = "mysql"
DB.HOST         = "127.0.0.1"
DB.PORT         = 3306
DB.USERNAME     = ""
DB.PASSWORD     = ""
DB.NAME         = ""
DB.SLOW_QUERY_THRESHOLD = 0.1

# SearchD

SEARCH.HOST         =   "127.0.0.1"
SEARCH.PORT         =   9306
SEARCH.USER         =   "root"
SEARCH.PASSWORD     =   ""

# Redis

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

# Auth
AUTH.EXPIRE_TIME = 86400 

# Region editor cookies

AUTH.COOKIES.FILEMANAGER_STORAGE_PATH = 'kw_livemap_filemanager_storagepath'
AUTH.COOKIES.FILEMANAGER_CURRENT_MAP  = 'kw_livemap_filemanager_current_map'

# [copyright]
COPYRIGHT   = 'Livemap Engine version 0.10.0 "Aerlis"'

```

## backup.conf 
Нужен для работы скриптов бэкапа

```shell
#!/usr/bin/env bash

# @todo: включить/выключить выполнение каждого из 3 скриптов указанием ключа "ENABLE_BACKUP_DATABASE, ENABLE_BACKUP_STORAGES, ENABLE_BACKUP_ARCHIVES
# @todo: reorder настроек

# Using RClone
export RCLONE_PROVIDER=<rclone section account name>
export RCLONE_CONFIG=<path to rclone.conf>

# Export MySQL settings (user & password MUST be declared at /root/.my.cnf file)
export MYSQL_HOST=localhost

# Temp folder
export TEMP_PATH=/tmp

# Datetime values
export NOW=`date '+%F-%H-%M-%S'`
export NOW_DOW=`date '+%u'`
export NOW_DAY=`date '+%d'`

# Files lists
export RARFILES_EXCLUDE_LIST=rarfiles-exclude.conf
export RARFILES_INCLUDE_LIST=rarfiles-include.conf

# @todo: переименовать в DB_MIN_AGE_*

# Minimal age interval for daily backups (7 days)
export MIN_AGE_DAILY=7d

# Minimal age interval for weekly backups (7*6 + 1)
export MIN_AGE_WEEKLY=43d

# Minimal age interval for monthly backups (30*12)
export MIN_AGE_MONTHLY=360d

# @todo: задать min-age для скрипта бэкапа архивов

# Cloud Containers (можно указать не просто имя контейнера, но и контейнер+путь внутри: LIVEMAP/STORAGE, это корректный вариант
export CLOUD_CONTAINER_DB="<container>/DATABASE"
# export CLOUD_CONTAINER_SITE="<container>"
export CLOUD_CONTAINER_FILES="<container>/STORAGE"

# Backup sources: Databases
export DATABASES=( <databases> )

# Backup sources: Storages (may be array: ( /tmp/1/ /tmp/2/ ) or string
export FILE_SOURCES=<path to storage>

# Backup source: archived name
export FILENAME_RAR=<file>_${NOW}.rar
```

## rclone.conf
```shell
[<RCLONE_PROVIDER>]
type = swift
env_auth = false
user = ...
key = ...
auth = https://auth.selcdn.ru/v1.0
endpoint_type = public
```


