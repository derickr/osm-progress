<?php
$files = glob($argv[1] . '/*.osm.gz');

// sort on filename
sort($files);

// create changes
$length = count( $files ) - 1;
for ( $i = 0; $i < $length; $i++ )
{
	preg_match( '@^dumps/([a-z]*)-([0-9]{8}-[0-9]{4})@', $files[$i + 1], $m );
	if ( !file_exists( "changes/diff-{$m[2]}.osc" ) )
	{
		if ( filesize( $files[$i] ) == filesize( $files[$i + 1] ) )
		{
			file_put_contents( "changes/diff-{$m[2]}.osc", <<<ENDDOC
<?xml version='1.0' encoding='UTF-8'?>
<osmChange version="0.6" generator="Osmosis 0.39">
</osmChange>

ENDDOC
			);
		}
		else
		{
			passthru( "osmosis --rx {$files[$i + 1]} --rx {$files[$i]} --derive-change --write-xml-change file='changes/diff-{$m[2]}.osc'" );
		}
	}
}
