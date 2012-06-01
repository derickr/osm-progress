#!/bin/bash

export JAVACMD_OPTIONS="$JAVACMD_OPTIONS -Djava.net.preferIPv4Stack=true"

echo "SETUP"
mkdir -p backup
mkdir -p dumps

#wget http://download.geofabrik.de/osm/europe/great_britain.osm.pbf
#../osmosis-0.39/bin/osmosis --rb great_britain.osm.pbf --bb left=-3.85 right=-3.70 top=55.72 bottom=55.60 --wx party.osm
#../osmosis-0.39/bin/osmosis --rrii 
# get state file from http://planet.openstreetmap.org/minute-replicate/
#55.6798, -3.816, 55.652, -3.7486

echo "UPDATE"

../osmosis-0.39/bin/osmosis --rri --simc --rx party.osm --ac --bb `cat config.txt | grep "bbox=" | cut -d "=" -f 2-` --wx party-new.osm || exit 4

export TS=`cat state.txt | grep timestamp | sed 's/timestamp=//' | sed 's/\\\//g'`
export NOW=`date +%Y%m%d-%H%M -d $TS`

tar -cvjf backup/party-$NOW.tar.bz2 party.osm state.txt

echo
echo "Processing: $NOW"

cp party-new.osm dumps/party-$NOW.osm
gzip -9 dumps/party-$NOW.osm
mv party-new.osm party.osm

echo "DONE X"
