<?php
/*******************************************************************
* GimmeTheFile checks.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
*
* This script performs the application requirements checks server side
* Mainly: PHP version, libcURL availability and 7-Zip archiver.
********************************************************************/
require_once './includes/init.php';

//-----------------------------------------------------
// Ensure session has not expired
if (empty($_SESSION['settings'])) {
	echo "<span class=\"error-color\">ERROR:</span> Session has expired. <a href=\"index.php\">Reload</a> the page.";
	exit(0);
}

//=================================================================================================
// Checking requirements
//=================================================================================================
$requirements = array();
$error = array();

// Find PHP version
$phpVersion = ( $tmp = strpos(PHP_VERSION, '-') ) ? substr(PHP_VERSION, 0, $tmp) : PHP_VERSION;

// Check above 5 and if not, add error text
if ( ! ( $ok = version_compare($phpVersion, '5', '>=') ) ) {
	$error[] = "GimmeTheFile requires at least PHP 5 or greater.<br>";
}

// Add to requirements
$requirements[] = array('name'  => 'PHP version',
					  'value' => $phpVersion,
					  'ok'    => $ok);

//--------------------------------------------------------
// Check libcurl
if ( ! ( $ok = function_exists('curl_version') ) ) {
	$error[] = "GimmeTheFile requires the libcURL library to be installed (or enabled) on your server. <span class=\"info\">(check <a href=\"http://curl.haxx.se/libcurl/php/\">this page</a> to install it)</span><br>";
}

// curl version
$curlVersion   = $ok && ( $tmp = curl_version() ) ? $tmp['version'] : 'Not available';

// Add to requirements
$requirements[] = array('name'  => 'cURL version',
					  'value' => $curlVersion,
					  'ok'    => $ok);

//--------------------------------------------------------
// Check 7zip
if (strtolower(substr(php_uname('s'), 0, 3)) === 'win') {
	$archiverPath="\"".$_SESSION['settings']['7z.WindowsPath']."\"";
}
else {
	$archiverPath=$_SESSION['settings']['7z.UnixPath'];
}

if (empty($archiverPath)) {
	$error[] = "The 7-Zip archiver path is not properly set in the settings.ini file.<br>";
	$ok = false;
}
else {
	$output=array();
	exec($archiverPath,$output,$returnVal);
	$returnVal == 0 ? $ok = true : $ok = false;
	if (!$ok) {
		$error[] = "GimmeTheFile requires 7-Zip archiver to be installed on your server.  <span class=\"info\">(check <a href=\"http://www.7-zip.org/\">this page</a> to install it)</span><br>";
	}
}

$requirements[] = array('name'  => '7zip Archiver',
					  'value' => ($ok ? 'Available' : 'Not available'),
					  'ok'    => $ok);

//--------------------------------------------------------
// Print errors if any
if (!empty($error)) {
	echo "<h2 class=\"error-color\">Some errors were found</h2>";
	echo "<div class=\"paragraph\"><ul>";
	foreach ($error as $msg) { echo "<li>".$msg."</li>"; };
	echo "</div></ul>";
};
					  
//--------------------------------------------------------
// Print requirements
echo <<<OUT
<h2>Checking server requirements</h2>
<div class="paragraph">
<ul>
OUT;

foreach ( $requirements as $li ) {
	echo "<li>{$li['name']}: <span class=\"" . ( ! $li['ok'] ? ' error-color' : 'ok-color' ) . "\">{$li['value']}</span></li>\n";
}

// End requirements
echo "</ul></div>";