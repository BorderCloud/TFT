<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use BorderCloud\SPARQL\SparqlClient;

//require_once 'lib/sparql/Endpoint.php';
use BorderCloud\SPARQL\Mimetype;
use BorderCloud\SPARQL\ParserTurtle;
use BorderCloud\SPARQL\ParserCSV;
use BorderCloud\SPARQL\ParserSparqlResult;
use BorderCloud\SPARQL\ToolsBlankNode;

class Test extends AbstractTest  {

    const  PREFIX = <<<'EOT'
prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix : <http://www.w3.org/2009/sparql/docs/tests/data-sparql11/bind/manifest#> 
prefix rdfs:	<http://www.w3.org/2000/01/rdf-schema#> 
prefix mf:     <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> 
prefix qt:     <http://www.w3.org/2001/sw/DataAccess/tests/test-query#> 
prefix dawgt:   <http://www.w3.org/2001/sw/DataAccess/tests/test-dawg#> 
prefix ut:     <http://www.w3.org/2009/sparql/tests/test-update#> 
prefix sd:    <http://www.w3.org/ns/sparql-service-description#>

EOT;

	public $query = "";
	public $URLquery = "";

	public $ListGraphInput = null;
	public $ListGraphOutput = null;
	public $ListGraphResult = null;

	public $URLresultDataDefaultGraphType = "application/sparql-results+xml";

	public $_tabDiff = null;

	function __construct($URLquery)
	{
        parent::__construct();
		$this->URLquery = $URLquery;

		$this->ListGraphInput = array();
		$this->ListGraphOutput = array();
		$this->ListGraphResult = array();
	}

	private function readGraphResult()
	{
		global $TESTENDPOINT,$TTRIPLESTORE;

		foreach ($this->ListGraphOutput as $name=>$dataOutput) {
			$output = $dataOutput["mimetype"];
			if($TTRIPLESTORE == "allegrograph" && $output == "text/turtle") //pffffff
				$output = "text/plain";

			if($dataOutput["graphname"] == "DEFAULT"){
				$this->ListGraphResult["DEFAULT"] = $TESTENDPOINT->queryRead("CONSTRUCT { ?s ?p ?o } WHERE {?s ?p ?o}",$output);
			}else{
				$this->ListGraphResult[$dataOutput["graphname"]] = $TESTENDPOINT->queryRead("CONSTRUCT { ?s ?p ?o } WHERE {GRAPH  <".$dataOutput["graphname"]."> {?s ?p ?o}}",$output);
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
		$type = Mimetype::getMimetypeOfFilenameExtensions($extension);

		if($type === NULL){
		    $this->AddFail("DataResultExpected has an extension unknown : ".$extension." (".$url.")");
		    print_r($this->_fails);
		    exit();
		}
		return $type;
	}

	function addGraphInput($url, $name="DEFAULT", $graphname="DEFAULT",$endpoint="DEFAULT")
	{
		$this->ListGraphInput[$name]= array ("graphname"=>$graphname,"url"=>$url,"mimetype"=> $this->getType($url),"endpoint"=>$endpoint);
	}
	function addGraphOutput($url, $name="DEFAULT", $graphname="DEFAULT",$endpoint="DEFAULT")
	{
		$this->ListGraphOutput[$name]= array ("graphname"=>$graphname,"url"=>$url,"mimetype"=> $this->getType($url),"endpoint"=>$endpoint);
	}

    function readAndAddMultigraphInput($graphTest,$iriTest,$query=true)
    {
        global $ENDPOINT;
        $qGraphInput = "";
        $prefix = "";
        if($query){
            $prefix = "qt";
        }else{
            $prefix =  "ut";
        }
        $qGraphInput = Test::PREFIX.' 
		select DISTINCT  ?graphData ?graphName ?graphDataContent ?graphNameExist
		where
		 {GRAPH  <'.$graphTest.'>
				 {
					<'.$iriTest.'>  	mf:action [ '.$prefix.':graphData  ?graphData ].
					
					OPTIONAL {
					    ?graphData '.$prefix.':graph ?graphDataContent ;
									rdfs:label ?graphName .
					}				
				}
				
            BIND(BOUND(?graphName) AS ?graphNameExist)
		}';

        $rowsGraph = $ENDPOINT->query($qGraphInput,"rows");
        //print_r($rowsGraph);
        foreach ($rowsGraph["result"]["rows"] as $rowGraph){
            if ($rowGraph["graphNameExist"]) {
                $this->addGraphInput($rowGraph["graphDataContent"],$rowGraph["graphName"],$rowGraph["graphName"]);
            } else {
                $this->addGraphInput($rowGraph["graphData"],$rowGraph["graphData"],$rowGraph["graphData"]);
            }
        }
    }
    function readAndAddMultigraphOutput($graphTest,$iriTest,$query=true)
    {
        global $ENDPOINT;
        $qGraphOutput = "";
        $prefix = "";
        if($query){
            $prefix = "qt";
        }else{
            $prefix =  "ut";
        }

        $qGraphOutput = Test::PREFIX.' 
        select DISTINCT  ?graphData ?graphName ?graphDataContent ?graphNameExist
		where
		 {GRAPH  <'.$graphTest.'>
				 {

					<'.$iriTest.'>  	mf:result [ '.$prefix.':graphData  ?graphData ].
					
					OPTIONAL {
					    ?graphData '.$prefix.':graph ?graphDataContent ;
									rdfs:label ?graphName .
					}				
				}
				
            BIND(BOUND(?graphName) AS ?graphNameExist)
		}';
        $rowsGraph = $ENDPOINT->query($qGraphOutput,"rows");
        foreach ($rowsGraph["result"]["rows"] as $rowGraph){
            if ($rowGraph["graphNameExist"]) {
                $this->addGraphOutput($rowGraph["graphDataContent"],$rowGraph["graphName"],$rowGraph["graphName"]);
            } else {
                $this->addGraphOutput($rowGraph["graphData"],$rowGraph["graphData"],$rowGraph["graphData"]);
            }
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
			$this->addGraphInput($rowGraph["graphData"],$rowGraph["endpoint"],"DEFAULT",$rowGraph["endpoint"]);
		}
	}

	function doQuery($testResult=false, $name="DEFAULT")
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

		$t1 = SparqlClient::mtime();

		// Read the query
		//$this->query = $CURL->fetchUrl($this->URLquery);
        $queryContent = $this->LoadContentFile($this->URLquery)		;
        if(empty($queryContent)){
            //LoadContentFile has already insert AddFail
            return;
        }
        $this->query =$queryContent;

		$this->_time = SparqlClient::mtime() - $t1 ;
		//init Dataset for the test
		if($testResult){
		   $this->importGraphInput();
		   $this->URLresultDataDefaultGraphType = $this->ListGraphOutput["DEFAULT"]["mimetype"];
		}

		$output = $this->URLresultDataDefaultGraphType;
		if($TTRIPLESTORE == "allegrograph" && $this->URLresultDataDefaultGraphType == "text/turtle") //pffffff
		{
			$output = "text/plain";
		}

		$this->replaceServiceIRIQuery();
		$this->checkBaseQuery();
		$this->ListGraphResult[$name] = $TESTENDPOINT->queryRead($this->query , $output);

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
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$CURL,$TTRIPLESTORE,$listTestSuite;
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
		$t1 = SparqlClient::mtime();
        $queryContent = $this->LoadContentFile($this->URLquery)		;
        if(empty($queryContent)){
            //LoadContentFile has already insert AddFail
            return;
        }
        $this->query =$queryContent;

        $this->_time = SparqlClient::mtime() - $t1 ;
		//init Dataset for the test
		if($testResult){
            $this->importGraphInput();
		}

		$this->replaceExampleRDFIRIQuery();
		$this->replaceServiceIRIQuery();
		$this->checkBaseQuery();
		$TESTENDPOINT->queryUpdate($this->query);
		$errorsQuery = $TESTENDPOINT->getErrors();
		if ($errorsQuery) {
			$this->_errors = $errorsQuery;
		}else{
			if($testResult){
				$this->readGraphResult();
				$message = $this->checkResult();

				// check
				if (count($this->ListGraphOutput) === 0) {
				    // check if empty and without graph
                    $nbTriples = $this->countTriples();
                    $nbGraphs = $this->countGraphs();
                    if($nbTriples > 0 || $nbGraphs > 0) {
                        $this->AddFail(
                            "The test is failed.". $message
                            ."Nb triples found : ".$nbTriples."\n"
                            ."Nb graphs found : ".$nbGraphs."\n"
                        );
                    }
                } elseif(count($this->_tabDiff)>0) {
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
		$message .=  "Data in input : \n\n";
		foreach ($this->ListGraphInput as $name=>$data) {
			$message .= "FILE <".$data["url"]."> \n";
			$message .= "GRAPH <".$data["graphname"]."> :\n";
			$message .= $data["content"]."\n";
			$message .= "\n---------------------------\n";
		}

		return $message;
	}

	private function checkResult(){
		$message =  $this->printTestHead();

        $message .=  "Number of graphs expected : ".count($this->ListGraphOutput)." \n\n";
        foreach ($this->ListGraphOutput as $name=>$dataOutput) {
            //read data
            $expected = $this->LoadContentFile($dataOutput["url"]);
            //print_r($this->ListGraphResult);
            $message .=  "\n================================================================= \n";
            $message .=  "Expected data in graph : \n\n";
            $message .=  "FILE  <".$dataOutput["url"]."> \n";
            $message .= "GRAPH <".$dataOutput["graphname"]."> :\n";
            $message .= $expected."\n";

            $message .= $this->checkDataInGraph($dataOutput["graphname"],
                $dataOutput["mimetype"],
                $expected,
                $this->ListGraphResult[$dataOutput["graphname"]],
                $dataOutput["url"]);
            $message .=  "\n================================================================= \n";
        }
		return $message;
	}

	private function checkDataInGraph($nameGraph,$mimetype,$expected,$result,$url)
	{
		$sort = false;
		$distinct = false;
		$tabDiff = null;
		$test = false;
		$message =  "";
		$message .=  "\n---------------------------------------------------- \n";
		$message .=  "Data after query in graph:\n";
		$message .=  "GRAPH : <".$nameGraph.">\n" ;
		$message .=  "DATA :\n".$result;
		$message .=  "\n---------------------------------------------------- \n";

		//Check if the results have to respect the order
		if ( ! preg_match("/CONSTRUCT/i", $this->query)) {
		      $sort = preg_match("/(?:ORDER +BY)/i",$this->query);
		}
		//Check if the results have to respect the duplicates
		$distinct = preg_match("/(?:DISTINCT)/i",$this->query);

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
				$tabResultDataExpected = $parserSparqlResult->getResult();

				xml_parse($parserSparqlResult->getParser(),$result, true);
				$tabResultDataset = $parserSparqlResult->getResult();
                $tabDiff = ParserSparqlResult::compare($tabResultDataExpected,$tabResultDataset,$sort,$distinct);
				//$test = true;
				break;
			case "text/tab-separated-values; charset=utf-8":
				$tabResultDataExpected = ParserCSV::csvToArray($expected,"\t");
				$tabResultDataset = ParserCSV::csvToArray($result,"\t");
                $tabDiff = ParserCSV::compare($tabResultDataExpected,$tabResultDataset,$sort,$distinct);

				break;
			case "text/csv; charset=utf-8":
				$tabResultDataExpected = ParserCSV::csvToArray($expected);
				$tabResultDataset = ParserCSV::csvToArray($result);
                $tabDiff = ParserCSV::compare($tabResultDataExpected,$tabResultDataset,$sort,$distinct);
				//$test = true;
				break;
			case  "text/turtle":
				$nameGraphTemp = $nameGraph;
				if ($nameGraphTemp == "DEFAULT") //default graph is by default the url of source ttl
				   $nameGraphTemp = $url;
				$tabResultDataExpected = ParserTurtle::turtleToArray($expected,$nameGraphTemp);
				$tabResultDataset = ParserTurtle::turtleToArray($result,$nameGraphTemp);
                $tabDiff = ParserTurtle::compare($tabResultDataExpected,$tabResultDataset,$sort,$distinct);
				break;
			case  "application/sparql-results+json":
				$tabResultDataExpected = json_decode($expected, true);
				$tabResultDataset = json_decode($result, true);
				if($sort){
					  $tabDiff =  ToolsBlankNode::arrayDiffAssocRecursive($tabResultDataExpected, $tabResultDataset);
				  }else{
					  $tabDiff =  ToolsBlankNode::arrayDiffAssocUnordered($tabResultDataExpected, $tabResultDataset) ;
				  }
				//$test = true;
				break;
			default:
				$this->AddFail("The ckeck result is not yet implemented : ".$mimetype);
				print_r($this->_fails);
				exit();
		}

		$message .=  "Result expected after parsing : <".$nameGraph.">\n";
		$message .= print_r($tabResultDataExpected,true);
		$message .=  "\n================================================================= \n";
		$message .=  "Result of dataset after parsing : \n";
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

    public function countGraphs(){
        global $modeDebug,$modeVerbose,$TESTENDPOINT;

        $TESTENDPOINT->ResetErrors();
        $q = 'SELECT (COUNT(DISTINCT ?g) AS ?count) WHERE {GRAPH ?g {  ?s ?p ?v .}} ';
        $res = $TESTENDPOINT->query($q, 'row');
        $err = $TESTENDPOINT->getErrors();
        if ($err) {
            return -1;
        }
        return $res["count"];
    }

	function clearAllTriples() {
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$TTRIPLESTORE,$CONFIG;
		$q = "";
		switch($TTRIPLESTORE){
				case "virtuoso":
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

		if (preg_match("/SERVICE/i",$this->query)) {
		    //CLEAN the extern endpoint
		    foreach ($CONFIG["SERVICE"]["endpoint"] as $tempEndpoint){
			    // TODO query to identify the software...
			    $endpoint = new SparqlClient($modeDebug);
			    $endpoint->setEndpointRead($tempEndpoint);
			    $endpoint->setEndpointWrite($tempEndpoint);

			    $q = "DELETE { GRAPH ?g  { ?o ?p ?v } } WHERE  { GRAPH ?g  { ?o ?p ?v . } }";
			   //echo "t:".$tempEndpoint."\n";
			    $res = $endpoint->queryUpdate($q);
		    }
		}
    }

	private function importGraphInput(){
		global $modeDebug,$modeVerbose,$TESTENDPOINT,$TTRIPLESTORE,$CONFIG;
		foreach ($this->ListGraphInput as $name=>$data){
            $content = $this->LoadContentFile($data["url"]);
			$this->ListGraphInput[$name]["content"]=$content ;

            $testsuite = null;
			if($this->ListGraphInput[$name]["endpoint"] == "DEFAULT"){
				switch($TTRIPLESTORE){
					/*case "sesame":
                        $testsuite = new SesameTestSuite($TESTENDPOINT,,);
                        $testsuite->importData($content,$name,$data["mimetype"]);
						break;
					case "4store":
						TestSuite::importData($TESTENDPOINT ,$data["url"],$name);
						//FourStoreTestSuite::importData($TESTENDPOINT ,$content,$name);
						break;
					case "fuseki":
                        $testsuite = new FusekiTestSuite($TESTENDPOINT,"","");
                        $testsuite->importData($data["url"],$data["graphname"]);
						break;*/
					default:
                        $testsuite = new TestSuite($TESTENDPOINT,"","");
                        $testsuite->importData($data["url"],$data["graphname"]);
				}
			}else{
				$nameEndpoint = $this->ListGraphInput[$name]["endpoint"];
				if(! isset($CONFIG["SERVICE"]["endpoint"][$nameEndpoint])){
					$this->AddFail("the service ".$name." is not defined in the file config.");
					return;
				}

				$tempEndpoint = $CONFIG["SERVICE"]["endpoint"][$nameEndpoint];

				$endpoint = new SparqlClient($modeDebug);
				$endpoint->setEndpointRead($tempEndpoint);
				$endpoint->setEndpointWrite($tempEndpoint);

                $testsuite = new TestSuite($endpoint,"","");
                $testsuite->importData($data["url"],$data["graphname"]);
			}
		}
	}

	private function replaceServiceIRIQuery(){
		global $CONFIG;
	      	if (preg_match("/SERVICE/i",$this->query)) {
		    //CLEAN the extern endpoint
		    foreach ($CONFIG["SERVICE"]["endpoint"] as $nameEndpoint=>$tempEndpoint){
			    //Change the query
			$pattern = '$SERVICE +<'.$nameEndpoint.'>$i';
			$replacement = 'SERVICE <'.$tempEndpoint.'>';
			$this->query = preg_replace($pattern, $replacement, $this->query);
		    }
		}
	}
	private function replaceExampleRDFIRIQuery(){
		global $CONFIG;
	      	if (preg_match("/LOAD/i",$this->query)) {
		    //CLEAN the extern endpoint
		    foreach ($CONFIG["LOAD"]["file"] as $nameEndpoint=>$tempEndpoint){
			    //Change the query
			$pattern = '$LOAD +<'.$nameEndpoint.'>$i';
			$replacement = 'LOAD <'.$tempEndpoint.'>';
			$this->query = preg_replace($pattern, $replacement, $this->query);
		    }
		}
	}

	private function checkBaseQuery(){
	      	if (! preg_match("/BASE/i",$this->query)) {
		    $urlTab = parse_url($this->URLquery);
		    $pathParts = pathinfo($urlTab['path']);
		    $base = "BASE <".$urlTab['scheme']."://".$urlTab['host'].$pathParts['dirname']."/>";
		    $this->query = $base."\n".$this->query;
		}
	}
}
