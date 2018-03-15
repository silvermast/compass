#!/bin/bash
##
# Note: Icon must be >192px
cd `dirname $0`/../
logo="html/res/img/logo.png"

dir="html/res/img"
mkdir $dir

convert $logo -resize 180x180 "$dir/logo-180x180.png" && \
convert $logo -resize 114x114 "$dir/logo-114x114.png" && \
convert $logo -resize 72x72   "$dir/logo-72x72.png" && \
convert $logo -resize 57x57   "$dir/logo-57x57.png" && \

echo "Done!"
exit 0
