<?php
#######################
# 
# DB-Dump Erzeugung
# Version: 1.0 (01.01.2014)
# Paetrick Vogt < paetrickvogt@gmail.com >
# 
#######################

// max MYSQL-Timeout setzen
ini_set('mysqli.connect_timeout', 10); 

########################################################################
# ################# Konfigurier mich !! ################################
#########################################################################
#
# Include Datei mit den Variablen für den Server
#
include 'include/dbConnect.inc.php'; # Anpassen an die Originale PHP-Datei im Projekt, sonst werden die Standard Zugangsdaten benutzt
#
# Konfiguration fuer Windows-Betriebssysteme
#
$sDumpPfadWindows = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
$sMysqlPfadWindows = "C:\\xampp\\mysql\\bin\\mysql.exe";
$sAusgabeOrdnerWin = 'C:\\temp\\';
#
# Konfiguration fuer Linux-Betriebssyteme
#
$sDumpPfadLinux = "mysqldump";
$sMysqlPfadLinux = "mysql";
$sAusgabeOrdnerLinux = '/tmp/';
#
# Konfiguration Default SQL
#
$sSQLDefaultUser = "root";
$sSQLDefaultPass = "1234567890";
$sSQLDefaultServer = "localhost";
$sSQLDefaultDatenbank = "DB";
$sSQLDefaultPort = "3306";
#
#########################################################################

$sFilepath = "";
if(strtolower(substr(trim(PHP_OS), 0, 3)) == 'win'){
	$sFilepath = $sAusgabeOrdnerWin;
}else if(strtolower(substr(trim(PHP_OS), 0, 5)) == 'linux'){
	$sFilepath = $sAusgabeOrdnerLinux;
}

# Variablen fue DB-Connect
# Standard-Variablen aus Include Datei

$sDBUser = isset($user) && $user != "" ? $user : $sSQLDefaultUser;
$sDBPass = isset($pass) && $pass != "" ? $pass : $sSQLDefaultPass;
$sDBServer = isset($server) && $server != "" ? $server : $sSQLDefaultServer;
$sDBDatenbank = isset($datenbank) && $datenbank != "" ? $datenbank :$sSQLDefaultDatenbank;
$iDBPort = isset($port) && $port != "" ? $port : $sSQLDefaultPort;


?>
