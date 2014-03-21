<?php
require_once 'lib/sparql/Endpoint.php';
require_once 'lib/sparql/ParserTurtle.php';
require_once 'lib/sparql/ParserCSV.php';

class Test {

    const  PREFIX = <<<'EOT'
prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix : <http://www.w3.org/2009/sparql/docs/tests/data-sparql11/bind/manifest#> 
prefix rdfs:	<http://www.w3.org/2000/01/rdf-schema#> 
prefix mf:     <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> 
prefix qt:     <http://www.w3.org/2001/sw/DataAccess/tests/test-query#> 
prefix dawgt:   <http://www.w3.org/2001/sw/DataAccess/tests/test-dawg#> 
prefix ut:     <http://www.w3.org/2009/sparql/tests/test-update#> 

EOT;

	public $query = "";
	public $data = "";
	public $resultData = "";
	public $URLquery = "";
	public $URLdata = "";
	public $URLresultData = "";
	
	public $URLresultDataType = "application/sparql-results+xml";
	public $result = null;
	//public $resultQuery = null;
	
	private $_errors;
	private $_fails;	
	
	public $queryTime = 0; 
	
	public $_tabDiff = null; 
	
	function __construct($URLquery, $URLdata = "", $URLresultData = "")
	{	
		$this->URLquery = $URLquery;
		$this->URLdata = $URLdata;
		$this->URLresultData = $URLresultData;		
		
		$this->_errors = array();
		$this->_fails = array();
		
		if($URLresultData != ""){
			preg_match("/^.*\.([^\.]+)$/i", $this->URLresultData, $matches);
			$extension = $matches[1];
			switch($extension){
				/*case "nt":				
					$this->URLresultDataType =  "text/plain";*/
				case "csv":
					$this->URLresultDataType =  "text/csv; charset=utf-8";
					break;
				case "tsv":
					$this->URLresultDataType =  "text/tab-separated-values; charset=utf-8";
					break;
				case "ttl":
					$this->URLresultDataType =  "text/turtle";
					break;
				case "srx":
					$this->URLresultDataType = "application/sparql-results+xml";
					break;
				case "srj":
					$this->URLresultDataType = "application/sparql-results+json";
					break;
				default :
					$this->AddFail("DataResultWait has an extension unknown : ".$extension." (".$this->URLresultData.")");	
					print_r($this->_fails);
					exit();					
			}
		}
	}
	
	function doQuery($testResult=false)
	{
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$CURL,$TTRIPLESTORE;	
		$message = "";		
		$test = false;
		
        $TESTENDPOINT->ResetErrors();
		$this->clearAllTriples();
		
		// check if triplestore is empty
		$count = $this->countTriples();
		if($count > 0 || $count == -1){
			$this->AddFail("Dataset is not clean before the test. (".$count." Triples)");
			return;
		}	
		$t1 = Endpoint::mtime();
		$this->query = $CURL->fetch_url($this->URLquery);		
		$this->queryTime = Endpoint::mtime() - $t1 ;
		//init Dataset for the test
		if($testResult){
				$this->data = $CURL->fetch_url($this->URLdata);
				$this->importDataTest();
		}
		
		$output = $this->URLresultDataType;
		if($TTRIPLESTORE == "allegrograph" && $this->URLresultDataType == "text/turtle") //pffffff
		{
				$output = "text/plain";
		}
		//echo "&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&2".$output;
		$this->result = $TESTENDPOINT->queryRead($this->query , $output);		
		$errorsQuery = $TESTENDPOINT->getErrors();
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{
			if($testResult){
				$tabDiff = null;
						
				$message = $this->checkData();
				
				
				// check		
				if(count( $this->_tabDiff)>0){
					$this->AddFail("The test is failed.". $message);
				}
			}
		}
		
		 $TESTENDPOINT->ResetErrors();
		$this->clearAllTriples();
		$count = $this->countTriples();
		if($count > 0 || $count == -1){
			$this->AddFail("Dataset is not clean after the test. (".$count." Triples)");
			
		}	
		
		if($test){
			echo $message;
			print_r($this->_fails);
			exit();	
		}	
	}
	
	function doUpdate($testResult=false)
	{		
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$CURL,$TTRIPLESTORE;		
		$message = "";		
		$test = false;
		
        $TESTENDPOINT->ResetErrors();
		$this->clearAllTriples();
		
		// check if triplestore is empty
		$count = $this->countTriples();
		if($count > 0 || $count == -1){
			$this->AddFail("Dataset is not clean before the test. (".$count." Triples)");
			return;
		}	
		$t1 = Endpoint::mtime();
		$this->query = $CURL->fetch_url($this->URLquery);		
		$this->queryTime = Endpoint::mtime() - $t1 ;
		//init Dataset for the test
		if($testResult){
				$this->data = $CURL->fetch_url($this->URLdata);
				$this->importDataTest();
		}
		
		$TESTENDPOINT->queryUpdate($this->query);
		
		if($testResult){
			$output = $this->URLresultDataType;
			if($TTRIPLESTORE == "allegrograph" && $this->URLresultDataType == "text/turtle") //pffffff
				$output = "text/plain";
			
		//echo "&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&1".$this->URLresultDataType."1".$TTRIPLESTORE."1".$output;
			$this->result = $TESTENDPOINT->queryRead("CONSTRUCT { ?s ?p ?o } WHERE {?s ?p ?o}",$output);
		}
		
		$errorsQuery = $TESTENDPOINT->getErrors();		
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{
			if($testResult){
				$message = $this->checkData();
				
				// check		
				if(count( $this->_tabDiff)>0){
					$this->AddFail("The test is failed.". $message);
				}
			}
		}
		/////////////////////////////
		$TESTENDPOINT->ResetErrors();
		$this->clearAllTriples();
		$count = $this->countTriples();
		if($count > 0 || $count == -1){
			$this->clearAllTriples();
					$count = $this->countTriples();
					if($count > 0 || $count == -1){
						$this->AddFail("Dataset is not clean after the test. (".$count." Triples)");
						
					}
		}		
		if($test){
			echo $message;
			print_r($this->_fails);
			//exit();	
		}
	}
	
	function checkData()
	{
	    global $CURL;	
		$tabDiff = null;
		$test = false;
		$message = "";
		
		//read data			
		$this->resultData = $CURL->fetch_url($this->URLresultData);
		
		$message .= "\n================================================================= \n";
		$message .=  "queryTest :<".$this->URLquery.">\n".$this->query;
		$message .=  "\n================================================================= \n";
		$message .=  "dataInput : <".$this->URLdata.">\n".$this->data;
		$message .=  "\n================================================================= \n";
		$message .=  "resultDataWait : <".$this->URLresultData.">\n".$this->resultData;
		$message .=  "\n================================================================= \n";
		$message .=  "result of dataset: \n";
		$message .=  $this->result;
		$message .=  "\n================================================================= \n";
				
		$sort = ! preg_match("/(?:ORDER|GROUP)/i",$this->URLquery);
				
		switch($this->URLresultDataType){
		case "application/sparql-results+xml":
			$parserSparqlResult = new ParserSparqlResult();	
			xml_parse($parserSparqlResult->getParser(),$this->resultData, true);		
			$tabResultDataWait = $parserSparqlResult->getResult();
			
			xml_parse($parserSparqlResult->getParser(),$this->result, true);		
			$tabResultDataset = $parserSparqlResult->getResult();
				
			if($sort){
				$tabResultDataWait = ParserSparqlResult::sortResult($tabResultDataWait);	
				$tabResultDataset = ParserSparqlResult::sortResult($tabResultDataset);
			}						
			$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
			//$test = true;
			break;
		case "text/tab-separated-values; charset=utf-8":
			$tabResultDataWait = ParserCSV::csv_to_array($this->resultData,"\t");
			$tabResultDataset = ParserCSV::csv_to_array($this->result,"\t");		
			if($sort){
				$tabResultDataWait = ParserCSV::sortTable($tabResultDataWait);	
				$tabResultDataset = ParserCSV::sortTable($tabResultDataset);
			}						
			$tabDiff = Tools::array_diff_assoc_recursive($tabResultDataWait, $tabResultDataset);
			//$test = true;
			break;			
		case "text/csv; charset=utf-8":
			$tabResultDataWait = ParserCSV::csv_to_array($this->resultData);
			$tabResultDataset = ParserCSV::csv_to_array($this->result);		
			if($sort){
				$tabResultDataWait = ParserCSV::sortTable($tabResultDataWait);	
				$tabResultDataset = ParserCSV::sortTable($tabResultDataset);
			}						
			$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
			//$test = true;
			break;						
		case  "text/turtle":		
			$tabResultDataWait = ParserTurtle::turtle_to_array($this->resultData,$this->URLdata);	
			$tabResultDataset = ParserTurtle::turtle_to_array($this->result,$this->URLdata);		
			if($sort){
				$tabResultDataWait = ParserTurtle::sortTriples($tabResultDataWait);	
				$tabResultDataset = ParserTurtle::sortTriples($tabResultDataset);
			}						
			$tabDiff = Tools::array_diff_assoc_recursive($tabResultDataWait["triples"], $tabResultDataset["triples"]);		
			break;							
		case  "application/sparql-results+json":					
			$tabResultDataWait = json_decode($this->resultData, true);
			$tabResultDataset = json_decode($this->result, true);		
			$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
			//$test = true;
			break;		
		default:
			$this->AddFail("The ckeck result is not yet implemented : ".$this->URLresultDataType);	
			print_r($this->_fails);
			exit();		
		}
				
		$message .=  "resultDataWait after parsing : <".$this->URLresultData.">\n";					
		$message .= print_r($tabResultDataWait,true);	
		$message .=  "\n================================================================= \n";
		$message .=  "result of dataset after parsing : \n";
		$message .= print_r($tabResultDataset,true);	
		$message .=  "\n================================================================= \n";
		$message .=  "Difference in the result : \n";
		$message .= print_r($tabDiff ,true);
		$message .=  "\n================================================================= \n";	
		if($test){
			echo "ZZZ".$message;
			exit();
		}
		$this->_tabDiff = $tabDiff;		
		return $message;
	}
	
	function AddError($error) {
		if (!in_array($error, $this->_errors)) {
			$this->_errors[] = $error;
		}
		return true;
	}
	function AddFail($fail) {
		if (!in_array($fail, $this->_errors)) {
			$this->_fails[] = $fail;
		}
		return true;
	}

	function GetErrors() {
		return $this->_errors;
	}
	function ResetErrors() {
		$this->_errors = array();
	}
	function GetFails() {
		return $this->_fails;
	}
	function ResetFails() {
		$this->_fails = array();
	}
	
	function countTriples() {		
		global $modeDebug,$modeVerbose,$TESTENDPOINT;	
		$q = 'SELECT (COUNT(?s) AS ?count) WHERE {GRAPH ?g {  ?s ?p ?v .}} '; 
		$res = $TESTENDPOINT->query($q, 'row');
		$err = $TESTENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"]; //todo trycatch //test with sesame */
    }
	function clearAllTriples() {		
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$TTRIPLESTORE;	
		$q = "";			
		switch($TTRIPLESTORE){
			case "virtuoso":		
				$q = "DELETE WHERE 
				  {
					GRAPH ?g 
					  {
						?o ?p ?v . 
					  }
				}";
				
				$res = $TESTENDPOINT->queryUpdate($q);
				break;
			case "4store":		
				$rows = $TESTENDPOINT->query("SELECT DISTINCT ?g WHERE { GRAPH ?g { ?s ?p ?o } }", 'rows');
				foreach ($rows["result"]["rows"] as $row){	
					$q ="CLEAR GRAPH <".$row["g"].">";
					$res = $TESTENDPOINT->queryUpdate($q);
				}
				break;
			case "sesame":
			case "fuseki":
			default:
				$q = "CLEAR ALL";
				$res = $TESTENDPOINT->queryUpdate($q);
				break;
		}
    }
	
	private function importDataTest(){   	
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$TTRIPLESTORE;	
		$graph =$this->URLdata;
		$content =$this->data;
		switch($TTRIPLESTORE){ 
			case "sesame":		
				SesameTestSuite::importDataTest($TESTENDPOINT,$graph,$content);
				break;
			case "4store":		
				FourStoreTestSuite::importDataTest($TESTENDPOINT,$graph,$content);
				break;
			case "fuseki":
				FusekiTestSuite::importDataTest($TESTENDPOINT,$graph,$content);
				break;
			default:
				TestSuite::importDataTest($TESTENDPOINT,$graph);
		}
   }
   


}


