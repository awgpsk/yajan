<?php
require_once("$LIB_PATH/php/db/registration.php");

class DatabaseAdaptor
{
	private $dbHandels;
	private $dbNumber;

	private $queryStatus;
	private $queryType;
	private $syncMode;
	private $scnMgmt;
	
	function __construct()
	{
		global $YAJAN_DATA;
		$this->dbHandels = array();
		$this->message = new Messages("DatabaseAdaptor");
		$this->link=null;
		$this->commitMode=true;
		$this->start=0;
		$this->timecount=0;
		$this->queryCount=0;

		$this->queryStatus=false;
		$this->queryType = null;

		
		$this->scnMgmt = new ScnMgmt();
		// echo "$YAJAN_DATA/db/current";
		$this->dbNumber=file_get_contents("$YAJAN_DATA/db/current");
		$this->syncMode=file_get_contents("$YAJAN_DATA/db/mode");
		
		//echo $this->syncMode;
	}
	function scnLog($v)
	{
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			$this->dbHandels[$i]->scnLog($v);
		}
	}
	function getmicrotime() 
	{ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	}
	function getDriver()
	{
		return $this->dbHandels[$this->dbNumber]->parm['driver'];
	}
	public function addDriver($parm)
	{
		global $DB_REG;

		$n = count($this->dbHandels);
		if($parm['driver']=="oracle")
		{
			putenv ("NLS_LANG=AMERICAN_AMERICA.AL32UTF8");
			$this->dbHandels[$n] = new OracleDatabase($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}
		else if($parm['driver']=="mysql")
		{
			$this->dbHandels[$n] = new MysqlDatabase($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}
		else if($parm['driver']=="mysqli")
		{
			$this->dbHandels[$n] = new MysqliDatabase($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}
		else if($parm['driver']=="mariadb")
		{
			$this->dbHandels[$n] = new MariaDB($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}
		else if($parm['driver']=="clouddb")
		{
			$this->dbHandels[$n] = new CloudDB($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}
		else if($parm['driver']=="sqlite")
		{

			$this->dbHandels[$n] = new SqliteDatabaseDriver($parm,$n);
			$this->dbHandels[$n]->scnMgmt = $this->scnMgmt;
		}		
		return $this->getDbHandel();
	}
	public function isAvilable()
	{
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			if(!$this->dbHandels[$i]->isAvilable())
			{
				return false;
			}
		}
		return true;
	}
	public function countDbHandel()
	{
		return count($this->dbHandels);
	}
	public function getDbHandel($i=null)
	{
		if($i==null)
		{
			$i=$this->dbNumber;
		}
		if(isset($this->dbHandels[$i]))
		{
			return $this->dbHandels[$i];
		}
	}
	public function getDbResource()
	{
		if(isset($this->dbHandels[$this->dbNumber]))
		{
			return $this->dbHandels[$this->dbNumber]->resource;
		}
	}
	public function getDbLink()
	{
		return $this->dbHandels[$this->dbNumber]->link;
	}	
	public function getQueryStatus()
	{
		
		return $this->queryStatus;
	}
	public function showError($v,$all='')
	{
		if($all=='')
		{
			$this->dbHandels[$this->dbNumber]->showError($val);
		}
		else
		{
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				$this->dbHandels[$i]->showError($val);
			}
		}
	}
	public function autoExecute($val,$all='')
	{
		if($all=='')
		{
			$this->dbHandels[$this->dbNumber]->autoExecute($val);
		}
		else
		{
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				$this->dbHandels[$i]->autoExecute($val);
			}
		}
	}
	function bindVar($var,&$val,$type,$size)
	{
		$v = true;
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			if(!$this->dbHandels[$i]->bindVar($var,$val,$type,$size))
			{
				$v=false;
			}
		}
		return $v;
	}
	function bindWith($obj)
	{
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			$this->dbHandels[$i]->bindWith($obj);
		}
	}
	function getDescriptor($name,$type,$dbType)
	{
		return $this->dbHandels[$this->dbNumber]->getDescriptor($name,$type,$dbType);
	}
	public function execute()
	{
		
		$this->queryStatus=false;
		if($this->queryType!="DQL")
		{
			$this->queryStatus = true;
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				$this->dbHandels[$i]->execute();
				if(!$this->dbHandels[$i]->isQueryComplite())
				{
					$this->queryStatus=false;
					break;
				}
				else
				{
					$this->dbHandels[$i]->scnUpdate();
				}
			}
			return $this->getDbResource();
		}
		
	}
	public function getResult()
	{
		$result = true;
		if($this->syncMode=="all_db")
		{
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				if(!$this->dbHandels[$i]->result)
				{
					$result=false;
				}
			}
		}
		else if($this->syncMode=="current")
		{
			$result=$this->dbHandels[$this->dbNumber]->result;
		}		
		return $result;
	}
	public function resultFree()
	{
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			$this->dbHandels[$i]->resultFree();
		}		
	}
	public function parse($q,$type)
	{
		
		$this->queryStatus=false;
		$this->queryType = $type;
		
		if($type=="DQL")
		{
			$this->queryStatus=true;
			$rec = $this->dbHandels[$this->dbNumber]->parse($q,$type);
			$this->dbHandels[$this->dbNumber]->execute();
			return $rec;
		}
		else
		{
			
			$this->queryStatus = true;
			if($this->syncMode=="all_db")
			{
				for($i=0;$i<count($this->dbHandels);$i++)
				{

					$this->dbHandels[$i]->parse($q,$type);
					if($this->dbHandels[$i]->autoExecute)
					{
						$this->dbHandels[$i]->execute();
						if($this->dbHandels[$i]->isQueryComplite())
						{
							$this->dbHandels[$i]->scnUpdate();
						}
					}
					if(!$this->dbHandels[$i]->isQueryComplite())
					{
						$this->queryStatus=false;
						break;
					}
				}
			}
			else if($this->syncMode=="current")
			{
				$this->dbHandels[$this->dbNumber]->parse($q,$type);
				if($this->dbHandels[$this->dbNumber]->autoExecute)
				{
					$this->dbHandels[$this->dbNumber]->execute();
					if($this->dbHandels[$this->dbNumber]->isQueryComplite())
					{
						$this->dbHandels[$i]->scnUpdate();
					}	
				}
				if(!$this->dbHandels[$this->dbNumber]->isQueryComplite())
				{
					$this->queryStatus=false;
				}	
			}

			
			
			return $this->getDbResource();
			
		}
	}

	public function autoCommit($val,$all='')
	{
		
		if($all=='')
		{
			// echo ".".$this->dbNumber.".";
			// print_r($this->dbHandels[$this->dbNumber]);
			// echo "AS";
			return $this->dbHandels[$this->dbNumber]->autoCommit($val);
		}
		else
		{
			$rec=true;
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				if(!$this->dbHandels[$i]->autoCommit($val));
				{
					
					$rec=false;
				}
			}
			
			return $rec;
		}
	}
	public function commit($all='')
	{
		if($all=='')
		{
			return $this->dbHandels[$this->dbNumber]->commit();
		}
		else
		{
			$rec = true;
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				if(!$this->dbHandels[$i]->commit())
				{
					$rec=false;
				}
			}
			return $rec;
		}
	}
	public function getMessage()
	{
		$temp = array();

		for($i=0;$i<count($this->dbHandels);$i++)
		{
			
			$temp[$i]=$this->dbHandels[$i]->getMessage();
		}
		
		return $temp;
	}
	public function rollback($all='')
	{
		if($all=='')
		{
			return $this->dbHandels[$this->dbNumber]->rollback();
		}
		else
		{
			$rec=true;
			for($i=0;$i<count($this->dbHandels);$i++)
			{
				if(!$this->dbHandels[$i]->rollback())
				{
					$rec=false;
				}
			}
			return $rec;
		}
	}
	public function close()
	{
		$this->scnMgmt->close();
		for($i=0;$i<count($this->dbHandels);$i++)
		{
			$this->dbHandels[$i]->closeDatabase();
		}
	}
}
?>
