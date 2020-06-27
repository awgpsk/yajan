<?php
class Open
{
	function __construct($cmd)
	{
		global $AUTH,$CS;
		
		if($cmd[1]=='sql')
		{
			if(!$AUTH->isMemberOff("developer") && !$AUTH->isMemberOff("dba"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}			
			
			$this->openSql($cmd);
		}
		else if($cmd[1]=="form+")
		{
			if(!$AUTH->isMemberOff("developer"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}			
			
			$this->openFormBuilder($cmd);
		}
		else if($cmd[1]=="ycm")
		{
			if(!$AUTH->isMemberOff("developer") && !$AUTH->isMemberOff("dba"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}
			$this->openYCM($cmd);
		}
		/*
		else if($cmd[1]=="yrm")
		{
			if(!$AUTH->isMemberOff("admin"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}
			$this->openRepository($cmd);
		}
		*/
		else if($cmd[1]=="emc")
		{
			if(!$AUTH->isMemberOff("admin") && !$AUTH->isMemberOff("developer"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}
			$this->openErrMgmt($cmd);
		}
		else if($cmd[1]=="mvc")
		{
			if(!$AUTH->isMemberOff("admin") && !$AUTH->isMemberOff("developer"))
			{
				$CS->showError("insufficient privileges.");
				return;
			}
			$this->openMvc($cmd);
		}
	}
	
	function openSql($cmd)
	{
		global $CS,$DB_REG,$DEFAULT_DB_CONFIG,$__run;
		$CS->setFGColor("yellow");
			
		if(count($cmd)==4)
		{
			$db =$cmd[3];
		}
		else
		{
			$db = $DB_REG->getRegistrationId($DEFAULT_DB_CONFIG);
		}
		if($DB_REG->isRegisterdId($db)==-1)
		{
			$CS->showError("Invalid Database ID");
			return;
		}
		$dbId = $db;
		$sp = $DB_REG->getProperty($db);
		// print_r($sp);
		$dbName = $sp['name'];
		$db = new Connection($sp);
		
		$CS->cout("Connected to DB ID $dbId($dbName).\n");
		
		if(!$db->autoCommit(false))
		{
			$CS->cout("NOTE!! Auto commit is on.\n");	
		}
		
		$sql = "";
		$colSize=30;
		$oldSql = "";
		
		while($sql!="exit")
		{
			if($sql!="" and $sql != "exit")
			{
				if($sql=="/")
				{
					$sql = $oldSql;
				}
				if(isset($__run))
				{
					$sql=$__run;
				}
				$s = explode(" ",$sql);
				if(strtoupper($s[0])=="SET")
				{
					if(strtoupper($s[1])=="LIN")
					{
						$colSize = $s[2];
					}
				}
				else if(strtoupper($s[0])=="DESC")
				{
					$obj = $s[1];
					if($sp['driver']=="oracle")
					{
						$q="select * from $obj where rownum < 1";
					}
					else
					{
						$q="select * from $obj limit 1,1";
					}
					$r = $db->execute($q);
					$out = new Recordset();
					$out->addColumns("name","type","size");
					
						for($i=0;$i<$r->countColumns;$i++)
						{
							$out->add($r->columns[$i]->getName(),$r->columns[$i]->getType(),$r->columns[$i]->getSize());
						}
						$out->showCLITable();
					$oldSql = $sql;
				}
				else
				{
					$r = $db->execute($sql);
					
					
					if(gettype($r)=="object")
					{
						$r->showCLITable();
						echo "\n".$r->count." rows return\n\n";
					}
					else
					{
						echo "Opration complite\n";
					}
					$oldSql = $sql;
				}
			}
			if(isset($__run))
			{
				break;
			}
			echo "SQL: [".$sp['name']."] > ";
			$sql = $CS->read();
			$sql = rtrim($sql,";");
		}
		
	}
	function openFormBuilder($cmd)
	{
		global $CS;
		require_once "form/formBuilder.php";
		$fc = new FormBuilder($cmd);
		$fc->run();
		$CS->setFGColor("yellow");
		$CS->cout("\n\nThanks.\n\n");	
		
	}
	function openYCM($cmd)
	{
		global $CS;
		require_once "ycm/class.ycm.php";
		$fc = new YajanCacheManager($cmd);
		$fc->run();
		$CS->setFGColor("yellow");
		$CS->cout("\n\nThanks.\n\n");	
		
	}
	/*
	function openRepository($cmd)
	{
		global $CS,$DB_REG,$DEFAULT_DB_CONFIG,$__run;
		

		
		$fc = new YajanRepository($cmd);
		$fc->run();
		$CS->setFGColor("yellow");
		$CS->cout("\n\nThanks.\n\n");	
	}
	*/
	function openErrMgmt()
	{
		global $CS,$DB_REG,$DEFAULT_DB_CONFIG,$__run;
		require_once("emService/class.emService.php");
		$fc = new EMServiceConsole($cmd);
		$fc->run();
		$CS->setFGColor("yellow");
		$CS->cout("\n\nThanks.\n\n");	
	}
	
	function openMvc()
	{
		global $CS,$DB_REG,$DEFAULT_DB_CONFIG,$__run;
		require_once("mvcapp/class.mvcapp.php");
		$fc = new MVCAppConsole($cmd);
		$fc->run();
		$CS->setFGColor("yellow");
		$CS->cout("\n\nThanks.\n\n");	
	}
}
?>