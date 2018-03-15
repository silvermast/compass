#!/bin/bash
##
# Note: Icon must be >192px
cd `dirname $0`/../
logo="html/res/img/logo.png"

dir="html/res/img"
mkdir $dir

convert $logo -resize 100x100 "$dir/logo-100x100.png" && \
convert $logo -resize 114x114 "$dir/logo-114x114.png" && \
convert $logo -resize 120x120 "$dir/logo-120x120.png" && \
convert $logo -resize 144x144 "$dir/logo-144x144.png" && \
convert $logo -resize 152x152 "$dir/logo-152x152.png" && \
convert $logo -resize 167x167 "$dir/logo-167x167.png" && \
convert $logo -resize 180x180 "$dir/logo-180x180.png" && \
convert $logo -resize 192x192 "$dir/logo-192x192.png" && \
convert $logo -resize 29x29   "$dir/logo-29x29.png" && \
convert $logo -resize 36x36   "$dir/logo-36x36.png" && \
convert $logo -resize 40x40   "$dir/logo-40x40.png" && \
convert $logo -resize 48x48   "$dir/logo-48x48.png" && \
convert $logo -resize 50x50   "$dir/logo-50x50.png" && \
convert $logo -resize 57x57   "$dir/logo-57x57.png" && \
convert $logo -resize 58x58   "$dir/logo-58x58.png" && \
convert $logo -resize 60x60   "$dir/logo-60x60.png" && \
convert $logo -resize 72x72   "$dir/logo-72x72.png" && \
convert $logo -resize 76x76   "$dir/logo-76x76.png" && \
convert $logo -resize 80x80   "$dir/logo-80x80.png" && \
convert $logo -resize 87x87   "$dir/logo-87x87.png" && \
convert $logo -resize 96x96   "$dir/logo-96x96.png" && \

echo "Done!"
exit 0
