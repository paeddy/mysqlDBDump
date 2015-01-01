<?php
#######################
# 
# DB-Dump Erzeugung
# Version: 1.0 (01.01.2014)
# Paetrick Vogt < paetrickvogt@gmail.com >
# 
#######################

include 'dumpConfig.inc.php'; # Anpassen an die Originale PHP-Datei im Projekt, sonst werden die Standard Zugangsdaten benutzt

###################
# Ab hier - Finger weg !!
###################

# Globale Variablen

$aServerDateien = array();
$MysqlServerOK = 0;
# Pfad Einstellungen für Datei-Export

$sFilepath = "";
if(strtolower(substr(trim(PHP_OS), 0, 3)) == 'win'){
	$sFilepath = $sAusgabeOrdnerWin;
}else if(strtolower(substr(trim(PHP_OS), 0, 5)) == 'linux'){
	$sFilepath = $sAusgabeOrdnerLinux;
}
leseVorhandeneDateien($sFilepath);


print "
<html>
<head>
<meta charset='utf-8'>
<script src=\"script/jquery.js\"></script>
<script src=\"script/jquery.ui.widget.js\"></script>
<script src=\"script/jquery.fileupload.js\"></script>
<script src=\"script/jquery.iframe-transport.js\"></script>

<script>
var files;
\$(function() {
	 $('#fileupload').fileupload({
        dataType: 'json',
        done: function (e, data) {
         ladeImportDetails(data.result.pfad);
        }
    });
    
	// Add events
	\$('#butDateiUpload').on('click', uploadData);
	\$('#selVorhandeneDatei').change(function (){ 
		resetAnsicht(1);
		if(getSelVorhandeneDatei() != '')
		{
			ladeImportDetails(getSelVorhandeneDatei());
		}
	});
});

function getSelVorhandeneDatei()
{
	return \$('#selVorhandeneDatei').val();
}

function ladeImportDetails(sFilename)
{
	if(sFilename != '')
	{
		\$.ajax({
			dataType: 'json',
			url: 'mysql_dump_ajax.php',
			data: {'getAjaxDateiInformationen':1, 'filename': sFilename}
		}).done(function(oData) {
			zeichneImportDetailTabelle(oData);
		});
	}
}

function zeichneImportDetailTabelle(oData)
{
	var sText = '<div id=\"spanDateiname\">Datei: ' + oData.info.dateiname + '</div>';
	sText += '<table><tr><th>Tabelle</th><th>Datensätze</th></tr>';
	\$.each(oData.tabellen, function(i,el){
		sText += '<tr><td>' + el.name + '</td><td>' + el.anzahl + '</td></tr>';
	});
	sText += '</table>';
	
	if(oData.info.dateiname != '')
	{
		sText += '<button id=\"butStarteImport\" onclick=\"starteImportVorgang(\\'' + oData.info.dateiname + '\\');\">Import Starten</button>';
	}
	\$('#divDateiDetails').html(sText);
}

function starteImportVorgang(sFilename)
{
	if(sFilename != '')
	{
		createImportAnsicht(1);
		\$('#butStarteImport').hide();
		\$('#selVorhandeneDatei').prop('disabled','disabled');
		\$('#fileupload').prop('disabled','disabled');
		
		\$.ajax({
			dataType: 'json',
			url: 'mysql_dump_ajax.php',
			data: {'getAjaxStartImport':1, 'filename': sFilename}
		}).done(function(oData) {
			createImportAnsicht(2,oData);
		});
	}
}

function resetAnsicht(ebene)
{
	if(ebene <= 1)
	{
		\$('#divDateiDetails').html('');
	}
}

function createImportAnsicht(ebene, oData)
{
	\$('#divImportDetails').html('');
	var sText = '<h2>Imports-Status</h2>';

	if(ebene == 1)
	{
		sText += '<h2>Import gestartet, dies kann einige Zeit dauern. Bitte warten</h2>';
	}
	else if (ebene == 2)
	{
		sText += '<table><tr><th></th><th>Status</th></tr>';
		sText += '<tr><td>Status</td><td>' + oData.status+ '</td></tr>';	
		sText += '<tr><td>Datei</td><td>' + oData.pfad+ '</td></tr>';	
		sText += '<tr><td>DB-Alt</td><td>' + oData.db_alt+ '</td></tr>';	
		sText += '<tr><td>DB-Neu</td><td>' + oData.db_neu+ '</td></tr>';	
		sText += '</table>';	
		if(oData.status == 'OK')
		{
			sText += '<h2 style=\"color: green;\">Import-Vorgang erfolgreich!</h2>';
		}
		else
		{
			sText += '<h2 style=\"color: red;\">Import-Vorgang fehlerhaft!</h2>';
		}
	}
	\$('#divImportDetails').html(sText);
}

function uploadData()
{
	if(\$('#inputDatei').val() != '')
	{
		\$.ajax({
			dataType: 'json',
			url: 'mysql_dump_ajax.php',
			data: {'uploadFile':1, 'file': \$('#inputDatei').val()}
		}).done(function(oData) {

		});
	}
}

</script>
<style>

.txtGruen{
	color: green;
}

.txtRot{
	color: red;
}

.dataWrapper{
	width: 100%;
}

th, td {
	text-align: left;
	padding: 2px 5px 2px 5px;
}

label{
	display: inline-block;
	min-width: 180px;
	width: 180px;
}

#divDateiDetails
{
	margin-top: 20px;
}

#spanDateiname
{
	margin-bottom: 10px;
}

#spanServer
{
	font-weight: bolder;
}

.txtGreen
{
	font-weight: bolder;
	color: green;
}

.txtRed
{
	font-weight: bolder;
	color: red;
}

</style>

</head>
<body>
<div id=\"dataWrapper\">
<h2>DB-Import</h2>
<div style=\"color:red\">VORSICHT: Es wird eine vorhandenen Datenbank gelöscht und mit dem Backup überschrieben!</div>
<label for=\"spanServer\">Server:</label><span id=\"spanServer\">$sDBServer</span> " .checkDBServer() ."<br>";
if($MysqlServerOK == 1)
{
	print "
<label for=\"selVorhandeneDatei\">vorhandene Datei:</label><select id=\"selVorhandeneDatei\">
<option value=\"\">Bitte wählen</option>";
for($i = 0; $i < count($aServerDateien); $i++)
{
	print "<option value=\"" .$aServerDateien[$i]['pfad'] ."\"> " .$aServerDateien[$i]['anzeigeName'] ."</option>";
}

print "</select>
<br>
<label for=\"fileupload\">Datei Auswahĺ:</label><input id=\"fileupload\" type=\"file\" name=\"importFile\" data-url=\"mysql_dump_ajax.php\" multiple><br>
<div id=\"divDateiDetails\"></div>
<div id=\"divImportDetails\"></div>";
}
print "
</body>
</html>";

print $sHtmlAusgabe;



function leseVorhandeneDateien($sPfad)
{
	global $aServerDateien;
	$files = array();
	if ( is_dir ( $sPfad ))
	{
		if ( $handle = opendir($sPfad) )
		{
			while (($file = readdir($handle)) !== false)
			{
		
			if ($file != "." && $file != "..") {
				preg_match('/^database_backup_(\d*)_.*\.sql$/', $file, $treffer);
				$timestamp = $treffer[1];
				$timestamp = 'a' .$timestamp;
				$files[$timestamp] = $file;
			}
       
			}
			closedir($handle);
		}
	}
	
	rsort($files);
	
	foreach($files as $file) {
		if(preg_match('/^database_backup_.*\.sql$/',$file))
		{
			preg_match('/^database_backup_(\d*)_(.*)\.sql$/', $file, $treffer);
			$timestamp = erzeugeZeitstempel($treffer[1]);
			$server = $treffer[2];

			$hTemp = array();
			$hTemp['server'] = $server;
			$hTemp['pfad'] = $sPfad.$file;
			$hTemp['Zeit'] = $timestamp;
			$hTemp['anzeigeName'] = $server ." (" .$timestamp .")";
			
			if(preg_match('/^\d{2}\.\d{2}\.\d{4}\s\d{2}:\d{2}:\d{2}$/', $timestamp))
			{
				array_push ( $aServerDateien , $hTemp);
			}
		}				
    }
}

function erzeugeZeitstempel($sZeitstempel)
{
	preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $sZeitstempel, $treffer);
	$sZeitstempelReturn = $treffer[3] ."." .$treffer[2] ."." .$treffer[1] ." "  .$treffer[4] .":" .$treffer[5] .":" .$treffer[6];
	return $sZeitstempelReturn;
}

function checkDBServer()
{
	global $sDBServer;
	global $sDBUser;
	global $sDBPass;
	global $MysqlServerOK;
	$oDBConnect = new mysqli( $sDBServer, $sDBUser, $sDBPass);
	if(!$oDBConnect->ping()){
		$sReturn = "<span class=\"txtRed\"> (Server nicht erreichbar) </span>";
	}
	else
	{
		$sReturn = "<span class=\"txtGreen\"> (Server erreichbar) </span>";
		$MysqlServerOK = 1;
	}
	$oDBConnect -> close();
	return $sReturn;
}

?>
