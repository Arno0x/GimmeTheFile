<?php
/*******************************************************************
* GimmeTheFile get.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
*
* This script sends the resulting file to the end user.
********************************************************************/
require './includes/init.php';

//-----------------------------------------------------
if (empty($_GET['transformType']) || ($_GET['transformType'] != "zip" && $_GET['transformType'] != "b64")) {
	echo "Error, wrong transformType. It can only be zip or b64";
	exit(0);
}

if ($_GET['transformType'] == "zip") {
	$resultFile=GIMMETHEFILE_RESULTDIR.session_id().".zip";
	$filename="result.zip";
}
elseif ($_GET['transformType'] == "b64") {
	$resultFile=GIMMETHEFILE_RESULTDIR.session_id().".b64";
	$filename="result.b64";
}

if (!file_exists($resultFile)) {
	echo "Error, requested file doesn't exist";
	exit (0);
}

//-----------------------------------------------------
// Sending no-cache headers
header('Cache-Control: no-store, no-cache, must-revalidate' );
header('Cache-Control: post-check=0, pre-check=0', false );
header('Pragma: no-cache' );
header('Content-Disposition: attachment; filename="'.$filename.'"');

set_time_limit(0);
$fileHandler = @fopen($resultFile,"rb");
while(!feof($fileHandler))
{
	print(@fread($fileHandler, 1024*8));
	ob_flush();
	flush();
}
?>