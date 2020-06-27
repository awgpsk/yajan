<?php
function preInsertFunctionEMService(&$row,&$col,$db)
		{
			$series = $row["ERROR_SERIES"];
			$instanceId = $row["INSTANCE_ID"];
			$q="select max(error_sequance)+1 as out from errorDefination where error_series = '".$series."'";
			$r = $db->execute($q);
			$id= $r->get("out");
			if($id=="")
			{
				$id = 1;
			}
			$code = $series."-".str_pad($id,5,"0",STR_PAD_LEFT);
			
			//echo "AAS";
			$q="select max(error_id)+1 as total from errorDefination where instance_id = '$instanceId'";
			$r = $db->execute($q);
			$nid = $r->get("total");
			if($nid=="")
			{
				$nid=1;
			}
			$row["ERROR_SEQUANCE"]=$id;
			$row["ERROR_CODE"]=$code;
			$row["ERROR_ID"]=$nid;
		}
class EMErrorObject
{
	var $code;
	var $text;
	var $description;
	var $reson;
	var $sollution;
	var $application;
	var $type;
	var $extra;
	function __construct()
	{
			$this->extra = null;
	}
	function upateArray($data)
	{
		$data["text"]=$this->text;
		$data["type"]=$this->type;
		$data["errorCode"]=$this->code;
		$data["errorDescription"]=$this->description;
		
		if($this->reson!="")
		{
			$data["errorReson"]=$this->reson;	
		}
		if($this->sollution!="")
		{
			$data["errorSolution"]=$this->sollution;	
		}
		return $data;
	}
	function extra($value)
	{
			$this->extra=$value;
	}
	function toArray()
	{
		$data = array();
		$data["type"]=$this->type;
		$data["text"]=$this->text;
		$data["errorCode"]=$this->code;
		$data["extra"]=$this->extra;
		$data["errorDescription"]=$this->description;
		if($this->reson!="")
		{
			$data["errorReson"]=$this->reson;	
		}
		if($this->sollution!="")
		{
			$data["errorSolution"]=$this->sollution;	
		}
		return $data;		
	}
	function __toString()
	{
		$data = array();
		$data["type"]=$this->type;
		$data["text"]=$this->text;
		$data["errorCode"]=$this->code;
		$data["extra"]=$this->extra;
		$data["errorDescription"]=$this->description;
		if($this->reson!="")
		{
			$data["errorReson"]=$this->reson;	
		}
		if($this->sollution!="")
		{
			$data["errorSolution"]=$this->sollution;	
		}
		return json_encode($data);
	}
}
class EMService
{
	var $home;
	var $application;
	var $connection;
	var $instanceName;
	var $instanceId;
	var $log;
	var $list;
	function __construct()
	{
		global $YAJAN_DATA,$APPLICATION_NAME,$CONFIGURATION,$LOG_PATH;
		$this->instanceName = $CONFIGURATION->instanceName;
		$this->instanceId = $CONFIGURATION->instanceId;
		$this->list = array();
		$this->home = $YAJAN_DATA."/"."emServiceData/errorList.sqlite";
		$newDb = false;
		if(!file_exists($this->home))
		{
			$newDb = true;
		}
		$connectionString = "/@sqlite:::$this->home:rw";
		$this->connection = new Connection($connectionString);
		
		if(!is_dir($LOG_PATH))
		{
			mkdir($LOG_PATH);
		}
		$this->log = new Logfile("emCall",$LOG_PATH);
		if($newDb)
		{
			$this->initDb();
		}
	}
	function initDb()
	{
		$q="CREATE TABLE errorDefination ( `error_sequance` INTEGER NOT NULL, `error_series` TEXT NOT NULL, `error_code` TEXT, `error_type` TEXT NOT NULL, `error_description` TEXT NOT NULL, `error_resion` TEXT, `error_solution` TEXT, `error_registration_date` DATE, `instance_name` TEXT NOT NULL, `instance_id` TEXT NOT NULL, `error_id` INTEGER NOT NULL, PRIMARY KEY(`error_code`) )";
		$this->connection->execute($q);

		$q="CREATE INDEX `err_i01` ON `errorDefination` ( `error_code` )";
		$this->connection->execute($q);		
	}
	function register($type,$series,$description,$reson="",$sollution="")
	{
		global $APPLICATION_NAME;

		$filename = $this->home ."/". md5($code);
		$data = array();
		$data["error_sequance"]="";
		$data["error_series"]=$series;
		$data["error_code"]="";
		$data["error_id"]="";
		$data["error_type"] = $type;
		$data["error_description"]=$description;
		$data["error_solution"]=$sollution;
		$data["error_resion"]=$reson;
		$data["error_registration_date"]=date("Y-m-d");
		$data["instance_name"]=$this->instanceName;
		$data["instance_id"]=$this->instanceId;

		
		$dt = new DBTable("errorDefination",$this->connection);
		$dt->loadArray($data);
		$dt->preInsert("preInsertFunctionEMService");
		$q=$dt->insert(true);
		//echo "AS.$q.AS";
		return $dt->get("error_code");

	}
	function getList($errorCode="")
	{

		$dt = new DBTable("errorDefination",$this->connection);
		$f="error_code,error_description,error_resion";
		if($errorCode!="")
		{
			$errorCode="where error_code='$errorCode' ";
			$f = "*";
		}
		$r = $dt->query("","select $f from errorDefination $errorCode",true);
		return $r;
	}
	function getDefinition ($code,&$data=null,$message="")
	{
		global $CALL_SEQUANCE_TIME;
		$r = $this->getList($code);
		
		$doc = new EMErrorObject();
		if($r->count==1)
		{
			$doc->code = $r->get("error_code");
			$doc->text = $r->get("error_description");
			$doc->description = $r->get("error_description");
			$doc->reson = $r->get("error_reson");
			$doc->sollution = $r->get("error_solution");
		}
		else
		{
			$doc->code = $r->get("error_code");
			$doc->text = $r->get("error_description");			
		}
		$doc->text .= $message;
		if($doc->code=="")
		{
			$doc->code=$code;
		}
		if(gettype($data)=="array")
		{
			$data = $doc->upateArray($data);
		}
		$bt = debug_backtrace ();
		$filename = $bt[0]["file"];
		$line = $bt[0]["line"];

		$this->log->write("$CALL_SEQUANCE_TIME|$code|$doc->text|$filename:$line");
		return $doc;
	}
	function exportJSON($filename)
	{
		$dt = new DBTable("errorDefination",$this->connection);
		$r = $dt->query("select * from errorDefination");
		$json = ( json_encode($r->data));
		file_put_contents($filename, $json);
	}
	function importJSON($filename)
	{
		global $CS;
		if(file_exists($filename))
		{
			$r = json_decode(file_get_contents($filename),true);	
			
			$dt = new DBTable("errorDefination",$this->connection);
			$dt->deleteAll();
			for($i=0;$i<count($r);$i++)
			{
				$dt->loadArray($r[$i]);
				$dt->insert(true,true)."\n";
			}
		}
		else
		{
			$CS->showError("File $filename is not exist");
		}
	}
	function message($code,$type,$message)
	{
		$doc = new EMErrorObject();
		$doc->code = $code;
		$doc->type = $type;
		$doc->text = $message;
		$this->list[count($this->list)]=$doc;
		return $doc;
	}
	function isSuccess(&$out=null)
	{
		
		for($i=0;$i<count($this->list);$i++)
		{
			if($this->list[$i]->type!="success")
			{
				$out=$this->list[$i];
				return false;
			}
		}
		
		return true;
	}

	function isError(&$out=null)
	{
		for($i=0;$i<count($this->list);$i++)
		{
			if($this->list[$i]->type=="error")
			{
				$out=$this->list[$i];
				return true;
			}
		}
		return false;
	}
	
	function success($message)
	{
		return $this->message("0","success",$message);
	}
	function error($code,$message)
	{
		return $this->message($code,"error",$message);
	}
	function info($message)
	{
		return $this->message("0","info",$message);
	}
	function __toString()
	{
		$type = "success";
		for($i=0;$i<count($this->list);$i++)
		{
			if($this->list[$i]->type=="error")
			{
				$type = "errpr";
			}
		}
		$output=array();
		$output["type"]=$type;
		$output["mode"]="list";
		$output["message"]=$this->list;
		return json_encode(($output));
	}
}
?>