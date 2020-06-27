<?php
import("security");

require_once("$LIB_PATH/php/db/databaseadaptor.php");
require_once("$LIB_PATH/php/db/mysql.php");
require_once("$LIB_PATH/php/db/oracle.php");

class Connection
{
	//var $parameter;
	var $database;
	var $query;
	var $resource;
	var $querytype;
	var $message;
	var $startTime;
	var $endTime;
	var $timecount;
	var $driver;
	var $adaptor;
	var $id;
	var $scnLog;
	var $queryCount;
	var $dbRegistration;
	function __construct($parameter)
	{
		
		global $DB_REG,$EXEC_MODE;
		$this->dbRegistration=false;
		$dbRegMode=false;
		if(gettype($parameter)=="array")
		{

			if(isset($parameter["driver"]) 
				&& isset($parameter["username"])
				&& isset($parameter["password"])
				&& isset($parameter["database"])
				&& isset($parameter["server"])
				&& isset($parameter["port"])
				&& isset($parameter["dbmode"]))
			{
				$parameter["name"]="direct";
				$dbRegMode=true;
			}
			else
			{
				$dbName = $parameter['name'];	
			}
		}
		else if(gettype($parameter)=="string")
		{
			if(strpos($parameter, "@")!==false)
			{
				$str = explode("@",$parameter);
				$str1 = explode("/", $str[0]);
				$p = array();
				$p["name"]="connectionString";
				$p["username"]=$str1[0];
				$p["password"]=$str1[1];

				$str3 = explode(":", $str[1]);
				
				$p["driver"]=$str3[0];
				$p["server"]=$str3[1];
				$p["port"]=$str3[2];
				$p["database"]=$str3[3];
				$p["dbmode"]=$str3[4];
				

				$dbRegMode=true;
				$parameter = $p;

			}
			else
			{
				$dbName = $parameter;	
			}
			
		}

		
		if($dbRegMode==false)
		{

			if($DB_REG->isRegisterd($dbName)==-1)
			{
				echo "Database $dbName id not registered\n";

				if($EXEC_MODE!="CLI")
				{
					exit(0);
				}
			}	
			$this->queryCount=0;
			$dbId = $DB_REG->getRegistrationId($dbName);
			$parm = $DB_REG->getProperty($dbId);
			$this->dbRegistration = true;


		}
		else
		{
			$parm = $parameter;
			$this->dbRegistration=false;
		}

		//print_r($parameter);
		//$this->id = $DB_REG->addConnection($dbName);
		//print_r($parameter);
		$this->message = new Messages("DatabaseConnection");

		$this->adaptor=new DatabaseAdaptor();
		
		
		$this->addDriver($parameter);
		$this->database = $this->adaptor->getDbHandel();
		$this->startTime = $this->getmicrotime();
		$this->scnLog = false;
		
	}
	function getDriver()
	{
		return $this->adaptor->getDriver();
	}
	function isAvilable()
	{
		$this->adaptor->isAvilable();
	}
	function scnLog($v)
	{
		$this->scnLog = $v;
		$this->adaptor->scnLog($v);
	}
	function addDriver($parameter)
	{
		global $DB_REG,$EXEC_MODE;
		$dbRegMode=false;
		if(gettype($parameter)=="array")
		{
			if(isset($parameter["driver"]) 
				&& isset($parameter["username"])
				&& isset($parameter["password"])
				&& isset($parameter["database"])
				&& isset($parameter["server"])
				&& isset($parameter["port"])
				&& isset($parameter["dbmode"]))
			{

				$parameter["name"]="direct";

				$dbRegMode=true;

			}
			else
			{
				$dbName = $parameter['name'];	
			}
		}
		else if(gettype($parameter)=="string")
		{
			if(strpos($parameter, "@")!==false)
			{
				$str = explode("@",$parameter);
				$str1 = explode("/", $str[0]);
				$p = array();
				$p["name"]="connectionString";
				$p["username"]=$str1[0];
				$p["password"]=$str1[1];

				//$str2 = explode("/", $str[1]);
				

				$str3 = explode(":", $str[1]);
				
				$p["driver"]=$str3[0];
				$p["server"]=$str3[1];
				$p["port"]=$str3[2];
				$p["database"]=$str3[3];
				$p["dbmode"]=$str3[4];
				
				$dbRegMode=true;
				$parameter = $p;

			}
			else
			{
				$dbName = $parameter;	
			}
		}
		
		if($dbRegMode==false)
		{


			if($DB_REG->isRegisterd($dbName)==-1)
			{
				echo "Database $dbName id not registered\n";
				if($EXEC_MODE!="CLI")
				{
					exit(0);
				}
			}
			$dbId = $DB_REG->getRegistrationId($dbName);
			$parameter = $DB_REG->getProperty($dbId);
		}
		else
		{

		}
		//print_r($parameter);
		if(!isset($parameter['driver']))
		{
			echo "Database configuration error. driver value not specified";
			exit(0);
		}
		if(!isset($parameter['username']))
		{
			echo "Database configuration error. username value not specified";
			exit(0);
		}
		if(!isset($parameter['password']))
		{
			echo "Database configuration error. password value not specified";
			exit(0);
		}
		if(!isset($parameter['server']))
		{
			echo "Database configuration error. server value not specified";
			exit(0);
		}
		if(!isset($parameter['database']))
		{
			echo "Database configuration error. database value not specified";
			exit(0);
		}
		if(!isset($parameter['port']))
		{
			echo "Database configuration error. port value not specified";
			exit(0);
		}		
		if(!isset($parameter['dbmode']))
		{
			echo "Database configuration error. dbmode value not specified";
			exit(0);
		}		
		//echo "AS".variable_name($parameter);
		//$DB_REG->addDriver($parameter,$this->id);
		
		$this->adaptor->addDriver($parameter);
		
	}
	function setRedoLog($file)
	{
		
	}
	function getmicrotime() 
	{ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	}
	function bindVar($var,&$val,$size=2000,$type=SQLT_CHR)
	{
		return $this->adaptor->bindVar($var,$val,$type,$size);
	}
	function bindWith($obj)
	{
		return $this->adaptor->bindWith($obj);
	}
	function getBlob($name)
	{
		return $this->getDescriptor($name,OCI_D_LOB,OCI_B_BLOB);
	}
	function getClob($name)
	{
		return $this->getDescriptor($name,OCI_D_LOB,OCI_B_CLOB);
	}
	function getDescriptor($name,$type,$dbType)
	{
		return $this->adaptor->getDescriptor($name,$type,$dbType);
	}
	function getResult()
	{
		return $this->adaptor->getResult();
	}
	function autoExecute($val,$all='')
	{
		$this->adaptor->autoExecute($val,$all);
	}
	function parse($q)
	{
		$this->queryCount++;
		$this->query = trim($q);
		$temp = explode(" ",$this->query);
		$command = strtoupper($temp[0]);
		if($command=="SELECT" || $command=="DESC" || $command=="SHOW")
		{
			$this->queryType = "DQL";
		}
		else if($command=="INSERT" or $command=="UPDATE" or $command=="DELETE")
		{
			$this->queryType = "DML";
		}
		else if($command=="CREATE" or $command=="ALTER" or $command=="DROP"  or $command=="SET")
		{
			$this->queryType = "DDL";
		}
		else if($command=="COMMIT" or $command=="ROLLBACK")
		{
			$this->queryType = "DCL";
		}
		else if($command=="DECLARE" or $command=="BEGIN")
		{	
			$this->queryType = "PLSQL";
		}
		else
		{
			$this->queryType = "OTHER";
		}
		
		$this->resource = $this->adaptor->parse($q,$this->queryType);
		return $this->adaptor->getQueryStatus();
	}
	function resultFree()
	{
		return $this->adaptor->resultFree();
	}
	function execute($q="",$auto_populate=true)
	{
		
		if($q!="")
		{	
			
			global $AUTO_POPULATE_RECORDSET;
			$resp = $this->parse($q);
			
			if($resp)
			{
				
				if($this->queryType=="DQL")
				{
					$rec = $this->getRecordset();
					//echo gettype($rec);
					
					//echo $q."...".gettype($rec)."<br><br>";
					if($rec->database!=null)
					{
						
						if($AUTO_POPULATE_RECORDSET==true && $auto_populate == true)
						{
							$rec->populate();
							return $rec;
						}
						else
						{
							$rec->populateColumns();
							return $rec;
						}
					}
					else
					{
						return $rec;
					}
				}
				else
				{
					
					return true;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			if($this->resource)
			{
				if($this->queryType=="DQL")
				{
					$rec = $this->getRecordset();
					if($rec->database!=null)
					{
						if($AUTO_POPULATE_RECORDSET==true && $auto_populate == true)
						{
							$rec->populate();
							return $rec;
						}
						else
						{
							$rec->populateColumns();
							return $rec;
						}
					}
					else
					{
						return $rec;
					}
				}
				else
				{
					return $this->adaptor->execute();;
				}
			}
			else
			{
				return false;
			}
		}
	}
	function showError($v,$all='')
	{
		$this->adaptor->showError($val,$all);
	}
	function getMessage()
	{
		return $this->adaptor->getMessage();
	}
	function autoCommit($val,$all='')
	{
		
		return $this->adaptor->autoCommit($val,$all);
	}
	function commit($all='')
	{
		return $this->adaptor->commit($all);
	}
	function rollback($all='')
	{
		return $this->adaptor->rollback($all);
	}
	function getRecordset($resource='')
	{
		if($resource=='')
		{
			$resource=$this->adaptor->getDbResource();
		}

		if(gettype($resource)=="resource" || gettype($resource)=="object")
		{
			return new Recordset($this->adaptor->getDbHandel(),$resource);
		}
		else
		{
			return $resource;
		}
	
	}
	function getLink()
	{
		return $this->adaptor->getDbLink();
	}
	function sysdate($format="")
	{

		if($this->getDriver()=="oracle")
		{
			if($format=="")
			{
				$format="dd-mon-yy";
			}
			$q="select to_char(sysdate,'$format') as mydate from dual";
		}
		else
		{
			if($format=="")
			{
				$format="%Y-%m-%d";
				
			}
			$q="select date_format(curdate(),'$format') as mydate";
		}
		$r = $this->execute($q);
		return $r->data[0]['MYDATE'];
	}
	function getTimetout()
	{
		return $this->getmicrotime()-$this->startTime;
	}
	function close($commitRegistration=true)
	{
		/*
		global $DB_REG;
		if($commitRegistration)
		{
			$DB_REG->save();
		}
		*/
		$this->adaptor->close();
		$this->endTime = $this->getmicrotime();
		$this->timecount = $this->endTime-$this->startTime;
	}

}
?>