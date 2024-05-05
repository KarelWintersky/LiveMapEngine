#!/usr/bin/env bash
# Backup (sync) unarchived storages

SCRIPT="${0}"
SCRIPT_BASEDIR="$(dirname ${SCRIPT})"
if [[ ${SCRIPT_BASEDIR} != '.' ]]; then
	SCRIPT_PATH=`echo ${SCRIPT_BASEDIR}`
else
	SCRIPT_PATH=`echo ${PWD}`
fi

. /etc/arris/livemap/backup.conf

if [[ "$(declare -p FILE_SOURCES)" =~ "declare -a" ]]; then
    for SOURCE in "${FILE_SOURCES[@]}"
    do
        rclone sync --config ${RCLONE_CONFIG} -L --progress -u -v ${SOURCE} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_FILES}/${SOURCE}
    done
else
    rclone sync --config ${RCLONE_CONFIG} -L --progress -u -v ${FILE_SOURCES} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_FILES}/${SOURCE}
fi

