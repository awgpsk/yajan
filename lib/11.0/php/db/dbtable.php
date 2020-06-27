<?php
class DBTable extends Recordset
{
	var $table;
	var $db;
	var $newId;
	var $query;
	var $preInsert;
	var $postInsert;
	var $onInsert;
	var $requiredColumns;
	var $condition;
	var $orderBy;
	var $skipColumn;
	function __construct($name,$database)
	{
		parent::__construct();
		$this->table = $name;
		$this->db=$database;
		$this->newId = null;
		$this->preInsert=null;
		$this->postInsert=null;
		$this->onInsert=null;
		$this->requiredColumns = null;
		$this->formulaColumn =array();
		$this->condition=array();
		$this->orderBy="";
		$this->skipColumn = "";
	}
	function initDb()
	{
		$this->getColumns();
	}
	function addTrigger($triggerName,$functionName)
	{
		if($triggerName=="preInsert")
		{
			$this->preInsert=$functionName;
		}
		else if($triggerName=="postInsert")
		{
			$this->postInsert=$functionName;
		}
		else if($triggerName=="preUpdate")
		{
			$this->preUpdate=$functionName;
		}
		else if($triggerName=="postUpdate")
		{
			$this->postUpdate=$functionName;
		}
		else if($triggerName=="preDelete")
		{
			$this->preDelete=$functionName;
		}
		else if($triggerName=="postDelete")
		{
			$this->postDelete=$functionName;
		}
	}
	function addCondition($name,$condition,$value,$join="and")
	{
		$obj = array();
		$obj["field"]=$name;
		$obj["condition"]=$condition;
		$obj["value"]=$value;
		$obj["join"]=$join;

		$this->condition[count($this->condition)]=$obj;



	}
	function orderBy($columns,$desc=false)
	{

		$this->orderBy = $columns;
		if($desc == true)
		{
			$this->orderBy .= " desc";
		}
	}
	function preInsert(&$data,$columns,$db)
	{
		if($this->preInsert!=null)
		{
			$f = $this->preInsert.'($data,$columns,$db);';
			eval($f);
		}
	}
	function postInsert(&$data,$columns,$db)
	{
		if($this->postInsert!=null)
		{
			$f = $this->postInsert.'($data,$columns,$db);';
			eval($f);
		}
	}
	function preUpdate(&$data,$columns,$db)
	{
		if($this->preUpdate!=null)
		{
			$f = $this->preUpdate.'($data,$columns,$db);';
			eval($f);
		}
	}
	function postUpdate(&$data,$columns,$db)
	{
		if($this->postUpdate!=null)
		{
			$f = $this->postUpdate.'($data,$columns,$db);';
			eval($f);
		}
	}
	function preDelete(&$data,$columns,$db)
	{
		if($this->preDelete!=null)
		{
			$f = $this->preDelete.'($data,$columns,$db);';
			eval($f);
		}
	}
	function postDelete(&$data,$columns,$db)
	{
		if($this->postDelete!="")
		{
			$f = $this->postDelete.'($data,$columns,$db);';
			eval($f);
		}
	}
	function setTable($name)
	{
		$this->table=$name;
	}
	function getColumns()
	{
		if($this->db->getDriver()=="mysql" || $this->db->getDriver()=="mysqli")
		{
			$q="select * from $this->table limit 1,1";
		}
		else if($this->db->getDriver()=="oracle")
		{
			$q="select * from $this->table where rownum < 2";
		}
		$r = $this->db->execute($q);
		
		$this->columns = $r->columns;
		return $r->columns;
	}
	function getMax($column,$init="1")
	{
		if($this->db->getDriver()=="mysql" || $this->db->getDriver()=="mysqli")
		{
			$sql="select IFNULL(max($column),0)+1 as maxval from $this->table";
		}
		else if($this->db->getDriver()=="oracle")
		{
			$sql="select nvl(max($column),0)+1 as maxval from $this->table";
		}
		
		
		$find=$this->db->execute($sql);
		$maxVal=$find->data[0]['MAXVAL'];
		if(!$maxVal)
		{
			$maxVal=$init;
		}
		return $maxVal;
	}
	function importRecordset($r)
	{
		$this->count = $r->count;
		$this->data = $r->data;
		$this->columns = $r->columns;
		$this->countColumns = $r->countColumns;
		$this->recordPosition = 0;
		$this->populationStatus = true;
		$this->encription = $r->encription;
		$this->encrptCode = $r->encrptCode;
		
	}
	function getNextVal($column,$init=0)
	{
		$val = $this->getMax($column,$init);
		return ($val+1);
	}
	function requiredColumns($requiredColumns,&$nullColumnName)
	{
		$requiredColumns = strtoupper($requiredColumns);
		$this->requiredColumns = $requiredColumns;
		$requiredColumns = explode(",",$requiredColumns);
		//print_r($requiredColumns);
		for($i=0;$i<$this->count;$i++)
		{
			for($j=0;$j<count($requiredColumns);$j++)
			{
				if(!isset($this->data[$i][$requiredColumns[$j]]) || $this->data[$i][$requiredColumns[$j]]=="")
				{
					$nullColumnName = $requiredColumns[$j];
					return false;
				}
			}
		}
		return true;

	}
	function insert($insert=false,$autoCommit=false,$excludeColumns="")
	{
		
		$this->db->autoCommit(false);
		$resp = $this->_insert($insert,$excludeColumns);
		
		if($autoCommit)
		{
			if($resp)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}
		}
		
		return $resp;
	}
	function update($whereList,$update=false,$autoCommit=false,$forceUpdate="",$excludeColumns="")
	{
		
		$this->db->autoCommit(false);
		$resp = $this->_update($whereList,$update,$forceUpdate,$excludeColumns);
		if($autoCommit)
		{
			if($resp)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}
		}
		return $resp;
	}
	function findColumByName($name)
	{
		$name = strtoupper($name);
		
		for($i=0;$i<count($this->columns);$i++)
		{
			if($this->columns[$i]->getName()==$name)
			{
				return $this->columns[$i];
			}
		}
		return null;
	}
	private function _update($whereList,$autoUpdate,$forceUpdateColumns="",$excludeColumns="")
	{
		$col="";
		$value="";
		$whereList = strtoupper($whereList);
		$forceUpdateColumns = strtoupper($forceUpdateColumns);
		$forceUpdateColumns = explode(",", $forceUpdateColumns);

		$excludeColumns = strtoupper($excludeColumns);
		$excludeColumns = explode(",", $excludeColumns);
		
		$this->recordPosition = 0;
		$sql="";
		$err = false;
		$whereList = explode(",", $whereList);
		for($i=0;$i<$this->count;$i++)
		{
			$value="";
			$col = "";
			$where ="";
			$set = "";
			for($x=0;$x<count($this->columns);$x++)
			{
				
				$c = $this->columns[$x];
				
				$w = $c->getName();
				$f = $c->getFormat();
				$t = $c->getType();
				$v = $this->data[$i][$c->getName()];
				if($t=="DATE")
				{
					if($f!="")
					{
						$v="to_date('".$v."','".$f."')";
					}
					else
					{
						$v="'$v'";
					}
				}
				else
				{
					$v="'$v'";
				}
				if(array_search($w, $whereList)!==false )
				{
					$where .= "$w = $v and";
					if(array_search($w,$forceUpdateColumns)!==false)
					{
						$set .= "$w = $v,";
					}
				}
				else
				{
					
					if(array_search($w,$excludeColumns)===false)
					{
						$set .= "$w = $v,";
					}
				}
			}
			//echo $set;
			$where = rtrim($where,"and");
			$set = rtrim($set,",");
			
			if($where!="")
			{
				$where = "where $where";
			}
			$this->preUpdate($this->data[$i],$this->columns,$this->db);
			$q="update ".$this->table." set $set $where";
			
			if($autoUpdate)
			{
				if(!$this->db->execute($q))
				{
					$err=true;
				}
				$this->postUpdate($this->data[$i],$this->columns,$this->db);
			}
			$sql .= $q.";\n";
		}
		if(!$autoInsert)
		{
			return $sql;	
		}
		else
		{
			if($err)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
	}
	private function _insert($autoInsert,$excludeColumns)
	{
		$col="";
		$value="";
		$this->recordPosition = 0;
		$this->sql="";
		$err = false;
		$excludeColumns = strtoupper($excludeColumns);
		$excludeColumns = explode(",",$excludeColumns);

		for($i=0;$i<$this->count;$i++)
		{
			$value="";
			$col = "";
			$this->preInsert($this->data[$i],$this->columns,$this->db);
				
			for($j=0;$j<count($this->columns);$j++)
			{
				$colName = $this->columns[$j]->getDbName();
				
				
				if(array_search($colName,$excludeColumns)===false)
				{
					$v = $this->data[$i][$colName];
					if($this->skipColumn==$colName && $v=="")
					{
							$skip = true;

					}
					else 
					{
						
						
						if($this->columns[$j]->getType()=="DATE")
						{
							if($this->columns[$j]->getFormat()!="")
							{
								$value.="to_date('".$v."','".$this->columns[$j]->getFormat()."'),";
							}
							else
							{
								$value.="'".$v."',";
							}
						}
						else
						{
							$value.="'".$v."',";
						}
						if($colName!="")
						{
							$col.=$colName.",";
						}
					}
				}
			}
			$col = rtrim($col,",");
			$value = rtrim($value,",");
			$q="insert into ".$this->table."($col) values($value)";
			if($autoInsert)
			{
				
				if(!$this->db->execute($q))
				{
					$err=true;
				}
				$this->postInsert($this->data[$i],$this->columns,$this->db);	
			}
			$this->sql .= $q.";\n";
		}
		if(!$autoInsert)
		{
			return $this->sql;	
		}
		else
		{
			if($err)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
	}
	function getNewId()
	{
		$obj = null;
		
		foreach($this->columns as $col)
		{
			if($col->getKey()=="PRIMERY")
			{
				$obj=$col;
			}
		}
		if($obj!=null)
		{
			if($obj->getType()!="NUMBER")
			{
				$this->message->add("Error","Primery key column is not number format.");
				return false;
			}
			$name = $obj->getName();

			if(strtoupper($this->db->parameter['driver'])==strtoupper("ORACLE"))
			{
				$maxFun="nvl(max($name),0)+1 as total";
			}
			else
			{
				$maxFun="ifnull(max($name),0)+1 as total";
			}
			$q="select $maxFun from ".$this->table;
			$r = $this->db->execute($q);
			if($r)
			{
				$r = $r->getRow();
				$this->newId = $r['TOTAL'];
				$this->data[$this->recordPosition][strtoupper($name)]=$this->newId;
			}
			else
			{
				$this->message->add("Error","Query error during getting new id.");
				return false;
			}
			return true;
		}
		else
		{
			$this->message->add("Error","No primery key found.");
			return false;
		}
	}
	function delete($whereColumnList,$delete=false,$autoExecute=false)
	{
	

		
		$this->preDelete($this->data[$i],$this->columns,$this->db);

		$where = $this->arrayToSqlString($whereColumnList,"where");
		if($where!="")
		{
			$where = "where $where";
		}
	
	
		
		
		$q="delete from $this->table $where";
		
		if($autoExecute)
		{
			$r = $this->db->execute($q);
			$this->postDelete($this->data[$i],$this->columns,$this->db);
			return $r;
		}
		else
		{
			return $q;
		}
	}
	function selectAll($columns="*")
	{
		
		return $this->query($columns,"",true);
	}
	function getConditionSql()
	{
		$sql = "1 = 1";
		
		for($i=0;$i<count($this->condition);$i++)
		{
			$cn = $this->condition[$i];
			$f = strtoupper($cn["field"]);
			$fn = $this->findColumByName($f);
			$v = $cn["value"];
			$tp = $fn->getType();

			if($tp=="DATE")
			{
				$fr = $fn->getFormat();
				$v = "to_date('$v','$fr')";
			}
			else
			{
				$v = "'$v'";
			}
			if($cn["condition"]=="in" || $cn["condition"] == "not in")
			{
				$v = trim($v,"'");
				$v = explode(",",$v);
				$v = "'".join("','",$v)."'";
				$v = "($v)";
			}
			$sql.= ' '.$cn["join"].' ('.$f.' '.$cn["condition"].' '.$v.')';
			
		}
		
		return $sql;
	}
	function query($from="*",$query="",$autoExecute=false)
	{
		$where = "";

		if(gettype($query)=="string")
		{
			if($query!="")
			{
				$this->query=$query;
			}
		}
		else if(gettype($query)=="array")
		{
			$where = $this->arrayToSqlString($query,"where");;
			if($where!="")
			{
				$where = "where $where";
			}
			$this->query="";
		}
		if(gettype($query)=="boolean")
		{
			$autoExecute=true;
			$this->query="";
		}
		if($this->query=="")
		{
			$condition = $this->getConditionSql();
			if($where=="")
			{
				$where = "where ".$condition;
			}
			else
			{
				$where .= " and ".$condition;
			}
			if($this->orderBy!="")
			{
				$this->orderBy = "order by $this->orderBy";
			}
			$q="select $from from $this->table $where $this->orderBy";
			
		}
		else
		{
			$q=$this->query;
		}
		if($autoExecute)
		{
			
			$r = $this->db->execute($q);

			$this->importRecordset($r);
			return $r;
		}
		else
		{
			return $q;
		}
	}
	private function arrayToSqlString($data,$type)
	{
		$val="";
		$col = "";
		$where ="";
		$set = "";
		$output="";
		foreach ($data as $key => $value) 
		{
			$c = $this->findColumByName($key);	
			$w = $c->getName();
			$f = $c->getFormat();
			$t = $c->getType();
			$v = $value;
			if($t=="DATE")
			{
				if($f!="")
				{
					$v="to_date('".$v."','".$this->columns[$j]->getFormat()."')";
				}
				else
				{
					$v="'$v'";
				}
			}
			else
			{
				$v="'$v'";
			}
			
			if($type=="insert")
			{
				$col.="$w,";
				$val.="$v,";
			}
			else if($type=="update")
			{
				$val .= "$w=$v,";
			}
			else if($type=="where")
			{
				$val .= "$w = $v and ";
			}
		}
		
		$col = rtrim($col,",");
		$val = rtrim($val,",");
		$val = rtrim($val,"and ");
		//echo $val;
		$output = "";
		if($type=="insert")
		{
			$output = "($col) values($val)";
		}
		else if($type=="update")
		{
			$output = "$val";
		}
		else if($type=="where")
		{
			$output = "$val";
		}
		return $output;
	}
	function countColumnValue($name)
	{
		$q="select count(*) as total from ".$this->table." where $name = '".$this->data[$this->recordPosition][strtoupper($name)]."'";
		$t=$this->db->execute($q);
		return $t->data[0]['TOTAL'];
	}
	function createJsObject($name)
	{
		$str = serialize($this);
		echo '<script type="text/javascript">var '.$name.' = new Recordset();'.$name.'.load('."'$str'".'); </script>';
	}
	function loadArray($array)
	{
		$r = new Recordset();
		$a = array();
		foreach ($array as $key => $value) {
			$r->addColumns($key);	
			$a[strtoupper($key)]=$value;
		}
		$r->add($a);
		$this->importRecordset($r);
	}
	function deleteAll()
	{
		return $this->db->execute("delete from $this->table");
	}
	function autoValue($column,$pattern)
	{
		$db= $this->db;
		$column = strtoupper($column);
		$table = $this->table;
		$data = $this->data[$this->recordPosition];
		$val = md5(uniqid($table,true).rand().microtime());
		
		if($pattern!="")
		{
		   
			$pattern = strtoupper($pattern);
			preg_match_all('#\{(.*?)\}#', $pattern, $match);
			$date = $db->sysdate("dd/mm/rrrr");
			$date = explode("/",$date);
			
			foreach ($match[1] as $key => $vc) {
				$vk = $vc;
				$vc = explode(":",$vc);
			  
				if(count($vc)>1)
				{
					
					if($vc[0]=="F")
					{
						$fx = explode("<",$vc[1]);
						if(count($fx)>1)
						{
							$fn = strtolower($fx[0]);
							if(isset($data[strtoupper($fx[1])]))
							{
								$val = $fn($data[strtoupper($fx[1])]);
	
							}
						}
						else
						{
							if(isset($data[strtoupper($fx[0])]))
							{
								$val = $data[strtoupper($fx[0])];
							}
						}
					}
					$vc = $val;
				}
				else if($vk=="DD")
				{
					$vc = $date[0];
				}
				else if($vk=="MM")
				{
					$vc = $date[1];
				}
				else if($vk=="RR")
				{
					$vc = substr($date[2],2,2);
				}
				else if($vk=="RRRR")
				{
					$vc = $date[2];
				}
				
				$pattern = str_replace("{$vk}",$vc,$pattern);
			}
			
			//$val = preg_replace('/[^A-Za-z0-9\-]/', '', $val);
	
			
			
			$len=0;
			$pp="";
			for($i=0;$i<strlen($pattern);$i++)
			{
				if($pattern[$i]=="#")
				{
					$len++;
					
				}
				else
				{
					$pp.=$pattern[$i];
				}
			}
			$pattern = $pp;
			$pattern = strtoupper($pattern);
			$pattern = preg_replace('/[^A-Za-z0-9\-\/]/', '', $pattern);
			//echo $pattern;
			if($len>0)
			{
				$q="select max(to_number(substr($column,length('$pattern')+1,100)))+1 as total from $table where $column like '$pattern%'";
				//echo $q;
				$r = $db->execute($q);
				$id = $r->get("total");
				
				if($id=="")
				{
					$id = 1;
				}
				$id = str_pad($id, $len, "0", STR_PAD_LEFT);
				$val = $pattern.$id;
			}
		}
	 
		$this->data[$this->recordPosition][$column] = $val;
		return $val;
	}
	function skipNullRecord($code)
	{
			$code = strtoupper($code);
			$this->skipColumn = $code;
	}
	
}
?>