<?php
$glob = 'dumps/*osm.gz';
$regex = 'dumps/';
if ($argc == 3) {
	$glob = $argv[1];
	$regex = $argv[2];
}
echo $glob, "\n";
echo $regex, "\n";

$f = glob($glob);
sort( $f );
$lastSize = 0;
$lastFile = '';
foreach ( $f as $file )
{
	$a = filesize( $file );
	if ( $a == $lastSize )
	{
		unlink( $file );
		symlink( $lastFile, $file );
	}
	else
	{
		$lastSize = $a;
		$lastFile = preg_replace( "@^{$regex}@", '', $file );
	}
}
