#
# Regular cron jobs for the Livemap package
#
# https://crontab.guru/#1_*/1_*_*_*
#

# 1   */1   *   *   *	    www-data	/usr/bin/curl 'https://ecoparser.wintersky.me/forceUpdate?mode=cron' >> /var/www/ecoparser/logs/cron_update.log 2>&1

