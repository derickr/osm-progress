EXTRA_PATH=/home/derick/install/osmosis-0.39/bin

# NEVER rm -rf changes/*osc
#rm -rf changes/*json 
#rm -rf changes/*env changes/*png
#rm -rf images/x-*
#rm -rf images/*

LAT1=`cat config.txt | grep "LAT1=" | cut -d "=" -f 2-`
LAT2=`cat config.txt | grep "LAT2=" | cut -d "=" -f 2-`
LON1=`cat config.txt | grep "LON1=" | cut -d "=" -f 2-`
LON2=`cat config.txt | grep "LON2=" | cut -d "=" -f 2-`

php de-dup.php
PATH=${EXTRA_PATH}:${PATH}  php changes.php dumps changes
php editpoints.php changes $LON2 $LAT2 $LON1 $LAT1
./mapniks.sh
./mkfilm.sh
