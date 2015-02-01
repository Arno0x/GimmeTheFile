<?php
/*******************************************************************
* GimmeTheFile init.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
*
* This script is included in all other php scripts. It defines some
* constants and starts the session handling.
********************************************************************/

//------------------------------------------------------
// Set up some constants
define('GIMMETHEFILE_ROOTDIR', str_replace('\\', '/', dirname(dirname(__FILE__))).'/');
define('GIMMETHEFILE_SETTINGS', GIMMETHEFILE_ROOTDIR.'settings.ini');
define('GIMMETHEFILE_MIMETYPES', GIMMETHEFILE_ROOTDIR.'includes/mime.types');
define('GIMMETHEFILE_LOGDIR', GIMMETHEFILE_ROOTDIR.'log/');
define('GIMMETHEFILE_TMPDIR', GIMMETHEFILE_ROOTDIR.'tmp/');
define('GIMMETHEFILE_RESULTDIR', GIMMETHEFILE_ROOTDIR.'result/');

//------------------------------------------------------
// Error display on LIVE installation
ini_set('display_errors', 0);

// Error display on DEV installation
//ini_set('display_errors', 1);

//------------------------------------------------------
// Start a PHP session to persist some ojects between requests
session_name('gtf');
session_cache_limiter('private_no_expire');
// The session will be valid for 5 minutes, for this application path only, for this domain only. Allow Cookies on http (secure=false). Set it to http_only.
$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
session_set_cookie_params (300, dirname($_SERVER['SCRIPT_NAME']), $domain, false, true);

// Create a session
session_start();
?>