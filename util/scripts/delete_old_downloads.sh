#!/bin/bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DOWNLOADS_FOLDER=${SCRIPT_FOLDER}'/../../storage/app/public/'

date
echo "Deleting old files in $DOWNLOADS_FOLDER"
sudo find $DOWNLOADS_FOLDER  -type f  -name '[!.]*' -mtime +6 -delete
sudo find $DOWNLOADS_FOLDER  -type d -empty  -name 'ir_*' -mtime +6 -delete
date
echo

# Reference
# https://stackoverflow.com/questions/59895/getting-the-source-directory-of-a-bash-script-from-within
# https://askubuntu.com/questions/789602/auto-delete-files-older-than-7-days
# https://superuser.com/questions/152958/exclude-hidden-files-when-searching-with-unix-linux-find/999448#999448