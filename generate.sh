export EXTRA_PATH=/storage1/osm/videos/osmosis-latest/bin

# NEVER rm -rf changes/*osc
#rm -rf changes/*json changes/cache* changes/*env changes/*png images/*

export LAT1=`cat config.txt | grep "LAT1=" | cut -d "=" -f 2-`
export LAT2=`cat config.txt | grep "LAT2=" | cut -d "=" -f 2-`
export LON1=`cat config.txt | grep "LON1=" | cut -d "=" -f 2-`
export LON2=`cat config.txt | grep "LON2=" | cut -d "=" -f 2-`

php de-dup.php
PATH=${EXTRA_PATH}:${PATH}  php changes.php dumps
php de-dup.php 'changes/cache*serialize' 'changes/'
php -dmemory_limit=12G editpoints.php changes $LAT1 $LON2 $LAT2 $LON1
./mapniks.sh
./mkfilm.sh
