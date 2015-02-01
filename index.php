<?php
/*******************************************************************
* GimmeTheFile index.php script
*
* This application is available and distributed under the GPLv3 licence
* available here: http://www.gnu.org/licenses/gpl.html and also in the 
* gpl-3.0_licence.txt file included with the GimmeTheFile package.
*
* This is the webroot document. It displays the main form and allows
* the user to launch the server requirement checks.
********************************************************************/
require './includes/init.php';

//-------------------------------------------------------------------------------------
// Read the settings file for once and store all informations into the session for further use
$_SESSION['settings'] = parse_ini_file(GIMMETHEFILE_SETTINGS);
?>

<!DOCTYPE html>
<html>
<head>
<title>GimmeTheFile</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
<div class="title"></div>
<div class="main">
<h2>About: <a href="#"><span id="showAbout" class="fa fa-caret-square-o-right"></span></a></h2>
<div id="about" class="paragraph" style="display: none">
	<i>GimmeTheFile</i> downloads a file based on a user provided URL. As such it works as a standard web proxy. It then transforms the file by performing either an AES-256 encryption with a user provided password, or a simple base64 encoding. The resulting file is eventually made available for download with a temporary link. It comes in handy when users can't download a file because they're behind a filtering http proxy with antivirus capabilities preventing it, either because the content of the file is considered harmful by the antivirus, or because the URL is forbidden by the http proxy.<br>
	<i>GimmeTheFile</i> can also be useful if a desktop local antivirus won't let the file being stored on a the hard drive before even being able to move it to another location. The fact that the file is encrypted prevents antiviruses to analyse its content.<br>
	<br>
	<i>When to use AES-256 or Base64 ?</i><br>
	<b>AES-256</b>:
	<div style="padding-left: 15px;">PROS: Safer, no-one in the middle can see the actual content of the file that is being downloaded without knowing the decryption key.<br>
	CONS: Some http proxies might simply drop files that cannot be analyzed.</div>
	<b>BASE-64</b>:
	<div style="padding-left: 15px;">PROS: If the encrypted zip doesn't pass the proxy, this might do the trick and should be left undetected by the antivirus.<br>
	CONS: There's an overhead on the file size (bigger). The file can be decoded by anyone in the middle.</div>
</div>
<br>
<div class="paragraph">
Having errors executing the application ? Launch the <a id="checks" style="cursor:pointer;">server requirements checks</a>.</div>
<div id="checkresults" style="display: none;">Please wait while server requirements are being checked <span class="fa fa-spinner fa-spin"></span></div>
<?php
//=================================================================================================
// Set some variables
//=================================================================================================
$userAgent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

//=================================================================================================
// Print the Form
//=================================================================================================
echo <<<OUT
<h2>File Download:</h2>
<form id="fileForm">
	<div class="paragraph">
		URL:<br>
		<input type="text" name="fileUrl" id="fileUrl" size="60">
		<br>
		Choose the file transformation type:<br>
		<input onclick="document.getElementById('fileEncryptOptions').style.display = ''" type="radio" name="transformType" value="zip" checked>AES-256 encrypted zip
		<input onclick="document.getElementById('fileEncryptOptions').style.display = 'none'" type="radio" name="transformType" value="b64">Base64 encoding
		<br>
		<div id="fileEncryptOptions">
			Password used to encrypt the file:<br>
			<input type="text" name="encryptionPassword" id="encryptionPassword" size="25"><br>
		</div>
		<input type="button" id="submit" value="Go">
		[<a style="cursor:pointer;" onclick="document.getElementById('options').style.display = (document.getElementById('options').style.display=='none'?'':'none')">More options</a>]
	</div>
    <div id="options" class="options" style="display: none;">
		Some cURL request parameters can be customized if necessary:
		<ul>
			<li>UserAgent <span class="info">(by default, same as your current browser)</span>:<br>
			<input type="text" size="40" name="curlUserAgent" id="curlUserAgent" value="{$userAgent}">
			<li>Referer <span class="info">(can be left empty)</span>:<br>
			<input type="text" size="40" name="curlReferer" id="curlReferer">
			or use the provided URL host as the referer <input type="checkbox" name="useURLHostAsReferer" onclick="document.getElementById('curlReferer').disabled=(document.getElementById('curlReferer').disabled?false:true);">
		</ul>
	</div>
</form>
OUT;

//--------------------------------------------------------
// The div containing the result
echo <<<OUT
<div class="paragraph"><div id="result" class="result"></div>
</div></div>
<div class="footer">2014 GimmeTheFile [<a href="admin.php">admin page</a>]</div>
OUT;
?>

<!-- ================================ jquery Script ================================= -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	var aboutIsExpanded = false;

	$("#showAbout").click(function() {
		if (aboutIsExpanded == false) {
			$(this).removeClass("rotate_back");
			$(this).addClass("rotate");
			$("#about").slideDown();
			aboutIsExpanded = true;
		}
		else {
			$(this).removeClass("rotate");
			$(this).addClass("rotate_back");
			$("#about").slideUp();
			aboutIsExpanded = false;
		}		
	});

	$("#submit").click(function() {
		// Get form parameters attributes and perform basic checks, even if all these will be checked server side as well
		var fileUrl = $("#fileUrl").val();
		var transformType = $("input[name=transformType]:checked", "#fileForm").val();
		var encryptionPassword = $("#encryptionPassword").val();
		var curlUserAgent = $("#curlUserAgent").val();

		$("#result").show();
	
		if (fileUrl === '' || curlUserAgent === '' || (transformType === 'zip' && encryptionPassword === '')) {
			$("#result").html("Some fields are empty. Please fill them in before submiting.");
		}
		else {
				$("#result").html("Please wait while the file is being downloaded and transformed <span class=\"fa fa-spinner fa-spin\"></span>");
				$.ajax({
				url: './download.php',
				type: 'post',
				dataType: 'html',
				data: $("#fileForm").serialize(),
				success: function(data) { $("#result").html(data); }
				});
		}
	});
	
	$("#checks").click(function() {
		$("#checkresults").show();
		$("#checkresults").load("checks.php");
	});
});
</script>
</body>
</html>
