<?php

class QueryEvaluationTest {

	function countAllTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS ;

		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS.'> { ?s a mf:QueryEvaluationTest ;
							 dawgt:approval dawgt:Approved.}} ';
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"];
	}
	function countSkipTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;

		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:QueryEvaluationTest ;
							 dawgt:approval dawgt:NotClassified .}} ';
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"];
	}
	function countApprovedTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;

		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(DISTINCT ?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:QueryEvaluationTest ;
							 dawgt:approval dawgt:Approved .}} ';
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"];
   }

	static function doAllTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$CURL,$GRAPHTESTS,$GRAPH_RESULTS_EARL,$TAGTESTS;
		 //////////////////////////////////////////////////////////////////////
		echo "
--------------------------------------------------------------------
TESTS : QueryEvaluationTest";// ( ".QueryEvaluationTest::countApprovedTests()." Approved, ".QueryEvaluationTest::countSkipTests()." Skipped, ".QueryEvaluationTest::countAllTests()." Total\n";
		$Report = new TestsReport("QueryEvaluationTest",$TAGTESTS.'-QueryEvaluationTest-junit.xml');
		$q = Test::PREFIX.' 
SELECT DISTINCT ?testiri ?name ?queryTest  
		?ChangeDefaultGraph ?ChangeMultiGraph ?ChangeServiceGraph
		?graphInputDefault ?graphOutput ?graphInputDefaultName
WHERE
{GRAPH <'.$GRAPHTESTS .'>
	 {
                ?manifest a 		mf:Manifest ;
                          mf:entries  	?collection .
			  ?collection 	rdf:rest*/rdf:first  ?testiri .
			  
		?testiri a 			mf:QueryEvaluationTest ;
				 mf:name    	?name ;
				 dawgt:approval dawgt:Approved ;
				 mf:action   [ 
								qt:query  	?queryTest 
							] ;
				mf:result  ?graphOutput .		
		OPTIONAL{
			?testiri mf:action [ qt:data    ?graphInputDefault	]							
			}				
		OPTIONAL{
			?testiri mf:action [ qt:graphData    ?graphInputDefaultName	]							
			}		
		OPTIONAL{
			?testiri mf:action [ qt:graphData    [ qt:graph   ?graphInputGraph ]	]							
			}		
		OPTIONAL{
			?testiri mf:action [ qt:serviceData    ?serviceInputGraph	]							
			}	
		BIND(BOUND(?graphInputDefault) AS ?ChangeDefaultGraph)
		BIND(BOUND(?graphInputGraph) AS ?ChangeMultiGraph)		
		BIND(BOUND(?serviceInputGraph) AS ?ChangeServiceGraph)		
	}
}
ORDER BY ?testiri
';

		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();

		$iriTest = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/selectAssert";
		$labelAssert = "Select the QueryEvaluationTest";
		 if ($err) {
			echo "F => Cannot ".$labelAssert;
			$Report->addTestCaseFailure($iriTest,$iriAssert,$labelAssert,print_r($err,true));
			return;
		 }else{
			echo ".";
			$Report->addTestCasePassed($iriTest,$iriAssert,$labelAssert);
		 }

		//Check the nb of tests
		//print_r($rows);
		$nbTest = count($rows["result"]["rows"]);
		echo "Nb tests : ".$nbTest."\n";
		//exit();
		$nbApprovedTests = QueryEvaluationTest::countApprovedTests();

		$iriTest = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/CountTestsAssert";
		$labelAssert = "Compare the nb of valid tests with the nb of tests in the dataset.";
		if($nbTest !=  $nbApprovedTests ){
//			echo "F";
			echo "NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n";
// 		        $Report->addTestCaseFailure($iriTest,$iriAssert,$labelAssert,
// 					"NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n TODO//220 but there are tests with several names..."
// 					);
		}else{
//			echo ".";
//			$Report->addTestCasePassed($iriTest,$iriAssert,$labelAssert);
		}

		foreach ($rows["result"]["rows"] as $row){
			$iriTest = trim($row["testiri"]);

			/*
			echo $iriTest;
			//exit();
			if(! preg_match("/exists03/i", $iriTest))
				continue;

			if(! preg_match("/service/i", $iriTest))
				continue;
			*/

			$iriAssertProtocol =$row["testiri"]."/"."Protocol";
			$labelAssertProtocol = trim($row["name"])." : Test the protocol.";
			$iriAssertResponse =$row["testiri"]."/"."Response";
			$labelAssertResponse = trim($row["name"])." : Test the response.";

			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}

			$test = new Test(trim($row["queryTest"]));

            $GraphName = "DEFAULT";
			if($row["ChangeDefaultGraph"]){
			    if (!$row["ChangeMultiGraph"] && array_key_exists('graphInputDefaultName', $row)) {
				   $GraphName = trim($row["graphInputDefaultName"]);
				}
				$test->addGraphInput(trim($row["graphInputDefault"]),"DEFAULT",$GraphName);
				$test->addGraphOutput(trim($row["graphOutput"]),"DEFAULT",$GraphName);
			}
			if($row["ChangeMultiGraph"]){
				$test->readAndAddMultigraph($GRAPHTESTS,$iriTest); //todo check error http://www.w3.org/2009/sparql/docs/tests/data-sparql11/exists/exists03.rq
			}

			if($row["ChangeServiceGraph"]){
				$test->readAndAddService($GRAPHTESTS,$iriTest);
			}

			/*echo "ListGraphInput";
			echo $iriTest;
			echo "ListGraphInput";
			print_r($test->ListGraphInput);
			echo "ListGraphOutput";
			print_r($test->ListGraphOutput);
			//exit();*/

			//echo "\nmf:name    	\"".$row["name"]."\" ;\n";

			$test->doQuery(true,$GraphName);
			$err = $test->GetErrors();
			$fail = $test->GetFails();
			if (count($err) != 0) {
                echo "E";//echo "\n".$nameTestQueryPassed." ERROR";
                $Report->addTestCaseError($iriTest,$iriAssertProtocol,$labelAssertProtocol,
                    print_r($err,true));
                echo "S";//echo "\n".$nameTestQueryDataPassed." SKIP";
                $Report->addTestCaseSkipped($iriTest,$iriAssertResponse,$labelAssertResponse,
                "Cannot read result because test:" . $iriAssertProtocol . " is failed."
                );
			}else{
                echo ".";//echo "\n".$nameTestQueryPassed." PASSED";
                $Report->addTestCasePassed($iriTest,$iriAssertProtocol,$labelAssertProtocol);

                if(count($fail) != 0){
                    echo "F";
                    $Report->addTestCaseFailure($iriTest,$iriAssertResponse,$labelAssertResponse,
                        print_r($fail,true));
                }else{
                    echo ".";
                    $Report->addTestCasePassed($iriTest,$iriAssertResponse,$labelAssertResponse,
                        $test->queryTime);
                }
			}
		}
		echo "\n";
	}
}
