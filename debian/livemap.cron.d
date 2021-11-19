#
# Regular cron jobs for the Livemap package
#
# https://crontab.guru/#1_*/1_*_*_*
#

# *   */2   *   *   *	        www-data	/bin/bash 		/var/www/livemap/admin.cron/cron.update.sitemap.sh

1   */2     *       *       *       root    /bin/bash       /var/www/livemap/admin.cron/backup-db.sh >> /var/log/backup.livemap-db.log 2>&1
5   4       *       *       *       root    /bin/bash       /var/www/livemap/admin.cron/backup-files.sh >> /var/log/backup.livemap-db.log 2>&1

