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
	/*public $data = "";
	public $resultData = "";*/
	public $URLquery = "";
	/*public $URLdataDefaultGraph = "";
	public $URLresultDataDefaultGraph = "";*/
	
	public $ListGraphInput = null;
	public $ListGraphOutput = null;
	public $ListGraphResult = null;
	
	public $URLresultDataDefaultGraphType = "application/sparql-results+xml";
	//public $resultQuery = null;
	
	private $_errors;
	private $_fails;	
	
	public $queryTime = 0; 
	
	public $_tabDiff = null; 
	
	function __construct($URLquery)
	{	
		$this->URLquery = $URLquery;
		
		$this->ListGraphInput = array();
		$this->ListGraphOutput = array();	
		$this->ListGraphResult = array();	
		
		$this->_errors = array();
		$this->_fails = array();
	}
	
	private function readGraphResult()
	{
		global $TESTENDPOINT,$TTRIPLESTORE;
		
		/*$q = Test::PREFIX.' 
		select DISTINCT  ?g 
		where
		 {GRAPH  ?g
				 {
					?s ?p ?o.
				}
		}';
		
		$rows = $TESTENDPOINT->query($q,"rows");
		$errorsQuery = $TESTENDPOINT->getErrors();		
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{

			
		}*/
		
		foreach ($this->ListGraphOutput as $nameGraph=>$dataOutput) {		
			$output = $dataOutput["mimetype"];
			if($TTRIPLESTORE == "allegrograph" && $output == "text/turtle") //pffffff
				$output = "text/plain";
			
			if($nameGraph == "DEFAULT"){
				$this->ListGraphResult["DEFAULT"] = $TESTENDPOINT->queryRead("CONSTRUCT { ?s ?p ?o } WHERE {?s ?p ?o}",$output);
			}else{
				$this->ListGraphResult[$nameGraph] = $TESTENDPOINT->queryRead("CONSTRUCT { ?s ?p ?o } WHERE {GRAPH  <".$nameGraph."> {?s ?p ?o}}",$output);
			}
				
			$errorsQuery = $TESTENDPOINT->getErrors();		
			if ($errorsQuery) {
				$this->_errors = $errorsQuery;
			}
				
				//TODO : Check nb of graph in Result
		}
	}
	
	private function getType($url){
		$type = "";

		preg_match("/^.*\.([^\.]+)$/i", $url, $matches);
		$extension = $matches[1];
		switch($extension){
			case "rdf":				
				$type=  "application/rdf+xml";
				break;
			case "nt":				
				$type=  "text/plain";
				break;
			case "csv":
				$type =  "text/csv; charset=utf-8";
				break;
			case "tsv":
				$type =  "text/tab-separated-values; charset=utf-8";
				break;
			case "ttl":
				$type =  "text/turtle";
				break;
			case "srx":
				$type = "application/sparql-results+xml";
				break;
			case "srj":
				$type = "application/sparql-results+json";
				break;
			default :
				$this->AddFail("DataResultWait has an extension unknown : ".$extension." (".$url.")");	
				print_r($this->_fails);
				exit();					
		}
		return $type;
	}
	
	function addGraphInput($url, $name="DEFAULT",$endpoint="")
	{	
		$this->ListGraphInput[$name]= array ("url"=>$url,"mimetype"=> $this->getType($url),"endpoint"=>$endpoint);
	}
	function addGraphOutput($url, $name="DEFAULT")
	{		
		$this->ListGraphOutput[$name]= array ("url"=>$url,"mimetype"=> $this->getType($url));
	}
	
		
	
	function readAndAddMultigraph($graphTest,$iriTest)
	{
		global $ENDPOINT;
		$qGraphInput = Test::PREFIX.' 
		select DISTINCT  ?graphData ?graphName
		where
		 {GRAPH  <'.$graphTest.'>
				 {
					<'.$iriTest.'>  	mf:action [ ut:graphData [ ut:graph ?graphData ;
															rdfs:label ?graphName ]
										].				
				}
		}';
		$qGraphOutput = Test::PREFIX.' 
		select DISTINCT  ?graphData ?graphName
		where
		 {GRAPH  <'.$graphTest.'>
				 {
					<'.$iriTest.'> 	mf:result [ ut:graphData [ ut:graph ?graphData ;
															rdfs:label ?graphName ]
										] .		
				}
		}';
		$rowsGraph = $ENDPOINT->query($qGraphInput,"rows");
		foreach ($rowsGraph["result"]["rows"] as $rowGraph){
			$this->addGraphInput($rowGraph["graphData"],$rowGraph["graphName"]);
		}
		$rowsGraph = $ENDPOINT->query($qGraphOutput,"rows");
		foreach ($rowsGraph["result"]["rows"] as $rowGraph){
			$this->addGraphOutput($rowGraph["graphData"],$rowGraph["graphName"]);
		}
	}
	
	function readAndAddService($graphTest,$iriTest)
	{
		global $ENDPOINT;
		$qGraphInput = Test::PREFIX.' 
		select DISTINCT  ?graphData ?endpoint
		where
		 {GRAPH  <'.$graphTest.'>
				 {
					<'.$iriTest.'>  	mf:action [ qt:serviceData [
																   qt:endpoint ?endpoint ;
																   qt:data     ?graphData
														   ]
										].				
				}
		}';
		$rowsGraph = $ENDPOINT->query($qGraphInput,"rows");
		foreach ($rowsGraph["result"]["rows"] as $rowGraph){
			$this->addGraphInput($rowGraph["graphData"],$rowGraph["endpoint"],$rowGraph["endpoint"]);
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
			$this->importGraphInput();
		}
		
		$output = $this->URLresultDataDefaultGraphType;
		if($TTRIPLESTORE == "allegrograph" && $this->URLresultDataDefaultGraphType == "text/turtle") //pffffff
		{
			$output = "text/plain";
		}
		
		$this->ListGraphResult["DEFAULT"] = $TESTENDPOINT->queryRead($this->query , $output);		
		$errorsQuery = $TESTENDPOINT->getErrors();
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{
			if($testResult){
				$tabDiff = null;
				
				$message = $this->checkResult();			
				
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
				$this->importGraphInput();
		}
		
		$TESTENDPOINT->queryUpdate($this->query);
		$errorsQuery = $TESTENDPOINT->getErrors();
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{
			if($testResult){
				$this->readGraphResult();
				$message = $this->checkResult();
				
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
	
	private function printTestHead(){	
		$message = "";
		
		$message .= "\n================================================================= \n";
		$message .=  "queryTest :<".$this->URLquery.">\n".$this->query;
		$message .=  "\n================================================================= \n";
		$message .=  "dataInput : \n\n";
		foreach ($this->ListGraphInput as $nameGraph=>$data) {		
			$message .="<".$data["url"].">\n".$data["content"];
			$message .=  "\n******************************** \n";
		}
	
		return $message;
	}
	
	private function checkResult(){	
	    global $CURL;	
		
		$message =  $this->printTestHead();
		foreach ($this->ListGraphOutput as $nameGraph=>$dataOutput) {		
			//read data			
			$expected = $CURL->fetch_url($dataOutput["url"]);
			//$this->ListGraphOutput[$nameGraph]["content"]=$expected;
			
			$message .=  "\n================================================================= \n";
			$message .=  "Data Expected in graph : \n\n";	
			$message .="<".$dataOutput["url"].">\n".$expected;
			$message .= $this->checkDataInGraph($nameGraph,$dataOutput["mimetype"],$expected,$this->ListGraphResult[$nameGraph]);
		}
		return $message;
	}
	
	private function checkDataInGraph($nameGraph,$mimetype,$expected,$result)
	{
		$tabDiff = null;
		$test = false;
		$message =  "";
		$message .=  "\n================================================================= \n";
		$message .=  "Data after query in graph:\n";
		$message .=  "<".$nameGraph.">\n".$result;
		$message .=  "\n================================================================= \n";
		
		$sort = ! preg_match("/(?:ORDER|GROUP)/i",$this->URLquery);
				
		switch($mimetype){
			/*case "application/rdf+xml":
			TODO
				break;
			case "text/plain":
			TODO
				break;*/
			case "application/sparql-results+xml":
				$parserSparqlResult = new ParserSparqlResult();	
				xml_parse($parserSparqlResult->getParser(),$expected, true);		
				$tabResultDataWait = $parserSparqlResult->getResult();
				
				xml_parse($parserSparqlResult->getParser(),$result, true);		
				$tabResultDataset = $parserSparqlResult->getResult();
					
				if($sort){
					$tabResultDataWait = ParserSparqlResult::sortResult($tabResultDataWait);	
					$tabResultDataset = ParserSparqlResult::sortResult($tabResultDataset);
				}						
				$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
				//$test = true;
				break;
			case "text/tab-separated-values; charset=utf-8":
				$tabResultDataWait = ParserCSV::csv_to_array($expected,"\t");
				$tabResultDataset = ParserCSV::csv_to_array($result,"\t");		
				if($sort){
					$tabResultDataWait = ParserCSV::sortTable($tabResultDataWait);	
					$tabResultDataset = ParserCSV::sortTable($tabResultDataset);
				}						
				$tabDiff = Tools::array_diff_assoc_recursive($tabResultDataWait, $tabResultDataset);
				//$test = true;
				break;			
			case "text/csv; charset=utf-8":
				$tabResultDataWait = ParserCSV::csv_to_array($expected);
				$tabResultDataset = ParserCSV::csv_to_array($result);		
				if($sort){
					$tabResultDataWait = ParserCSV::sortTable($tabResultDataWait);	
					$tabResultDataset = ParserCSV::sortTable($tabResultDataset);
				}						
				$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
				//$test = true;
				break;						
			case  "text/turtle":		
				$tabResultDataWait = ParserTurtle::turtle_to_array($expected,$nameGraph);	
				$tabResultDataset = ParserTurtle::turtle_to_array($result,$nameGraph);		
				if($sort){
					$tabResultDataWait = ParserTurtle::sortTriples($tabResultDataWait);	
					$tabResultDataset = ParserTurtle::sortTriples($tabResultDataset);
				}						
				$tabDiff = Tools::array_diff_assoc_recursive($tabResultDataWait["triples"], $tabResultDataset["triples"]);		
				break;							
			case  "application/sparql-results+json":					
				$tabResultDataWait = json_decode($expected, true);
				$tabResultDataset = json_decode($result, true);		
				$tabDiff = Tools::array_diff_assoc_recursive_with_blanknode($tabResultDataWait, $tabResultDataset);
				//$test = true;
				break;		
			default:
				$this->AddFail("The ckeck result is not yet implemented : ".$mimetype);	
				print_r($this->_fails);
				exit();		
		}
				
		$message .=  "resultDataWait after parsing : <".$nameGraph.">\n";					
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
	
	private function importGraphInput(){   	
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$TTRIPLESTORE,$CURL,$CONFIG;	
		//echo "########################################################";
		//print_r($this->ListGraphInput);
		foreach ($this->ListGraphInput as $name=>$data){
			
			$content =$CURL->fetch_url($data["url"]);
			$this->ListGraphInput[$name]["content"]=$content ;
			
			if($this->ListGraphInput[$name]["endpoint"] == ""){				
				switch($TTRIPLESTORE){ 
					case "sesame":		
						SesameTestSuite::importData($TESTENDPOINT ,$content,$name,$data["mimetype"]);
						break;
					case "4store":		
						FourStoreTestSuite::importData($TESTENDPOINT ,$content,$name);
						break;
					case "fuseki":
						FusekiTestSuite::importData($TESTENDPOINT ,$content,$name,$data["mimetype"]);
						break;
					default:
						TestSuite::importData($TESTENDPOINT ,$data["url"],$name);
				}
			}else{
				$nameEndpoint = $this->ListGraphInput[$name]["endpoint"];
				if(! isset($CONFIG["SERVICE"]["endpoint"][$nameEndpoint])){
					$this->AddFail("the service ".$name." is not defined in the file config.");
					return;
				}
				
				$endpoint = new Endpoint($CONFIG["SERVICE"]["endpoint"][$nameEndpoint],false,$modeDebug);	
				TestSuite::importData($endpoint ,$data["url"],$name);			
			}
				
			/*echo "importGraphInput\n";
			echo $name."\n";
			echo $content;
			*/
			
			/*$output = $data["mimetype"];
			if($TTRIPLESTORE == "allegrograph" && $output == "text/turtle") //pffffff
				$output = "text/plain";
			$this->readGraphResult($output);
			print_r($this->ListGraphResult);
			echo "yo";
			exit();*/
		}
   }
   


}


