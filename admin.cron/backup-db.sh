#!/usr/bin/env bash
# BACKUP (copy) Database

SCRIPT="${0}"
SCRIPT_BASEDIR="$(dirname ${SCRIPT})"
if [[ ${SCRIPT_BASEDIR} != '.' ]]; then
	SCRIPT_PATH=`echo ${SCRIPT_BASEDIR}`
else
	SCRIPT_PATH=`echo ${PWD}`
fi

. /etc/arris/livemap/backup.conf

for DB in "${DATABASES[@]}"
do
    FILENAME_SQL=${DB}_${NOW}.sql
    FILENAME_GZ=${DB}_${NOW}.gz

    mysqldump -Q --single-transaction -h "$MYSQL_HOST" "$DB" | pigz -c > ${TEMP_PATH}/${FILENAME_GZ}

    rclone delete --config ${RCLONE_CONFIG} --min-age 7d ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/DAILY
    rclone copy --config ${RCLONE_CONFIG} -L -u -v ${TEMP_PATH}/${FILENAME_GZ} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/DAILY

    # if it is a sunday (7th day of week) - make store weekly backup (42 days = 7*6 + 1, so we storing last six weeks)
    if [[ ${NOW_DOW} -eq 1 ]]; then
        rclone delete --config ${RCLONE_CONFIG} --min-age 43d ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/WEEKLY
        rclone copy --config ${RCLONE_CONFIG} -L -u -v ${TEMP_PATH}/${FILENAME_GZ} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/WEEKLY
    fi

    # backup for first day of month
    if [[ ${NOW_DAY} == 01 ]]; then
        rclone delete --config ${RCLONE_CONFIG} --min-age 360d ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/MONTHLY
        rclone copy --config ${RCLONE_CONFIG} -L -u -v ${TEMP_PATH}/${FILENAME_GZ} ${RCLONE_PROVIDER}:${CLOUD_CONTAINER_DB}/MONTHLY
    fi

    # rm "$TEMP_PATH"/"$FILENAME_GZ"
done
