#!/usr/bin/env bash

SCRIPT="${0}"
SCRIPT_BASEDIR="$(dirname ${SCRIPT})"

if [ $SCRIPT_BASEDIR != '.' ]; then
	INSTALL_PATH=`echo ${SCRIPT_BASEDIR} |xargs dirname`
else
	INSTALL_PATH=`echo ${PWD} |xargs dirname`
fi

rm --force ${INSTALL_PATH}/public/sitemaps/*
mkdir --parents ${INSTALL_PATH}/public/sitemaps

# @todo: подумать о разрешении перестраивать sitemap только если есть флаг (/config.site/need_rebuid_sitemap.flag) с удалением флага после

/usr/local/bin/sitemapgenerator --config /etc/arris/livemap/sitemap.ini

