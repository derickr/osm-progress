<?php
require '/home/derick/dev/osm-tools/lib/getinfo.php';
require '/home/derick/dev/osm-tools/lib/gpx.php';
define('WIDTH', 1280);
define('HEIGHT', 720);
$DO_ZOOM = $DO_POINTS = $DO_GPX = true;

$config = file_get_contents( 'config.txt' );

if ( preg_match("@ZOOM=(\d+)@", $config, $m ) )
{
	$DO_ZOOM = ( $m[1] == '1' );
}
if ( preg_match("@POINTS=(\d+)@", $config, $m ) )
{
	$DO_POINTS = ( $m[1] == '1' );
}
if ( preg_match("@GPX=(\d+)@", $config, $m ) )
{
	$DO_GPX = ( $m[1] == '1' );
}
if ( preg_match("@ZOOMINFACTOR=(\d+)@", $config, $m ) )
{
	define("ZOOMINNESS", (int) $m[1]);
}
else
{
	define("ZOOMINNESS", 50);
}
if ( preg_match("@ZOOMOUTFACTOR=(\d+)@", $config, $m ) )
{
	define("ZOOMOUTNESS", (int) $m[1]);
}
else
{
	define("ZOOMOUTNESS", 20);
}

$files = glob($argv[1] . '/*.osc');
if ( isset( $argv[5] ) )
{
	$filter = array( 'N' => $argv[2], 'E' => $argv[3], 'S' => $argv[4], 'W' => $argv[5] );
}
else
{
	$filter = array( 'N' => 90, 'E' => 180, 'S' => -90, 'W' => -180 );
}

// load change file, and write a file for each of them with a json string
// containing all the edit points
$bounds = array( 'latMin' => 90, 'latMax' => -90, 'lonMin' => 180, 'lonMax' => -180, 'tsMin' => PHP_INT_MAX, 'tsMax' => 0 );

// read GPS traces
$gpxFiles = glob('gpx/*gpx');
$gpxPoints = array();

foreach ( $gpxFiles as $gpxFile )
{
	$gpxPoints = array_merge( $gpxPoints, Gpx::getPoints( $gpxFile, $filter ) );
}


// points
$points = $pointInfo['points'] = array();
if ( $DO_POINTS )
{
	$lastFile = NULL;
	$needToFetchCacheFileName = NULL;

	foreach ( $files as $file )
	{
		preg_match( "@^{$argv[1]}/([a-z]*)-([0-9]{8}-[0-9]{4})@", $file, $m );
		$cacheFileName = "changes/cache-{$m[2]}.serialize";

		if ( file_exists( $cacheFileName ) )
		{
			echo "Skipping bounds merging for $file\n";
			$needToFetchCacheFileName = $cacheFileName;
			continue;
		}

		if ( $needToFetchCacheFileName )
		{
			$points = unserialize( file_get_contents( $needToFetchCacheFileName ) );
			$needToFetchCacheFileName = NULL;
		}

		echo "Bounds merging for $file\n";

		$pointInfo = getEditPoints( $argv[1], $m[2], $filter );

		if ( $pointInfo['meta']['lonMin'] < $bounds['lonMin'] ) { $bounds['lonMin'] = $pointInfo['meta']['lonMin']; }
		if ( $pointInfo['meta']['lonMax'] > $bounds['lonMax'] ) { $bounds['lonMax'] = $pointInfo['meta']['lonMax']; }
		if ( $pointInfo['meta']['latMin'] < $bounds['latMin'] ) { $bounds['latMin'] = $pointInfo['meta']['latMin']; }
		if ( $pointInfo['meta']['latMax'] > $bounds['latMax'] ) { $bounds['latMax'] = $pointInfo['meta']['latMax']; }
		if ( $pointInfo['meta']['tsMin'] < $bounds['tsMin'] ) { $bounds['tsMin'] = $pointInfo['meta']['tsMin']; }
		if ( $pointInfo['meta']['tsMax'] > $bounds['tsMax'] ) { $bounds['tsMax'] = $pointInfo['meta']['tsMax']; }

		$points = array_merge( $points, $pointInfo['points'] );
		file_put_contents( $cacheFileName, serialize ( $points ) );
	}
}

if ( count( $pointInfo['points'] ) == 0 )
{
	$bounds = array(
		'lonMin' => $filter['W'],
		'lonMax' => $filter['E'],
		'latMin' => $filter['S'],
		'latMax' => $filter['N'],
		'tsMax'  => PHP_INT_MAX,
		'tsMin'  => 0,
	);
}


function writeBounds($bounds, $filename)
{
	list($west, $south, $east, $north) = explode( ' ', exec( "./correct-coords.py {$bounds['lonMin']} {$bounds['latMin']} {$bounds['lonMax']} {$bounds['latMax']}" ) );

	$env = <<<ENDCONF
LAT1=$west
LON1=$south
LAT2=$east
LON2=$north

ENDCONF;

	file_put_contents( $filename, $env );
}

$editBbox = array();
$calculated = $editBbox[0] = $bounds;
$maxBoxes = 5;

foreach ( $files as $file )
{
	preg_match( "@^{$argv[1]}/([a-z]*)-([0-9]{8}-[0-9]{4})@", $file, $m );
	if ( $DO_ZOOM )
	{
		// read edit bounds
		$newBbox = (array) json_decode( file_get_contents( $argv[1] . DIRECTORY_SEPARATOR . 'editbbox-' . $m[2] . '.json' ) );

		if ($newBbox['latMin'] != 90) {
			for ( $i = $maxBoxes; $i > 0; $i-- )
			{
				if ( isset( $editBbox[$i - 1] ) )
				{
					$editBbox[$i] = $editBbox[$i - 1];
				}
			}
			$editBbox[0] = $newBbox;
			$nothing = 0;
		} else {
			$nothing++;
		}
		if ($nothing > 50) {
			for ( $i = $maxBoxes; $i > 0; $i-- )
			{
				if ( isset( $editBbox[$i - 1] ) )
				{
					$editBbox[$i] = $editBbox[$i - 1];
				}
			}
			$editBbox[0] = array(
				'lonMin' => ($bounds['lonMin'] + ((ZOOMOUTNESS - 1) * $calculated['lonMin'])) / ZOOMOUTNESS,
				'latMin' => ($bounds['latMin'] + ((ZOOMOUTNESS - 1) * $calculated['latMin'])) / ZOOMOUTNESS,
				'lonMax' => ($bounds['lonMax'] + ((ZOOMOUTNESS - 1) * $calculated['lonMax'])) / ZOOMOUTNESS,
				'latMax' => ($bounds['latMax'] + ((ZOOMOUTNESS - 1) * $calculated['latMax'])) / ZOOMOUTNESS,
			);
		}

		$lonMin = $latMin = 180;
		$lonMax = $latMax = -180;
		foreach ( $editBbox as $box )
		{
			$lonMin = $box['lonMin'] < $lonMin ? $box['lonMin'] : $lonMin;
			$latMin = $box['latMin'] < $latMin ? $box['latMin'] : $latMin;
			$lonMax = $box['lonMax'] > $lonMax ? $box['lonMax'] : $lonMax;
			$latMax = $box['latMax'] > $latMax ? $box['latMax'] : $latMax;
		}
		$calculated = array(
			'lonMin' => ($lonMin + ((ZOOMINNESS - 1) * $calculated['lonMin'])) / ZOOMINNESS,
			'latMin' => ($latMin + ((ZOOMINNESS - 1) * $calculated['latMin'])) / ZOOMINNESS,
			'lonMax' => ($lonMax + ((ZOOMINNESS - 1) * $calculated['lonMax'])) / ZOOMINNESS,
			'latMax' => ($latMax + ((ZOOMINNESS - 1) * $calculated['latMax'])) / ZOOMINNESS,
		);
	}
	writeBounds( $calculated, $argv[1] . DIRECTORY_SEPARATOR . 'diff-' . $m[2] . '.env' );

	$area = array('current' => $editBbox, 'calculated' => $calculated );
	file_put_contents( $argv[1] . DIRECTORY_SEPARATOR . 'diff-' . $m[2] . '.json', json_encode( $area ) );

	preg_match( "@^{$argv[1]}/([a-z]*)-(([0-9]{8})-([0-9]{4}))@", $file, $m );
	$fname = $argv[1] . DIRECTORY_SEPARATOR . 'diff-' . $m[2] . '.png';

	$ts = strtotime( "{$m[3]} {$m[4]} Europe/London" );

	if ( !file_exists( $fname ) )
	{
		echo "Rendering $fname\n";
		renderPoints($points, $bounds, $ts, $fname, $calculated, $gpxPoints);
	}
	else
	{
		echo "Skipping $fname\n";
	}
}

function renderPoints($points, $bounds, $ts, $filename, $bounds, $gpxPoints )
{
	list($west, $south, $east, $north) = explode( ' ', exec( "./correct-coords.py {$bounds['lonMin']} {$bounds['latMin']} {$bounds['lonMax']} {$bounds['latMax']}" ) );

	$dWidth =  WIDTH / ($east - $west);
	$dHeight = HEIGHT / ($north - $south);
	// normal points

	// Weeks-long video (with a frame every 15 minutes) {
	$fadeOff = 5;
	$tsMin = $ts - (1 * 21600); // (6 hour fade)
	// }

	// Months-long video (with a frame every 12 hours) {
	$fadeOff = 15;
	$tsMin = $ts - (60 * 86400); // (60 days fade)
	// }

	$tsMax = $ts;
	$dTime = $fadeOff / ($tsMax - $tsMin);
	$dSize = 3 / ($tsMax - $tsMin);
	// gpx points
	$gpxFadeOff = 3600;
	$gpxTsMin = $ts - (43200*60); // 720 hours
	$gpxTsMax = $ts;
	$gpxDTime = $gpxFadeOff / ($gpxTsMax - $gpxTsMin);
	$gpxDSize = 3 / ($gpxTsMax - $gpxTsMin);

	$img = imagecreatetruecolor(WIDTH, HEIGHT);
//	imageantialias($img, true);
	$white = imagecolorallocate($img, 255, 255, 255);
	$blue = imagecolorallocate($img, 0, 0, 255);
	imagecolortransparent($img, $white);
	imagefilledrectangle($img, 0, 0, WIDTH, HEIGHT, $white);

	$colorDefs = array(
		array( 245, 121,   0 ), // f57900
		array( 115, 210,  22 ), // 73d216
		array(  52, 101, 164 ), // 3465a4
		array( 117,  80, 123 ), // 75507b
		array( 204,   0,   0 ), // cc0000
	);

	foreach ( $colorDefs as $idx => $colorDef )
	{
		for( $i = 0; $i <= $fadeOff; $i++ )
		{
			$colour[$idx][$i] = imagecolorallocatealpha($img, $colorDef[0], $colorDef[1], $colorDef[2], 127 - (127 * ($i / $fadeOff)));
		}
		for( $i = 0; $i <= $gpxFadeOff; $i++ )
		{
			$gpxColour[$idx][$i] = imagecolorallocatealpha($img, $colorDef[0], $colorDef[1], $colorDef[2], 127 - (127 * ($i / $gpxFadeOff)));
		}
	}
	$past = imagecolorallocate($img, 127, 0, 0);


	if ( $GLOBALS['DO_POINTS'] )
	{
		imagesetthickness( $img, 2 );

		if ( $GLOBALS['DO_GPX'] )
		{
			foreach ( $gpxPoints as $key => $node )
			{
				if ( $key > 0 && $node['ts'] >= $gpxTsMin && $node['ts'] <= $gpxTsMax )
				{
					$c2 = (int) ($node['ts'] - $gpxTsMin) * $gpxDTime;
					if (
						(abs($gpxPoints[$key-1]['lon'] - $node['lon']) < 0.001) &&
						(abs($gpxPoints[$key-1]['lat'] - $node['lat']) < 0.001)
					) {
						imageline($img,
							($gpxPoints[$key-1]['lon'] - $west) * $dWidth, ($north - $gpxPoints[$key-1]['lat']) * $dHeight,
							($node['lon'] - $west) * $dWidth, ($north - $node['lat']) * $dHeight,
							$gpxColour[$node['uid'] % count( $colorDefs )][$c2]
						);
					}
				}
			}
		}

		imagesetthickness( $img, 1 );

		foreach ( $points as $node )
		{
			if ( $node[2] >= $tsMin && $node[2] <= $tsMax )
			{
				$c1 = 30 - ($node[2] - $tsMin) * $dTime;
				$c2 = (int) ($node[2] - $tsMin) * $dTime;
				imagefilledellipse($img,
					($node[1] - $west) * $dWidth,
					($north - $node[0]) * $dHeight,
					(10 + $c1)/3, (10 + $c1)/3,
					$colour[$node[3] % count( $colorDefs )][$c2]
				);
/*				imageellipse($img,
					($node[1] - $west) * $dWidth,
					($north - $node[0]) * $dHeight,
					9.5 + $c1, 9.5 + $c1,
					$colour[$node[3] % count( $colorDefs )][$c2]
				);*/
			}
			else if ( $node[2] < $tsMin )
			{
	//			imagefilledellipse($img, ($node[1] - $west) * $dWidth, ($north - $node[0]) * $dHeight, 3, 3, $past);
			}
		}
	}

	/* Render date */
	imagestring($img, 4, 10, HEIGHT - 20, date_create("@$ts")->format( "F jS, Y - H:i"), $blue);
	imagepng($img, $filename, 9);
}

function getEditPoints( $dir, $file, $filter )
{
	$filename = $dir . DIRECTORY_SEPARATOR . 'diff-' . $file . '.osc';
	echo "- Reading points from '$filename'\n";
	$sxe = simplexml_load_file( $filename );
	$pointInfo = \OSM\getInfo::getEditPoints( $sxe, $filter );
	file_put_contents( $dir . DIRECTORY_SEPARATOR . 'editbbox-' . $file . '.json', json_encode( $pointInfo['meta'] ) );
	return $pointInfo;
}
