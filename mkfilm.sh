#!/bin/sh

FIRST_TEN=`(for i in images/aparty-201*.png; do echo $i; done) | head -11`
LAST_TWENTY=`(for i in images/aparty-201*.png; do echo $i; done) | tail -20`

OPACITY=-9

#remove the last 11 images, as they need to be redone due to fading
TO_REMOVE=`(for i in images/x-*jpg; do echo $i; done) | tail -21`
rm $TO_REMOVE

for i in images/aparty*.png; do
	IN_FIRST=`echo ${FIRST_TEN} | sed "s@.*\(${i}\).*@\1@"`
	if test "x${IN_FIRST}" = "x$i"; then
		OPACITY=`expr $OPACITY + 10`
		echo $OPACITY
	fi

	IN_LAST=`echo ${LAST_TWENTY} | sed "s@.*\(${i}\).*@\1@"`
	if test "x${IN_LAST}" = "x$i"; then
		OPACITY=`expr $OPACITY - 5`
		echo $OPACITY
	fi

	b=`echo $i | sed 's/aparty//' | sed 's/images\///' | sed 's/.osm.png//'`
	if [ ! -f images/x$b.jpg ]; then
		if [ -f images/bparty$b.osm.png ]; then
			composite images/bparty$b.osm.png $i /tmp/x.png
		else
			cp $i /tmp/x.png
		fi
		if test "x${IN_FIRST}" = "x$i"; then
			composite -blend ${OPACITY} /tmp/x.png artwork/title.jpg /tmp/y.png
		else
			cp /tmp/x.png /tmp/y.png
		fi
		if test "x${IN_LAST}" = "x$i"; then
			composite -compose Multiply changes/diff$b.png /tmp/y.png /tmp/z.png
			composite -blend ${OPACITY} /tmp/z.png -size 1280x720 xc:black -alpha Set images/x$b.jpg
		else
			composite -compose Multiply changes/diff$b.png /tmp/y.png images/x$b.jpg
		fi
		echo ${i}
	fi
done

mencoder "mf://artwork/title*.jpg" -mf fps=25 -o title.avi -ovc lavc -lavcopts vcodec=msmpeg4v2:vbitrate=16000
mencoder "mf://images/x*.jpg" -mf fps=25 -o middle.avi -ovc lavc -lavcopts vcodec=msmpeg4v2:vbitrate=16000
mencoder "mf://artwork/end*.jpg" -mf fps=25 -o end.avi -ovc lavc -lavcopts vcodec=msmpeg4v2:vbitrate=16000

mencoder -oac copy -oac copy -ovc copy -o test.avi title.avi middle.avi end.avi
mencoder -ovc copy -audiofile /home/derick/Artifact.mp3 -oac copy test.avi -o progress.avi
