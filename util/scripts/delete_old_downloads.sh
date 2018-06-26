#!/bin/bash

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DOWNLOADS_FOLDER=${SCRIPT_FOLDER}'/../../storage/app/public/'

echo "Deleting old files in $DOWNLOADS_FOLDER"
find $DOWNLOADS_FOLDER -not -path '*/\.*' -type f \( ! -iname ".*" \) -mtime +6 -delete

# Reference
# https://stackoverflow.com/questions/59895/getting-the-source-directory-of-a-bash-script-from-within
# https://askubuntu.com/questions/789602/auto-delete-files-older-than-7-days