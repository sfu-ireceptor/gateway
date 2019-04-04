#!/bin/bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# source Laravel .env config file
CONFIG_FILE=${SCRIPT_FOLDER}'/../../.env'
source ${CONFIG_FILE}

MYSQL_DATABASE="$DB_DATABASE"
MYSQL_USER="$DB_USERNAME"
MYSQL_PASSWORD="$DB_PASSWORD"

# create dump folder if it doesn't exist
DUMP_FOLDER=${SCRIPT_FOLDER}'/../../storage/db_backups'
mkdir ${DUMP_FOLDER}

TIME=`date +%Y-%m-%d_%H-%M-%S`

# MongoDB
date
echo "Backing up MongoDB database into ${DUMP_FOLDER}"
MONGODB_DUMP_FILE=${TIME}_mongodb.bz2
mongodump --archive | bzip2 > ${DUMP_FOLDER}/${MONGODB_DUMP_FILE}
echo "Done"

# MySQL
date
echo "Backing up MYSQL database into ${DUMP_FOLDER}"
MYSQL_DUMP_FILE=${TIME}_mysql.bz2
sudo mysqldump -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} > ${DUMP_FOLDER}/${MYSQL_DUMP_FILE}
echo "Done"

# delete old backups
date
echo "Deleting old backups in ${DUMP_FOLDER}"
#find ${DUMP_FOLDER}  -type f  -name '[!.]*' -mtime +31 -delete
