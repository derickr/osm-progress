#!/bin/bash

export TZ=UTC
export JAVACMD_OPTIONS="$JAVACMD_OPTIONS -Djava.net.preferIPv4Stack=true"
export MINUTELY=`cat config.txt | grep "MINUTELY=" | cut -d "=" -f 2-`

echo "UPDATE"

../osmosis-latest/bin/osmosis --rri --simc --rx party.osm --ac --bb `cat config.txt | grep "bbox=" | cut -d "=" -f 2-` --wx party-new.osm || exit 4
#../osmosis-0.39/bin/osmosis --rri --simc --rx party.osm --ac --bp file=`cat config.txt | grep "poly=" | cut -d "=" -f 2-` --wx party-new.osm || exit 4

export TS=`cat state.txt | grep timestamp | sed 's/timestamp=//' | sed 's/\\\//g'`
export NOW=`date +%Y%m%d-%H%M -d $TS`

echo
echo "Creating a backup"

tar -cvjf backup/party-$NOW.tar.bz2 party.osm state.txt

echo
echo "Processing: $NOW"

cp party-new.osm dumps/party-$NOW.osm
gzip dumps/party-$NOW.osm
mv party-new.osm party.osm

echo

if test $MINUTELY -ne "1"; then
	echo "DONE X"
	exit
fi

echo
echo "Checking for time end"
export TIMEEND=`date +%M -d $TS`

if ((1 <= $TIMEEND && $TIMEEND <= 8))
then
	cp configuration-14.txt configuration.txt
elif ((9 <= $TIMEEND && $TIMEEND <= 14))
then
	cp configuration-16.txt configuration.txt
elif ((16 <= $TIMEEND && $TIMEEND <= 23))
then
	cp configuration-14.txt configuration.txt
elif ((24 <= $TIMEEND && $TIMEEND <= 29))
then
	cp configuration-16.txt configuration.txt
elif ((31 <= $TIMEEND && $TIMEEND <= 38))
then
	cp configuration-14.txt configuration.txt
elif ((39 <= $TIMEEND && $TIMEEND <= 44))
then
	cp configuration-16.txt configuration.txt
elif ((46 <= $TIMEEND && $TIMEEND <= 53))
then
	cp configuration-14.txt configuration.txt
elif ((54 <= $TIMEEND && $TIMEEND <= 59))
then
	cp configuration-16.txt configuration.txt
else
	cp configuration-15.txt configuration.txt
fi

echo "DONE X"
