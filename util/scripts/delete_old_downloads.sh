#!/bin/bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DOWNLOADS_FOLDER=${SCRIPT_FOLDER}'/../../storage/app/public/'

date
echo "Deleting old files in $DOWNLOADS_FOLDER"
# Remove the ir_ download ZIP files
sudo find $DOWNLOADS_FOLDER  -type f -name 'ir_*.zip' -mtime +6 -delete
# Remove the ir_ directories and all files in them. We echo the directories
# so we know what was deleted since the find/remove is silent. We need to
# -prune so that the rm -rf doesn't remove stuff that is already removed.
sudo find $DOWNLOADS_FOLDER  -type d -name 'ir_*' -mtime +6 -exec echo {} \;
sudo find $DOWNLOADS_FOLDER  -type d -name 'ir_*' -mtime +6 -prune -exec rm -rf {} \;
date
echo

# Reference
# https://stackoverflow.com/questions/59895/getting-the-source-directory-of-a-bash-script-from-within
# https://askubuntu.com/questions/789602/auto-delete-files-older-than-7-days
# https://superuser.com/questions/152958/exclude-hidden-files-when-searching-with-unix-linux-find/999448#999448
