<?php
require_once( "pdfcrowd.php");
class PDF
{
	var $filename;
	var $client;
	function __construct($filename="")
	{
		$this->filename=$filename;
		//$$this->client = new Pdfcrowd("username", "apikey");
	}
	function fromHtml()
	{
		
	}
	function fromUrl()
	{
		
	}
}
?>