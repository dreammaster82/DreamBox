<?php

class xml_parser
{
	var $arrOutput = array();
	var $resParser;
	var $strXmlData;

	function xml_parser($tfile = "")
	{
		if(trim($tfile) != "") $this->loadFile($tfile);
	}

	function loadFile($tfile)
	{
		$this->thefile = $tfile;
		$th = file($tfile);
		$tdata = implode("\n", $th);

		return $this->parse($tdata);
	}
   
	function parse($strInputXML) 
	{
		$this->resParser = xml_parser_create ();
		xml_set_object($this->resParser, $this);
		xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

		xml_set_character_data_handler($this->resParser, "tagData");

		$this->strXmlData = xml_parse($this->resParser, $strInputXML);

		if(!$this->strXmlData) 
		{
			die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->resParser)), xml_get_current_line_number($this->resParser)));
		}

		xml_parser_free($this->resParser);

		return $this->arrOutput;
	}
   
	//called on each xml tree
	function tagOpen($parser, $name, $attrs) 
	{
		if(!is_array($name)) $name = util::decode("UTF-8", "CP1251", $name);
		if(!is_array($attrs)) $attrs = util::decode("UTF-8", "CP1251", $attrs);
		$tag = array("nodename" => $name, "attributes" => $attrs);
		array_push($this->arrOutput, $tag);
	}
  
	//called on data for xml
	function tagData($parser, $tagData) 
	{
		if(trim($tagData)) 
		{
			if(isset ($this->arrOutput[count ($this->arrOutput)-1]['nodevalue'])) 
			{
				$this->arrOutput[count($this->arrOutput)-1]['nodevalue'] .= $this->parseXMLValue($tagData);
			}
			else 
			{
				$this->arrOutput[count($this->arrOutput)-1]['nodevalue'] = $this->parseXMLValue($tagData);
			}
		}
	}
  
	//called when finished parsing
	function tagClosed($parser, $name) 
	{
		$this->arrOutput[count($this->arrOutput)-2]['childrens'][] = $this->arrOutput[count($this->arrOutput)-1];
       
		if(count($this->arrOutput[count($this->arrOutput)-2]['childrens'] ) == 1)
		{
			$this->arrOutput[count($this->arrOutput)-2]['firstchild'] =& $this->arrOutput[count($this->arrOutput)-2]['childrens'][0];
		}
		array_pop($this->arrOutput);
	}

	function toArray()
	{
		//not used, we can call loadString or loadFile instead...
	}

	function parseXMLValue($tvalue)
	{
		if(!is_array($tvalue)) $tvalue = util::decode("UTF-8", "CP1251", $tvalue);
		//$tvalue = htmlentities($tvalue);
		return $tvalue;
	}

	function toXML($tob = null)
	{
		//return back xml
		$result = "";

		if( $tob == null) $tob = $this->arrOutput;
       
		if(!isset($tob))
		{
			echo "XML Array empty...";
			return null;
		}


		for($c = 0; $c < count($tob); $c++)
		{
			$result .="<" . $tob[$c]["nodename"];
           
			while (list($key, $value) = each($tob[$c]["attributes"]))
			{
				$result .=" " . $key."=\"" . $this->parseXMLValue($value) . "\"";
			}

			$result .= ">";
           
			//assign node value
			if(isset($tob[$c]["nodevalue"]))
			{
				$result .= $tob[$c]["nodevalue"];
			}
           
			if(count($tob[$c]["childrens"]) > 0)
			{
				$result .= "\r\n" . $this->toXML(&$tob[$c]["childrens"]) . "";
			}

			$result .= "</" . $tob[$c]["nodename"] . ">\r\n";
           
           
		}//end of each array...
       
		return $result;
	}

	function displayXML()
	{
		return ($this->arrOutput);
	}

	function getXML($tob = null)
	{
		return "<?xml version='1.0'?>\r\n" . $this->toXML($tob);
	}

}//end of class

?>