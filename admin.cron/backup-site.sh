#!/usr/bin/env bash
# Backup (sync) archived files

SCRIPT="${0}"
SCRIPT_BASEDIR="$(dirname ${SCRIPT})"
if [[ ${SCRIPT_BASEDIR} != '.' ]]; then
	SCRIPT_PATH=`echo ${SCRIPT_BASEDIR}`
else
	SCRIPT_PATH=`echo ${PWD}`
fi

. /etc/arris/livemap/backup.conf

rar a -x@${SCRIPT_PATH}/${RARFILES_EXCLUDE_LIST} -m5 -mde -s -r ${TEMP_PATH}/${FILENAME_RAR} @${SCRIPT_PATH}/${RARFILES_INCLUDE_LIST}

rclone delete --config ${RCLONE_CONFIG} --min-age 71d ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_SITE}/
rclone sync --config ${RCLONE_CONFIG} -L -u -v ${TEMP_PATH}/${FILENAME_RAR} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_SITE}/

rm ${TEMP_PATH}/${FILENAME_RAR}