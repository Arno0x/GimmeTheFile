<?php
/*******************************************************************
* GimmeTheFile download.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
* 
* This script performs the actual download of the user provided file URL
* and then calls the 7-Zip archiver to encrypt it with AES-256 and a user provided
* password.
********************************************************************/
require './includes/init.php';

//-----------------------------------------------------
// Sending no-cache headers
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

//-----------------------------------------------------
// Ensure session has not expired
if (empty($_SESSION['settings'])) {
	echo "<span class=\"error-color\">ERROR:</span> Session has expired. <a href=\"index.php\">Reload</a> the page.";
	exit(0);
}

//-----------------------------------------------------
// Perform user input processing and sanitization

// Ensure none of the expected parameters are empty (just in case local javascript tests were bypassed)
if (empty($_POST['fileUrl']) || empty($_POST['transformType']) || empty($_POST['curlUserAgent']) || ($_POST['transformType'] =="zip" && empty($_POST['encryptionPassword']))) {
	echo "<span class=\"error-color\">ERROR:</span> MISSING FORM PARAMETERS";
	exit(0);
}

// Ensure the fileUrl is a proper URL
if(!filter_var($_POST['fileUrl'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
	echo "<span class=\"error-color\">ERROR:</span> INVALID FILE URL<br>".$_POST['fileUrl'];
	exit(0);
}
if (!preg_match('#^((https?)://(?:([a-z0-9-.]+:[a-z0-9-.]+)@)?([a-z0-9-.]+)(?::([0-9]+))?)(?:/|$)((?:[^?/]*/)*)([^?]*)(?:\?([^\#]*))?(?:\#.*)?$#i', $_POST['fileUrl'])) {
	echo "<span class=\"error-color\">ERROR:</span> INVALID FILE URL<br>".$_POST['fileUrl'];
	exit(0);
}
$fileURL = filter_var($_POST['fileUrl'],FILTER_SANITIZE_URL);

// Ensure the transformType is valid
if ($_POST['transformType'] != "zip" && $_POST['transformType'] != "b64") {
	echo "<span class=\"error-color\">ERROR:</span> UNKNOWN TRANSFORM TYPE. CAN ONLY BE ZIP OR BASE64";
	exit(0);
}
$transformType = $_POST['transformType'] ;

// If user wants to set the cURL referer, either manually set or using the host from the provided file URL
if (!empty($_POST['useURLHostAsReferer'])) {
	$curlReferer = parse_url($fileURL,PHP_URL_HOST);
}
elseif (!empty($_POST['curlReferer'])) {
	$curlReferer = $_POST['curlReferer'];
}

// Sanitize the user provided encryption password
$encryptionPassword = escapeshellarg($_POST['encryptionPassword']);

// No sanitization done on the UserAgent
$curlUserAgent = $_POST['curlUserAgent'];

//=================================================================================================
// Preparing the local temp file to store the result of the request
//=================================================================================================

// Get the file extension. If we can't find it now, we'll try to guess it later from the Content-Type returned
$fileExtension = pathinfo (parse_url($fileURL,PHP_URL_PATH), PATHINFO_EXTENSION);
if (!empty($fileExtension)) {
	$tempFile = GIMMETHEFILE_TMPDIR.session_id().".".$fileExtension;
}
else {
	$tempFile = GIMMETHEFILE_TMPDIR.session_id();
}
$tempFileHandler = fopen($tempFile, "w");

if ($tempFileHandler == false) {
	echo "<span class=\"error-color\">ERROR:</span> COULD NOT OPEN LOCAL FILE FOR WRITING";
	exit (0);
}

//=================================================================================================
// Perform the CURL request
//=================================================================================================

//-----------------------------------------------------
// Preparing the CURL request
$curlHandler = curl_init($fileURL);
curl_setopt($curlHandler, CURLOPT_FILE, $tempFileHandler); // Set the output file for the curl request
curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true); // Follow http redirections
curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false); // Do NOT verify SSL certificate of the remote server, we leave it up to the user to know what he's doing...
curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 30); // Wait 30s maximum while trying to connect to the remote server
curl_setopt($curlHandler, CURLOPT_MAXREDIRS, 5); // Following a maximum of 5 redirections
curl_setopt($curlHandler, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS); // Only allow http(s) and ftp(s) protocol for connection to the remote server
curl_setopt($curlHandler, CURLOPT_USERAGENT, $curlUserAgent); // Define the UserAgent to be used for requests to the remote server.
if (isset($curlReferer)) {
	curl_setopt($curlHandler, CURLOPT_REFERER, $curlReferer); // Define the Referer to be used for requests to the remote server.
}

// Create a download progress callback function
function callback($expectedDownloadBytes, $downloadedBytes, $expectedUploadBytes, $uploadedBytes) {
	// If downloadedBytes exceeds the configured limit, returning non-0 breaks the connection
    return ($downloadedBytes > ($_SESSION['settings']['Curl.MaxDownloadSizeInMB'] * 1024 * 1024)) ? 1 : 0;
}
curl_setopt($curlHandler, CURLOPT_BUFFERSIZE, 128); // more progress info
curl_setopt($curlHandler, CURLOPT_NOPROGRESS, false);
curl_setopt($curlHandler, CURLOPT_PROGRESSFUNCTION, 'callback' );

//-----------------------------------------------------
// Execute the CURL request
$curlResult = curl_exec($curlHandler);

// Close the temp file Handle
fclose($tempFileHandler);

if ($curlResult == true) {
	echo "<div style=\"color: #00ff00;\">STEP 1: File successfully downloaded.</div>";
}
elseif (curl_errno($curlHandler) == CURLE_ABORTED_BY_CALLBACK) {
	echo "<span class=\"error-color\">ERROR:</span> THE DOWNLOADED FILE SIZE EXCEEDS THE AUTHORIZED LIMIT OF ".$_SESSION['settings']['Curl.MaxDownloadSizeInMB']." MB";
	curl_close($curlHandler);
	exit(0);
}
else {
	echo "<span class=\"error-color\">ERROR:</span> AN ERROR OCCURED WHILE TRYING TO DOWNLOAD THE FILE";
	curl_close($curlHandler);
	exit(0);
}

//-----------------------------------------------------
// If the file extension was not available, retrieve 
// the Content-Type from the HTTP answer to try to map an
// extension file from the mime types
if (empty($fileExtension)) {
	$contentType = strtolower(curl_getinfo($curlHandler, CURLINFO_CONTENT_TYPE));
	
	// If a content type was found in the remote server answer
	if (!empty($contentType)) {
		$pattern="/".str_replace("/","\/",$contentType)."/";
		
		// Searching the file extension corresponding to this mime type
		$grepResult = preg_grep($pattern, file(GIMMETHEFILE_MIMETYPES));
		if (!empty($grepResult)) {
			// Some mime types have multiple extensions define, just keep the first one
			$fileExtension = explode(",",trim(array_values($grepResult)[0]))[1];
			rename($tempFile,$tempFile.".".$fileExtension);
			$tempFile = $tempFile.".".$fileExtension;
		}
	}
}

//-----------------------------------------------------
// Close the Curl Handler
curl_close($curlHandler);

//=================================================================================================
// Log the request
//=================================================================================================

// If logging is enabled
if ( $_SESSION['settings']['Log.Enabled'] === "true") {

	// LogFile name and path
	$logFile = GIMMETHEFILE_LOGDIR  . date('Y-m-d') . '.log';

	// Log line
	$logLine = $_SERVER['REMOTE_ADDR'] . "," . date('d/M/Y:H:i:s O') . "," . $fileURL . "\r\n";

	// Do it
	file_put_contents($logFile, $logLine, FILE_APPEND);
}

//=================================================================================================
// Now perform the transformation on the file depending on the user selected option:
// Either an AES256 zip encryption, using the 7za archiver using the user provided password to encrypt it
// Or a simple base64 encoding
//=================================================================================================
if ($transformType == "zip") {
	$resultFile=GIMMETHEFILE_RESULTDIR.session_id().".zip";

	// Get the proper archiver path based on the OS type
	if (strtolower(substr(php_uname('s'), 0, 3)) === 'win') {
		$archiverPath="\"".$_SESSION['settings']['7z.WindowsPath']."\"";
	}
	else {
		$archiverPath=$_SESSION['settings']['7z.UnixPath'];
	}
	$command=$archiverPath." a -tzip -mem=AES256 -p".$encryptionPassword." ".$resultFile." ".$tempFile;

	// Call the archiver
	exec($command,$output,$returnVal);
	if ($returnVal == 0) {
	echo <<<OUT
	<div style="color: #00ff00;">STEP 2: File successfully encrypted.</div>
	<br>
	You can download the resulting file <a href="get.php?transformType=zip">here</a>
OUT;
	}
	else {
		echo "<span class=\"error-color\">ERROR:</span> AN ERROR OCCURED WHILE CALLING THE 7ZIP ARCHIVER";
	}
}
elseif ($transformType == "b64") {
	$resultFile=GIMMETHEFILE_RESULTDIR.session_id().".b64";
	
	$chunkSize = 1024;
	$src = fopen($tempFile, 'rb');
	$dst = fopen($resultFile, 'wb');
		
	// Copying temporary file to the result file while base64 encoding it on the fly
	stream_filter_append($dst, 'convert.base64-encode');
	while (!feof($src)) {
		fwrite($dst, fread($src, $chunkSize));
	}
	
	fclose($dst);
	fclose($src);
	
	echo <<<OUT
	<div style="color: #00ff00;">STEP 2: File successfully encoded.</div>
	<br>
	You can download the resulting file <a href="get.php?transformType=b64">here</a>
OUT;
}

//-----------------------------------------------------
// Delete the temporary file
//-----------------------------------------------------
unlink($tempFile);
?>