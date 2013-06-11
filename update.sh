#!/bin/bash

export JAVACMD_OPTIONS="$JAVACMD_OPTIONS -Djava.net.preferIPv4Stack=true"

echo "UPDATE"

../osmosis-0.39/bin/osmosis --rri --simc --rx party.osm --ac --bb `cat config.txt | grep "bbox=" | cut -d "=" -f 2-` --wx party-new.osm || exit 4
#../osmosis-0.39/bin/osmosis --rri --simc --rx party.osm --ac --bp file=`cat config.txt | grep "poly=" | cut -d "=" -f 2-` --wx party-new.osm || exit 4

export TS=`cat state.txt | grep timestamp | sed 's/timestamp=//' | sed 's/\\\//g'`
export NOW=`date +%Y%m%d-%H%M -d $TS`

echo
echo "Creating a backup"

tar -cvjf backup/party-$NOW.tar.bz2 party.osm state.txt

echo
echo "Processing: $NOW"

cp party-new.osm dumps/party-$NOW.osm
gzip -9 dumps/party-$NOW.osm
mv party-new.osm party.osm

echo
echo "Checking for time end"
export TIMEEND=`date +%M -d $TS`

case $TIMEEND in
	00)
		cp configuration-15.txt configuration.txt;;
	15)
		cp configuration-15.txt configuration.txt;;
	30)
		cp configuration-15.txt configuration.txt;;
	45)
		cp configuration-15.txt configuration.txt;;
	*)
		echo "We need to use the 14 minute variant :-("
		cp configuration-14.txt configuration.txt;;
esac


echo "DONE X"
