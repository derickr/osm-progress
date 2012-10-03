#!/bin/bash

export JAVACMD_OPTIONS="$JAVACMD_OPTIONS -Djava.net.preferIPv4Stack=true"

echo "SETUP"
mkdir -p artwork
mkdir -p backup
mkdir -p changes
mkdir -p config
mkdir -p dumps
mkdir -p gpx
mkdir -p images

wget http://download.geofabrik.de/openstreetmap/europe/great_britain.osm.pbf -O great_britain.osm.pbf
../osmosis-0.39/bin/osmosis --rb great_britain.osm.pbf --bb `cat config.txt | grep "bbox=" | cut -d "=" -f 2-` --wx party.osm
rm configuration.txt
../osmosis-0.39/bin/osmosis --rrii 
# get state file from http://planet.openstreetmap.org/redaction-period/minute-replicate
#55.6798, -3.816, 55.652, -3.7486
