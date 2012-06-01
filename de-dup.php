<?php
$f = glob("dumps/*osm.gz");
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
//		echo "unlink( $file );\n";
//		echo "symlink( $lastFile, $file );\n";
	}
	else
	{
		$lastSize = $a;
		$lastFile = preg_replace( '@^dumps/@', '', $file );
	}
}
