<?php
class alter_config
{
	function __construct($cmd)
	{
		global $CONFIGURATION,$CS,$AUTH;

		if(!$AUTH->isMemberOff("admin") && $AUTH->userName!="DEFAULT")
		{
			$CS->showError("insufficient privileges.");
			return;
		}
		if($cmd[2]=="from")
		{
			if($cmd[3]=="file")
			{
				$this->loadConfigFile($cmd);
			}
		}
		else if($cmd[2]=="reset")
		{
			$this->reset($cmd);
		}
		else if($cmd[2]=="set")
		{
			$this->alterConfig($cmd);
		}
		else if($cmd[2]=="remove")
		{
			$this->removeConfig($cmd);
		}
		else if($cmd[2]=="make")
		{
			$this->make($cmd);
		}
		else
		{
			$CS->showError('invalid configuration command.');
		}
		
	}
	function reset($cmd)
	{
		global $YAJAN_DATA,$CONFIGURATION,$CS,$AUTH,$_CONSOLE,$FREAMWORK_PATH,$INFO,$libVersion;
		exec("mkdir -p $YAJAN_DATA/tmp");
		exec("chmod 777 $YAJAN_DATA/tmp");
		exec("mkdir -p $YAJAN_DATA/log");
		exec("chmod 777 $YAJAN_DATA/log");
		exec("mkdir -p $YAJAN_DATA/db");
		exec("mkdir -p $YAJAN_DATA/scn");
		exec("chmod 777 $YAJAN_DATA/scn");
		exec("mkdir -p $YAJAN_DATA/backup");


		file_put_contents("$YAJAN_DATA/db/current","0");
		file_put_contents("$YAJAN_DATA/db/mode","all_db");
		file_put_contents("$YAJAN_DATA/db/scnlogmode",'nolog');
		
		
		$CONFIGURATION->clearConfig();
		$CONFIGURATION->write();
		
		$AUTH->addUser("default","default");
		$AUTH->addGroup("admin");
		$AUTH->addGroup("security");
		$AUTH->addGroup("dba");
		$AUTH->addGroup("developer");
		$AUTH->addGroup("operator");
		$AUTH->addUserGroup("default","admin");
		$AUTH->addUserGroup("default","security");
		$AUTH->addUserGroup("default","dba");
		$AUTH->addUserGroup("default","developer");
		$AUTH->addUserGroup("default","operator");
 

		$_CONSOLE->run("alter config set AUTH_ENABLE=false");
		$_CONSOLE->run("alter config set AUTO_POPULATE_RECORDSET=true");
		$_CONSOLE->run("alter config set CSS_SYNC_MODE=");
		$_CONSOLE->run("alter config set DATABASE_AUTO_OPEN=false");
		$_CONSOLE->run("alter config set DB_REDOLOG_FILE=var/log/redo.log");
		$_CONSOLE->run("alter config set DEFAULT_DB_OBJECT=db");
		$_CONSOLE->run("alter config set DEFAULT_DB_CONFIG=");
		$_CONSOLE->run("alter config set DEFAULT_IMPORT_PACKEG=system,ui,db");
		$_CONSOLE->run("alter config set EXCEPTION_LOG=false");
		$_CONSOLE->run("alter config set EXCEPTION_LOG_FILE=var/log/exception.log");
		$_CONSOLE->run("alter config set FORM_URL_MODE=file");
		$_CONSOLE->run("alter config set FREAMWORK_PATH=yajan");
		$_CONSOLE->run("alter config set JAVASCRIPT_SYNC_MODE=");
		$_CONSOLE->run("alter config set JS_ELEMENT_MODE=dom");
		$_CONSOLE->run("alter config set BOOTSTRAP_CSS=true");

		$_CONSOLE->run("alter config set BOOTSTRAP_UI_CSS=false");
		$_CONSOLE->run("alter config set OUTPUT_CACHE=false");
		$_CONSOLE->run("alter config set OUTPUT_CACHE_MODE=web");
		$_CONSOLE->run("alter config set OUTPUT_CACHE_LOCATION=var/cache/datacache");
		$_CONSOLE->run("alter config set OUTPUT_CACHE_SIGNATURE=true");
		$_CONSOLE->run("alter config set DB_QUERY_TRACE_DUMP=false");
		$_CONSOLE->run("alter config set DB_QUERY_TRACE_FILE=");
		$_CONSOLE->run("alter config set PHP_ERROR_REPORTING=E_ALL+%26+%7EE_NOTICE+%26+%7EE_DEPRECATED");
		$_CONSOLE->run("alter config set PHP_DISPLAY_ERRORS=true");
		$_CONSOLE->run("alter config set PHP_DISPLAY_STARTUP_ERRORS=false");
		$_CONSOLE->run("alter config set PHP_LOG_ERRORS=true");
		$_CONSOLE->run("alter config set PHP_TRACK_ERRORS=false");
		$_CONSOLE->run("alter config set PHP_HTML_ERRORS=true");
		$_CONSOLE->run("alter config set PHP_ERROR_LOGFILE=var/log/php_error.log");
		$_CONSOLE->run("alter config set JAVASCRIPT_SYNC_MODE=");
		$_CONSOLE->run("alter config set CSS_SYNC_MODE=");
		$_CONSOLE->run("alter config set BOOTSTRAP_CSS=true");
		$_CONSOLE->run("alter config set BOOTSTRAP_JS=true");
		$_CONSOLE->run("alter config set FORM_URL_MODE=module");
		$_CONSOLE->run("alter config set FORM_BUILDER_VAR=var/form");
		$_CONSOLE->run("alter config set SHOW_DB_ERROR=true");
		$_CONSOLE->run("alter config set CURRENCY_MODE=IN");


		$_CONSOLE->run("alter config set BOOT_LOG_FILE=");
		$_CONSOLE->run("alter config set ACCESS_LOG=false");
		$_CONSOLE->run("alter config set BOOT_LOG=false");
		
		$INFO->addInfo("UPDATE_REVIEW_VERSION","1");
		$INFO->write();
		
		

		$php = "<?php
		$YAJAN_DATA=\"$YAJAN_DATA\";
		?>";
		
		//file_put_contents("$FREAMWORK_PATH/lib/$libVersion/parm.php",$php);
		$_CONSOLE->run("alter config set YAJAN_DATA=$YAJAN_DATA");
		$CS->showError('Config reset comlite');
	}
	function make($cmd)
	{
		global $CONFIGURATION,$CS,$YAJAN_DATA;
		if($cmd[3]=="duplicate")
		{
			if($cmd[4]=="as")
			{
				$filename = $cmd[5];
				copy($CONFIGURATION->getConfigFile(),$YAJAN_DATA."/config/$filename.conf");
				$CS->showOk("Duplicate copy of configuration created.");
			}
		}
		else if($cmd[3]=="php")
		{
			$phpFile = $cmd[4];
			if($cmd[5]=="of")
			{
				$name = $cmd[6];
				$CONFIGURATION->exportPhp($phpFile,$name);
				$CS->showOk("PHP Export complete.");
			}
		}
	}
	function removeConfig($cmd)
	{
		global $CONFIGURATION,$CS;
		$key = $cmd[3];
		$CONFIGURATION->removeKey($cmd[3]);
		$CONFIGURATION->write();
		$CS->showOK('Configuration altered.');
	}
	function loadConfigFile($cmd)
	{
		global $CONFIGURATION,$CS;
		$filename = $cmd[4];
		$safe=false;
		if(isset($cmd[5]))
		{
			if($cmd[5]=="safely")
			{
				$safe=true;
			}
		}
		if(file_exists($filename))
		{
			$fn = basename($filename);
			$data = file_get_contents($filename);
			$CONFIGURATION->set($fn,$data,"file",$safe);
			$CONFIGURATION->write();
			$CS->showOK('Configuration altered.');
		}

	}
function alterConfig($cmd)
	{
		global $CONFIGURATION,$CS;
		$str="";
		for($i=3;$i<count($cmd);$i++)
		{
			$str .= $cmd[$i]." ";
		}
		$str=trim($str);
		$c = explode("=",$str);
		
		$c[1]=urldecode($c[1]);
		$safe=false;
		if(isset($cmd[4]))
		{
			if($cmd[4]=="safely")
			{
				$safe=true;
			}
		}
		$CONFIGURATION->set($c[0],$c[1],"var",$safe);
		$CONFIGURATION->write();
		$CS->showOK('Configuration altered.');
	}
}
?>
