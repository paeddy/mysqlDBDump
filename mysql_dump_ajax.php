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
$sSQLDBDatum = date('YmdHis');
$oDBConnect = new mysqli( $sDBServer, $sDBUser, $sDBPass);
if(!isset($oDBConnect)){
	die('Keine Datenbankverbindung');
}

if( isset($_GET['getAjaxDateiInformationen']) && intval($_GET['getAjaxDateiInformationen']) == 1 && isset($_GET['filename']) && $_GET['filename'] != '')
{
	$aJsonReturn = Array();
	$aJsonReturn['tabellen'] = Array();
	$aJsonReturn['info'] = Array();
	$sFile = $_GET['filename'];
	$aDateiData = leseDatei($sFile);
	$aJsonReturn['info']['dateiname'] = $sFile;
	for($i = 0; $i < count($aDateiData); $i++)
	{
		if(preg_match('/^INSERT\sINTO\s`(.*)`\sVALUES/', $aDateiData[$i],$treffer))
		{
			$aTemp = Array();
			$aTemp['name'] = $treffer[1];
			$aTemp['anzahl'] = getAnzahlInsert($aDateiData[$i]);
			
			array_push($aJsonReturn['tabellen'],$aTemp);
		}
	}	
	print json_encode($aJsonReturn);
}
elseif( isset($_FILES['importFile']) )
{

    $allowed = array('sql');

    if($_FILES['importFile']['error'] == 0){

        $extension = pathinfo($_FILES['importFile']['name'], PATHINFO_EXTENSION);
		$pfad = "upload/" .$_FILES['importFile']['name'];
        if(!in_array(strtolower($extension), $allowed)){
            echo '{"status":"error"}';
            exit;
        }

        if(move_uploaded_file($_FILES['importFile']['tmp_name'], $pfad)){
            echo '{"status":"success", "pfad" : "' .$pfad .'"}';
            exit;
        }
        echo '{"status":"error"}';
    }
    exit();

}
elseif( isset($_GET['getAjaxStartImport']) && intval($_GET['getAjaxStartImport']) == 1 && isset($_GET['filename']) && $_GET['filename'] != '')
{
	$bStatus = 0;
	$bStatus = renameDatabase();
	$JsonReturn = Array();
	$JsonReturn['status'] = "FAIL";
	$JsonReturn['pfad'] = $_GET['filename'];
	$JsonReturn['db_neu'] = $sDBDatenbank;
	$JsonReturn['db_alt'] = $sDBDatenbank."_".$sSQLDBDatum;
	if($bStatus == 1){
		$bStatus = importDatabaseDump($_GET['filename']);
	}
	if($bStatus == 1)
	{
		$JsonReturn['status'] = "OK";
		$JsonReturn['pfad'] = $_GET['filename'];
	}
	print json_encode($JsonReturn);
}

function importDatabaseDump($sFile)
{
	global $sMysqlPfadWindows;
	global $sMysqlPfadLinux;
	global $sDBServer;
	global $sDBPass;
	global $sDBUser;
	global $sDBDatenbank;
	global $oDBConnect;
		
	$bOK = 1;
	$sMysqlPfad = "";
	if(strtolower(substr(trim(PHP_OS), 0, 3)) == 'win'){
		$sMysqlPfad = $sMysqlPfadWindows;
	}else if(strtolower(substr(trim(PHP_OS), 0, 5)) == 'linux'){
		$sMysqlPfad = $sMysqlPfadLinux;
	}
	else{
		$bOK = 0;
	}

	if($bOK == 1 && $sMysqlPfad != "")
	{
		$result=exec($sMysqlPfad . ' -h' .$sDBServer .' -p' .$sDBPass .'  -u' .$sDBUser .' < ' .$sFile ,$sOutput);
		if(!$oDBConnect->select_db($sDBDatenbank))
		{
			$bOK = 0;
		}
	}
	else
	{
		$bOK = 0;
	}
	
	return $bOK;
	
}

function renameDatabase()
{
	global $sSQLDBDatum;
	global $oDBConnect;
	global $sDBDatenbank;
	$DBOk = 1;
	
	$sqlCreateDB = "CREATE DATABASE " .$sDBDatenbank ."_" .$sSQLDBDatum ." CHARACTER SET utf8 COLLATE utf8_general_ci;";
	$sqlAbfrageDB = "SELECT CONCAT('RENAME TABLE ','" .$sDBDatenbank ."','.',table_name,' TO ','" .$sDBDatenbank ."_" .$sSQLDBDatum .".',table_name,';') AS queryRename FROM information_schema.TABLES WHERE table_schema LIKE '" .$sDBDatenbank ."';";
	$sqlDropDB = "DROP DATABASE " .$sDBDatenbank .";"; 
	
	$oDBConnect->autocommit(FALSE);
	$ResultNewDB = $oDBConnect->query($sqlCreateDB);
	if($oDBConnect->select_db($sDBDatenbank))
	{
		if($ResultNewDB)
		{
			$ResultAbfrageTable = $oDBConnect->query($sqlAbfrageDB);
			while($row = $ResultAbfrageTable->fetch_assoc())
			{
				if($DBOk == 1)
				{
					$ResultRenameTable = $oDBConnect->query($row['queryRename']);
					if(!$ResultRenameTable)
					{
						$DBOk = 0;
					}
				}
			}
			if($DBOk == 1)
			{
				$ResultDropDB = $oDBConnect->query($sqlDropDB);
				if(!$ResultDropDB)
				{
					$DBOk = 0;
				}
			}
		}
		
		if ($DBOk == 1) {
			$oDBConnect->commit();	
		}
		else {
		  $oDBConnect->rollback();
		}
		$oDBConnect->autocommit(true);
	}

	return $DBOk;
}

function getAnzahlInsert($sText)
{
	preg_match('/^INSERT\sINTO\s`.*`\sVALUES\s(.*)$/', $sText,$treffer);
	$aDataSet = preg_split('/\),\(/',$treffer[1]);
	return count($aDataSet);
}

function leseDatei($sFile)
{
	$aFileData = Array();
	$handle = fopen ($sFile, "r");
	 
	while ( $inhalt = fgets ($handle))
	{
	  array_push($aFileData,$inhalt);
	}
	 
	fclose($handle);
	return $aFileData;
}

?>
