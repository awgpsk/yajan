<?php
class YajanInfo
{
	var $infoFile;
	var $enc;
	var $data;

	function __construct()
	{
		global $YajanInfo;
		$this->init();
	}

	function init()
	{
		global $YAJAN_DATA,$FREAMWORK_PATH,$libVersion;
		$this->enc = new ENC();
		$this->infoFile = "$YAJAN_DATA/data/info.conf";
		// $this->infoFile = "$FREAMWORK_PATH/lib/$libVersion/data/info.conf";
		if(!is_dir("$FREAMWORK_PATH/lib/$libVersion/data"))
		{
			mkdir("$FREAMWORK_PATH/lib/$libVersion/data");
		}
		

		$this->data = array();
		$this->data['info']=array();
		$this->data['update']=array();
		$this->data['patch']=array();
		if(!file_exists($this->infoFile))
		{
			$this->write();
		}
		$this->load();
		
	}

	function load()
	{
		$this->data = file_get_contents($this->infoFile);
		$this->data = $this->enc->m_decrypt($this->data);
		$this->data = unserialize($this->data);
	}

	function write()
	{
		global $YAJAN_DATA;
		if(!is_dir($YAJAN_DATA."/data"))
		{
			mkdir($YAJAN_DATA."/data");
		}	
		$str = serialize($this->data);
		$str = $this->enc->m_encrypt($str);
		file_put_contents($this->infoFile,$str);
		$this->init();
	}
	function addInfo($key,$value)
	{
		$key=strtoupper($key);
		$this->data["info"][$key]=$value;
		$this->write();
	}
	function addUpdate($value)
	{
		$this->data["update"][count($this->data["update"])]=$value;
		$this->write();
	}
	function addPatch($value)
	{
		$this->data["patch"][count($this->data["patch"])]=$value;
		$this->write();
	}
	function showInfo()
	{
		print_r($this->data["info"]);
	}
	function showPatch()
	{
		print_r($this->data["patch"]);
	}	
	function showUpdate()
	{
		print_r($this->data["update"]);
	}
	function getInfo($key)
	{	
		$key=strtoupper($key);
		return $this->data["info"][$key];
	}
}
?>