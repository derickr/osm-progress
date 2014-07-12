#!/bin/bash

mkdir -p images

export LAT1=`cat config.txt | grep "LAT1=" | cut -d "=" -f 2-`
export LAT2=`cat config.txt | grep "LAT2=" | cut -d "=" -f 2-`
export LON1=`cat config.txt | grep "LON1=" | cut -d "=" -f 2-`
export LON2=`cat config.txt | grep "LON2=" | cut -d "=" -f 2-`

FIRST="0"

for i in dumps/*osm.gz; do
	NAME=`echo $i | sed 's/dumps\///' | sed 's/\.gz//'`
	NAME2=`echo $i | sed 's/dumps\///' | sed 's/party-//' | sed 's/\.osm\.gz//'`
	EDITBOXFILE="changes/editbbox-$NAME2.json"

	. ./changes/diff-${NAME2}.env

	if [ ! -f images/a${NAME}.png ]; then
		EDITBOXSIZE=`stat -L -c "%s" $EDITBOXFILE`
		if test $FIRST -ne "1"; then
			FIRST="1";
			echo "Loading first one: $i";
			osm2pgsql -S /home/derick/install/openstreetmap-carto/openstreetmap-carto.style --slim -d gis -C 2400 $i
			COUNTSAME="1"
		fi
		if test $EDITBOXSIZE -ne 91; then
			echo "Loading $i";
			osm2pgsql -S /home/derick/install/openstreetmap-carto/openstreetmap-carto.style --slim -d gis -C 2400 $i
			COUNTSAME="1"
		else
			echo "Skipping $i";
			COUNTSAME=`echo "${COUNTSAME} + 1" | bc -q`
		fi

		export MAPNIK_MAP_FILE=/home/derick/install/openstreetmap-carto/mapnik.osm
		/home/derick/install/mapnik/generate_image.py ${LAT1} ${LON1} ${LAT2} ${LON2}
		mv image.png images/a${NAME}.png
#		export MAPNIK_MAP_FILE=/home/derick/install/mapnik/3dbuil.xml
#		/home/derick/install/mapnik/generate_image.py ${LAT1} ${LON1} ${LAT2} ${LON2}
#		mv image.png images/b${NAME}.png
	fi
done
