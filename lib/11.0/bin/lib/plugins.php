<?php
class Plugins
{
	var $list;
	var $dx;
	var $filename;
	function __construct()
	{
		global $FREAMWORK_PATH,$YAJAN_DATA,$libVersion;
		$this->filename = "$YAJAN_DATA/data/plugins.conf";
		// $this->filename = "$FREAMWORK_PATH/lib/$libVersion/data/plugins.conf";
		$this->dx = new DataBox1_2("plugins");
		$this->dx->fromFile($this->filename);
		$this->list = $this->dx->getObject("list");
		if($this->list=="")
		{
			$this->list=new Recordset();
			$this->list->addColumns("name","description","version","micro_version","installation_date");
		}
	}
	function getData()
	{
		return $this->list;
	}
	function setData($data)
	{
		$this->list=$data;
	}
	function write()
	{
		$this->dx->add("list",$this->list);
		$this->dx->toFile($this->filename);
	}
	function getInstalledVersion($name,$version)
	{
		for($i=0;$i<$this->list->count;$i++)
		{
			if($this->list->data[$i]["NAME"]==$name && $this->list->data[$i]["VERSION"]==$version)
			{
				return $this->list->data[$i];
			}
		}
		return false;
	}
	function updateInfo($name,$description,$version,$micro_version,$installation_date)
	{
		global $CS;
		$update=false;
		for($i=0;$i<$this->list->count;$i++)
		{
			if($this->list->data[$i]["NAME"]==$name)
			{
				$this->list->data[$i]["VERSION"]=$version;
				$this->list->data[$i]["MICRO_VERSION"]=$micro_version;
				$this->list->data[$i]["DESCRIPTION"]=$description;
				$this->list->data[$i]["INSTALLATION_DATE"]=$installation_date;
				$update=true;
				$CS->showInfo("Plugin $name updated\n");
			}
		}
		if(!$update)
		{
			$CS->showInfo("Plugin $name added\n");
			$this->list->add($name,$description,$version,$micro_version,$installation_date);
		}
		$this->write();
	}
	function install($name,$version,$cmd)
	{
		global $YAJAN_SERVER,$CS,$_CONSOLE;
		if($version=="")
		{
			$CS->showError("Plugin version is required parameter.");
			return;
		}
		$withoutDb=false;
		if(isset($cmd[7]) && $cmd[6]=="without" && $cmd[7]=="db")
		{
			echo "AS";
			$withoutDb=true;
		}
		$this->updatePluginPhp($name,$version,true,$withoutDb);
		
	}
	
	function updatePluginPhp($name,$version,$installation=false,$withoutDb=false)
	{
		global $YAJAN_SERVER,$DB_REG,$CS,$_CONSOLE,$CONFIGRATION;
		
		$localInfo = $this->getInstalledVersion($name,$version);
		if($localInfo)
		{
			$installVersion=$localInfo["MICRO_VERSION"];
		}
		else
		{
			$installVersion=1;
		}	
		
		
		$pluginInfo = $this->getAvilableVersion($name);
		$avilableVersion = $pluginInfo["micro_version"];
		$description = $pluginInfo["description"];
		
		
		
		if($avilableVersion<=$installVersion)
		{
			$CS->showInfo(strtoupper($name)." ($description) $version.$installVersion is allrady updated.");
			return;
		}
		$CS->showInfo(strtoupper($name)." ($description) $version.$installVersion will update to $version.$avilableVersion");
		$CS->showInfo("\tRunning Scripts...");
		$scriptError=false;
		for($i=$installVersion;$i<=$avilableVersion;$i++)
		{
			$data = file_get_contents("$YAJAN_SERVER/release.php?version=$version&plugins=$name&get=pluginphp&micro=$i");
			
			if(strlen($data)>0)
			{
				$CS->showInfo("\t\tRun $i.php");
				
				file_put_contents("var/tmp/$name.$version.$i.php",$data);
				include "var/tmp/$name.$version.$i.php";
			}
		}
		if($scriptError==false)
		{
			$this->updateInfo($name,$description,$version,$avilableVersion,date("Y-m-d H:i:s"));
		}
	}
	function updatePluginSql($name,$version,$dbid,$installation=false)
	{
		global $YAJAN_SERVER,$DB_REG,$CS;
		$CS->showInfo("\tUpdating Database...");
		if($installation)
		{
			$installVersion=1;
		}	
		$avilableVersion = $this->getAvilableVersion($name);
		$avilableVersion = $avilableVersion["micro_version"];
		for($i=$installVersion;$i<=$avilableVersion;$i++)
		{
			
			$data = file_get_contents("$YAJAN_SERVER/release.php?version=$version&plugins=$name&get=pluginsql&micro=$i");
			if(strlen($data)>0)
			{
				
				if(file_exists("var/tmp/$name.$version.$i.sql"))
				{
					unlink("var/tmp/$name.$version.$i.sql");
				}
				file_put_contents("var/tmp/$name.$version.$i.sql",$data);
				$CS->showInfo("\t\tImport $name.$version.$i.sql");
				if(!$DB_REG->importSql($dbid,"var/tmp/$name.$version.$i.sql"))
				{
					echo $DB_REG->message;
				}
			}
		}
	}
	function download($file_source,$file_target)
	{
		$rh = fopen($file_source, 'rb');
		if(file_exists($file_target))
		{
			unlink($file_target);
		}
		$wh = fopen($file_target, 'w+b');
		if (!$rh || !$wh) 
		{
			$CS->showError("Downloading failed.");
			return false;
		}
		while (!feof($rh)) 
		{
			if (fwrite($wh, fread($rh, 4096)) === FALSE) 
			{
				$CS->showError("Downloading failed. writing error.");
				return false;
			}
			flush();
		}
		return true;
	}
	function showInstalled()
	{
		global $CS;
		$CS->showInfo("Installed plugins list");
		$this->list->showCLITable();
	}
	function getAvilableVersion($name)
	{
		global $CS,$YAJAN_SERVER,$libVersion;
		$data = file_get_contents("$YAJAN_SERVER/release.php?version=$libVersion&get=pluginlist");
		$data = explode("\n",$data);
		for($i=0;$i<count($data);$i++)
		{
			$d = explode("|",$data[$i]);
			if($d[0]==$name)
			{
				$t = array();
				$t["name"]= $d[0];
				$t["description"]= $d[3];
				$t["version"]= $d[1];
				$t["micro_version"]= $d[2];
				$t["release_date"]= $d[4];
				return $t;
			}
		}
		return false;
	}
	function getList()
	{
		global $CS,$YAJAN_SERVER,$libVersion;
		$CS->showInfo("Avilable plugins list");	
		$data = file_get_contents("$YAJAN_SERVER/release.php?version=$libVersion&get=pluginlist");
		$data = explode("\n",$data);
		$list=new Recordset();
		$list->addColumns("name","description","version","micro_version","installed_version","release_date");
		
		for($i=0;$i<count($data);$i++)
		{
			$d = explode("|",$data[$i]);
			if(count($d)>1)
			{
				$list->add($d[0],$d[3],$d[1],$d[2],"",$d[4]);
			}
		}
		$list->showCLITable();
		
	}
}
?>