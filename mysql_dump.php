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

$sAusgabeFormat = isset($argv[1]) && $argv[1] == "-noHTML" ? "konsole" :"html";
$sAusgabeText = "";
$sDumpPfad = "";

# Erzeuge Hilfe bei Konsolenausgabe

if(isset($argv[1]) && $argv[1] == "-help")
{
	createHelp();
	exit;
}

# Pfad Einstellungen für Datei-Export

$sFilepath = "";
if(strtolower(substr(trim(PHP_OS), 0, 3)) == 'win'){
	$sFilepath = $sAusgabeOrdnerWin;
}else if(strtolower(substr(trim(PHP_OS), 0, 5)) == 'linux'){
	$sFilepath = $sAusgabeOrdnerLinux;
}
$sFilename='database_backup_' .date('YmdHis') .'_' .$sDBServer .'.sql';
$sFile = $sFilepath .$sFilename;

# Data-Variablen für Dump-Pruefung
 
$hData['tempOK'] = 1;
$hData['dbServer'] = 0;
$hData['dbExist'] = 0;
$hData['dumpOK'] = 0;
$hData['dumpFilename'] = " - ";

# Skript zur Erzeugung der Dump-Datensätze

createLog("Erzeuge MySQL-Datenbank Backup");

# Betriebssystem-Erkennung für Mysql-Dump Pfad

if(strtolower(substr(trim(PHP_OS), 0, 3)) == 'win'){
	createLog("Das Betriebssystem Windows wurde erkannt.");
	$sDumpPfad = $sDumpPfadWindows;
}else if(strtolower(substr(trim(PHP_OS), 0, 5)) == 'linux'){
	createLog("Das Betriebssystem Linux wurde erkannt.");
	$sDumpPfad = $sDumpPfadLinux;
}else{
	createLog("Das Betriebssystem konnte nicht erkannt werden.");
	$hData['tempOK'] = 0;
}

# Erkennung ob DB-Server erreichbar ist

$oDBConnect = @mysqli_connect( $sDBServer, $sDBUser, $sDBPass);
if(isset($oDBConnect) && $oDBConnect){
	$hData['dbServer'] = 1;
}else{
	createLog("Datenbankverbindung konnte nicht aufgebaut werden ($sDBServer)");
	$hData['tempOK'] = 0;
}

# Erkennung ob Datenbank auf DB-Server vorhanden ist

if(isset($sDBDatenbank) && isset($oDBConnect) && $hData['tempOK'] == 1 && $hData['dbServer'] == 1 && @mysqli_select_db ( $oDBConnect, $sDBDatenbank)){
	$hData['dbExist'] = 1;
}else{
	createLog("Die Datenbank konnte nicht geöffnet werden ($sDBDatenbank)");
	$hData['tempOK'] = 0;
}

if($sFilepath == "")
{
	$hData['tempOK'] = 0;
	createLog("Kein Pfad für die Dateierzeugung angegeben");
} else {
	createLog("Ordner $sFilepath wird für die Erzeugung der Datei benutzt.");
}

# MysqlDump starten

if($hData['tempOK'] == 1 && $sDumpPfad != ""){
	createLog("exec($sDumpPfad . ' -h' .$sDBServer .' -p' .$sDBPass .'  -u' .$sDBUser .' --databases ' .$sDBDatenbank .' --single-transaction > ' .$sFile");
	$result=exec($sDumpPfad . ' -h' .$sDBServer .' -p' .$sDBPass .'  -u' .$sDBUser .' --databases ' .$sDBDatenbank .' --single-transaction > ' .$sFile ,$sOutput);
}else{
	$hData['tempOK'] = 0;
}

# Pruefen ob die Datei erzeugt wurde und nicht leer ist

if ($hData['tempOK'] == 1 && file_exists($sFile)) {
	if(filesize($sFile) > 0){
		createLog("OK: Die Datei $sFile erfolgreich erstellt (" .filesize($sFile) .")");
		$hData['dumpOK'] = 1;
		$hData['dumpFilename'] = $sFile;
	} else{
		createLog("FEHLER: Die Datei $sFile wurde erstellt enthält aber keine Daten");
	}
} else {
	createLog("FEHLER: Die Datei $sFile wurde nicht erstellt");
}

# Konsolenausgabe erzeugen und Loggin-Text für Webseite anpassen

$sKonsolenAusgabe = $sAusgabeText;
$sAusgabeText = preg_replace('/\n/', '<br>', $sAusgabeText);

# Erzeuge Ausgabe fuer Ansicht

$sHtmlAusgabe = "
<html>
<head>
<meta charset='utf-8'>
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

#divLogging {";
# Wenn alles ok dann LogFile-Box ausblenden
	if($hData['tempOK'] == 1){
		$sHtmlAusgabe .= "display: none;";
	} else {
		$sHtmlAusgabe .= "display: block;";
	}
$sHtmlAusgabe .= "
}


</style>

</head>
<body>
<div id=\"dataWrapper\">
<table>
<thead>
<tr>
<th></th>
<th>Status</th>
</tr>
</thead>
<tr>
<td>Pfad-MysqlDump</td><td>" .$sDumpPfad ."</td>
</tr>
<tr>
<td>MySQL-Server</td><td>";
if($hData['dbServer'] == 1){
	$sHtmlAusgabe .= "<span class='txtGruen'>OK</span>";
}
else{
	$sHtmlAusgabe .= "<span class='txtRot'>NOK</span>";
}
$sHtmlAusgabe .= "
</td>
</tr>
<tr>
<td>Datenbank</td><td>";
if($hData['dbExist'] == 1){
	$sHtmlAusgabe .= "<span class='txtGruen'>OK</span>";
}
else{
	$sHtmlAusgabe .= "<span class='txtRot'>NOK</span>";
}
$sHtmlAusgabe .= "
</td>
</tr>
<tr>
<td>Sicherung</td><td>";
if($hData['dumpOK'] == 1){
	$sHtmlAusgabe .= "<span class='txtGruen'>OK</span>";
}
else{
	$sHtmlAusgabe .= "<span class='txtRot'>NOK</span>";
}
$sHtmlAusgabe .= "
</td>
</tr>
<tr>
<td>Datei</td><td>" .$hData['dumpFilename'] ."</td>
</tr>
</table>
</div>
<div id='divLogging'><h2>Logging</h2>$sAusgabeText</div>
</body>
</html>";

# Erzeuge HTML- oder Konsolen-Ausgabe

if(isset($sAusgabeFormat) && $sAusgabeFormat == "html"){
	print $sHtmlAusgabe;
}
else if(isset($sAusgabeFormat) && $sAusgabeFormat == "konsole"){
	print $sKonsolenAusgabe;
}else{
	print $sKonsolenAusgabe;
}

# Funktionen 

function createLog($sText)
{
	global $sAusgabeText;
	$sAusgabeText .= date('d.m.Y H:i:s') ." - $sText \n"; 
}

function createHelp()
{
print "
Hilfe für Mysql-Dump Tool für Mysql-Datenbanken.
Datei kann über den Webbrowser ohne Angabe von Argumenten aufgerufen werden.
------------------------------------------------------\n
php5 mysql_dump.php [PARAMETER]
Bei Aufruf über die Konsole können folgende Parameter mit übergeben werden:
-help \t\t erzeugt diese Ausgabe
-noHTML \t erzeugt das Logfile ohne HTML-Strutkur
\t\t Sollte bei Konsolenaufruf immer mit übergeben werden\n
Beispiel:
php5 mysql_dump.php -help \n\n";
}
?>
