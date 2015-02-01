<?php
/*******************************************************************
* GimmeTheFile admin.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
*
* This script handles all administration functionnalities.
********************************************************************/
require_once './includes/init.php';

//-----------------------------------------------------
// Sending no-cache headers
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

//-------------------------------------------------------------------------------------
// Read the settings file, if not already present in the session variable
if (!isset($_SESSION['settings'])) {
	$_SESSION['settings'] = parse_ini_file(GIMMETHEFILE_SETTINGS);
}

//-------------------------------------------------------------------------------------
// Functions for overwriting the settings.ini file
function write_ini_file($array, $file) {
	$res = array();
	foreach($array as $key => $val)	{
		$val = str_replace('\\','/',$val); // Replacing windows OS backslash in that path, if any...
		$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
	}
	safefilerewrite($file, implode("\n", $res));
}

function safefilerewrite($fileName, $dataToSave) {
	if ($fp = fopen($fileName, 'w')){
		$startTime = microtime();
		do {
			$canWrite = flock($fp, LOCK_EX);
		   // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
		   if(!$canWrite) usleep(round(rand(0, 100)*1000));
		} while ((!$canWrite)and((microtime()-$startTime) < 1000));

		//file was locked so now we can store information
		if ($canWrite) {
			fwrite($fp,"<?php\n");
			fwrite($fp, $dataToSave);
			fwrite($fp,"\n?>");
			flock($fp, LOCK_UN);
		}
		fclose($fp);
	}
}

//-------------------------------------------------------------------------------------
// Functions for converting file size unit. Quick and dirty...
function formatSizeUnits($bytes) {
	if ($bytes >= 1073741824) {
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	}
	elseif ($bytes >= 1048576) {
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	}
	elseif ($bytes >= 1024) {
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	}
	elseif ($bytes > 1) {
		$bytes = $bytes . ' bytes';
	}
	elseif ($bytes == 1) {
		$bytes = $bytes . ' byte';
	}
	else {
		$bytes = '0 bytes';
	}
	return $bytes;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>GimmeTheFile</title>
<link rel="stylesheet" type="text/css" href="css/style.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	$('.numbersOnly').keydown(function (e) {
			// Allow: backspace, delete, tab, escape, enter and .
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) || 
             // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
                 // let it happen, don't do anything
                 return;
			}
			// Ensure that it is a number and stop the keypress
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}
		});
});
</script>
<body>
<div class="title"></div>
<div class="main">
<h1 class="admin">GimmeTheFile Administration panel</h1>
<div class="menu">[<a href="admin.php?home">Home</a>] | [<a href="admin.php?viewlog">View Logs</a>] | [<a href="admin.php?settings">Edit Settings</a>] |
[<a href="admin.php?changepwd">Change Password</a>] | [<a href="admin.php?logout">Logout</a>]</div>
<?php
//===========================================================================
// 1. Process page input...
//===========================================================================
$display = "homePage"; // Default value
$error = array();

//-------------------------------------------------------------
// Whatever the query, we must first check if that's the first
// time this page is being called
if (!isset($_SESSION['settings']['Admin.Password'])) {
	if (!isset($_POST['password'])) { $display = "initAdminPage"; }
	else {
		if (!empty($_POST['password'])) {
			$_SESSION['settings']['Admin.Password'] = hash("sha256",$_POST['password']);
			$_SESSION['logged'] = true;
			
			// Persist the password into the settings file
			write_ini_file($_SESSION['settings'],GIMMETHEFILE_SETTINGS);
			$display = "homePage";
		}
		else {
			$error[] = "The administration password cannot be empty. Go back to the <a href=\"admin.php\">admin</a> page.";
			$display = "errorPage";
		}
	}
}
//-------------------------------------------------------------
// Next, whatever the query, we need to check whether or not
// the current user is actually logged in
elseif (!isset($_SESSION['logged'])) {
	if (!isset($_POST['password'])) { $display = "loginPage"; }
	else {
		if (hash("sha256",$_POST['password']) == $_SESSION['settings']['Admin.Password']) {
			$_SESSION['logged'] = true;
			$display = "homePage";
		}
		else {
			$error[] = "Wrong password. Go back to the <a href=\"admin.php\">login</a> page.";
			$display = "errorPage";
		}
	}
}
//-------------------------------------------------------------
// If we're here, that means the user verification succeeded
// Process the various possible queries
elseif (isset(array_keys($_GET)[0])) {
	 switch (array_keys($_GET)[0]) {
		//---------------------------------------------------------
		// Query = viewlog
		case "viewlog":
			// Display a specific log file passed as parameter
			if (!empty($_GET['file'])) {
				$fileName=basename($_GET['file']); // Using basename here to prevent directory path traversal attempts
				
				// Verify file name format. Log files formatted as YYYY-MM-DD.log
				if (strlen($fileName) == 14 && preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2})\.log$#', $fileName)) {
					$fileName = GIMMETHEFILE_LOGDIR.$fileName;
					if (!file_exists($fileName)) {
						$error[] = "Unknow log file. Go back to the <a href=\"admin.php?viewlog\">View Logs</a> page.";
						$display = "errorPage";
					}
					else { $display = "viewLogFile";}
				}
				else {
					$error[] = "Unknow log file. Go back to the <a href=\"admin.php?viewlog\">View Logs</a> page.";
					$display = "errorPage";
				}
			}
			// Display the list of log files
			else {
				$display = "viewLogList";
			}
		break;
		//---------------------------------------------------------
		// Query = settings
		case "settings":
			// No form submission, simply display the edit settings page
			if(empty($_POST)) {
				$display = "editSettingsPage";
			}
			// A form was submitted, process it
			else {
				// If any value is empty, we just keep the existing values
				$_SESSION['settings']['Log.Enabled'] = empty($_POST['logenabled']) ? "false" : "true";
				
				if (!empty($_POST['7zwindowspath'])) {
					//$_SESSION['settings']['7z.WindowsPath'] = escapeshellcmd($_POST['7zwindowspath']);
					$_SESSION['settings']['7z.WindowsPath'] = $_POST['7zwindowspath'];
				}
				if (!empty($_POST['7zunixpath'])) {
					$_SESSION['settings']['7z.UnixPath'] = escapeshellcmd($_POST['7zunixpath']);
				}
				if (!empty($_POST['curlmaxdownloadsize'])) {
					$_SESSION['settings']['Curl.MaxDownloadSizeInMB'] = ($_POST['curlmaxdownloadsize']);
				}
				// Save settings in the settings.ini file
				write_ini_file($_SESSION['settings'],GIMMETHEFILE_SETTINGS);
				$display = "settingsSavedPage";
			}
		break;
		//---------------------------------------------------------
		// Query = changepwd
		case "changepwd":
			if (!isset($_POST['password'])) { $display = "changePasswordPage"; }
			elseif (empty($_POST['password'])) {
				$error[] = "The administration password cannot be empty. Go back to the <a href=\"admin.php?changepwd\">Change Password</a> page.";
				$display = "errorPage";
			}
			else {
				$_SESSION['settings']['Admin.Password'] = hash("sha256",$_POST['password']);
				// Persist the password into the settings file
				write_ini_file($_SESSION['settings'],GIMMETHEFILE_SETTINGS);
				$display = "passwordChangedPage";
			}
			
		break;
		//---------------------------------------------------------
		// Query = home
		case "home":
			$display = "homePage";
		break;
		//---------------------------------------------------------
		// Query = logout
		case "logout":
			$_SESSION = array(); 
			session_unset();
			session_destroy();
			session_write_close();
			$display = "loginPage";
		break;
	}
}

//===========================================================================
// 2. ... then handle it over to the page display
//===========================================================================
switch ($display) {

	//---------------------------------------------------------
	// Error page
	case "errorPage":
		echo "<h2 class=\"error-color\">Some errors were found</h2>";
		echo "<div class=\"paragraph\"><ul>";
		foreach ($error as $msg) { echo "<li>".$msg."</li>"; };
		echo "</ul></div>";
		break;

	//---------------------------------------------------------
	// Set Administrator's password page
	case "initAdminPage":
		echo <<<OUT
		<h2>Set administration password</h2>
		<div class="paragraph">
			It is apparently the first time you're connecting to the administration panel. You first need to set an administration password.
			<br><br>
			<form action="admin.php" method="post">
				Enter the administration panel password:<br>
				<input type="password" name="password" size="40"> <input type="submit" value="Go">
			</form>
		</div>
OUT;
		break;
	
	//---------------------------------------------------------
	// Home page
	case "homePage" :
		echo <<<OUT
		<br>
		<h2>About</h2>
		<div class="paragraph">
			This is the GimmeTheFile administration panel.<br><br>From here, you can view the usage log files, edit the application settings or change your administrator's password.
			Use the top menu to navigate to these functions.
		</div>
OUT;
		include("checks.php");
	break;
	
	//---------------------------------------------------------
	// Login Page
	case "loginPage":
		echo <<<OUT
		<h2>Login</h2>
		<div class="paragraph">
			<form action="admin.php" method="post">
				Enter the administration panel password:<br>
				<input type="password" name="password" size="40"> <input type="submit" value="Ok">
			</form>
		</div>
OUT;
	break;
	
	//---------------------------------------------------------
	// Displaying log file list page
	case "viewLogFile":
		echo <<<OUT
		<h2>Log Files</h2><div id="paragraph">
		<pre>
OUT;
		readfile($fileName);
		echo "</pre></div>";
	break;
	
	//---------------------------------------------------------
	// Viewing log files list page
	case "viewLogList":
		echo <<<OUT
		<h2>Log Files</h2><div class="paragraph">
		These are the GimmeTheFile application usage log files. Most recent files are at the top.<br>
		<br>
		<table class="mytable" cellpadding="0" cellspacing="0">
		<tr class="mytableheader"><td width="250">File Name</td><td width="100">File Size</td></tr>
OUT;
		// Get the list of log files
		$logFilesList = array_reverse(glob(GIMMETHEFILE_LOGDIR."*.log"));
		
		// Go through all files
		foreach ( $logFilesList as $file ) {
			$fileSize = formatSizeUnits(filesize($file));
			$fileName = basename($file);
			
			// Verify files name format. Log files formatted as YYYY-MM-DD.log
			if (strlen($fileName) == 14 && preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2})\.log$#', $fileName)) {
				echo <<<OUT
				<tr><td width="150"><a href="admin.php?viewlog&file={$fileName}">{$fileName}</a></td><td width="50">{$fileSize}</td></tr>
OUT;
			 }
		}
		
		// Print table footer
		echo "</table></div>";
	break;
	
	//---------------------------------------------------------
	// Edit application settings page
	case "editSettingsPage":
	    $checked = $_SESSION['settings']['Log.Enabled'] === "true" ? "checked" : "";
		echo <<<OUT
		<h2>Edit application settings</h2>
		<div class="paragraph">
			These are the GimmeTheFile application settings. Don't get confused: these are <b>server side</b> settings :-)<br><br>
			<form action="admin.php?settings" method="post">
				<table class="mytable" cellpadding="0" cellspacing="0">
				<tr class="mytableheader">
				 <td>Setting</td>
				 <td>Value</td>
				<tr>
					<td>Logging enabled</td>
					<td><input type="checkbox" name="logenabled" {$checked}></td>
				</tr>
				<tr>
					<td>Curl maximum download size in MB</td>
					<td><input class="numbersOnly" type="text" name="curlmaxdownloadsize" size="50" value="{$_SESSION['settings']['Curl.MaxDownloadSizeInMB']}"></td>
				</tr>
				<tr>
					<td>7-Zip archiver Windows Path</td>
					<td><input type="text" name="7zwindowspath" size="50" value="{$_SESSION['settings']['7z.WindowsPath']}"></td>
				</tr>
				<tr>
					<td>7-Zip archiver Unix Path</td>
					<td><input type="text" name="7zunixpath" size="50" value="{$_SESSION['settings']['7z.UnixPath']}"></td>
				</tr>
				</table>
				<br>
				<input type="submit" value="Save">
			</form>
		</div>
OUT;
	break;
	
	case "settingsSavedPage":
		echo <<<OUT
			<br>
			<div class="paragraph ok-color">
				Settings successfully changed.
			</div>
OUT;
	break;
	
	//---------------------------------------------------------
	// Change administrator's password page
	case "changePasswordPage" :
		echo <<<OUT
		<h2>Change password</h2>
		<div id="paragraph">
			<form action="admin.php?changepwd" method="post">
				Enter the <b>new</b> administration panel password:<br><br>
				<input type="password" name="password" size="40"> <input type="submit" value="Ok">
			</form>
		</div>
OUT;
	break;
	
	//---------------------------------------------------------
	// Change administrator's password page
	case "passwordChangedPage" :
		echo <<<OUT
		<br>
		<div id="paragraph" class="ok-color">
			Password successfully changed.
		</div>
OUT;
	break;
	
}
?>
</div>
<div class="footer">2014 [<a href="index.php">GimmeTheFile</a>]</div>
</body>
</html>