#!/bin/bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "****************************************************"

# source Laravel .env config file
CONFIG_FILE=${SCRIPT_FOLDER}'/../../.env'
source ${CONFIG_FILE}

# get the current time
TIME=`date +%Y-%m-%d_%H-%M-%S`

# create dump folder if it doesn't exist
DUMP_FOLDER='/data/db_backups'
mkdir -p ${DUMP_FOLDER}

if [ ! -d ${DUMP_FOLDER} ] 
then
    echo "Error: folder ${DUMP_FOLDER} does not exist." 
    exit 3
fi

# MongoDB backup
date
echo "Backing up MongoDB database into ${DUMP_FOLDER}"
MONGODB_DUMP_FILE=${TIME}_mongodb.bz2
mongodump --archive | bzip2 > ${DUMP_FOLDER}/${MONGODB_DUMP_FILE}
echo "Done"

# MySQL backup
date
echo "Backing up MYSQL database into ${DUMP_FOLDER}"
MYSQL_DUMP_FILE=${TIME}_mysql.dump
MYSQL_DATABASE="$DB_DATABASE"
MYSQL_USER="$DB_USERNAME"
MYSQL_PASSWORD="$DB_PASSWORD"
sudo mysqldump -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} > ${DUMP_FOLDER}/${MYSQL_DUMP_FILE}
echo "Done"

# delete old backups
date
echo "Deleting old backups in ${DUMP_FOLDER}"
find ${DUMP_FOLDER}  -type f  -name '[!.]*' -mtime +31 -delete
