<?php

class QueryEvaluationTest { 
 
	function countAllTests(){ 	
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS ;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS.'> { ?s a mf:QueryEvaluationTest .}} '; 
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
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:QueryEvaluationTest ;
							 dawgt:approval dawgt:Approved .}} '; 
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"]; 
   }

	function doAllTests(){ 	
		global $modeDebug,$modeVerbose,$ENDPOINT,$CURL,$GRAPHTESTS,$GRAPH_RESULTS_EARL;
		 //////////////////////////////////////////////////////////////////////
		echo "
--------------------------------------------------------------------
TESTS : QueryEvaluationTest";// ( ".QueryEvaluationTest::countApprovedTests()." Approved, ".QueryEvaluationTest::countSkipTests()." Skipped, ".QueryEvaluationTest::countAllTests()." Total\n";
		$Report = new TestsReport("QueryEvaluationTest",'sparql11-QueryEvaluationTest-junit.xml');

		$q = Test::PREFIX.' 
select DISTINCT ?testiri ?name ?queryTest ?graphInput ?graphOutput where
{GRAPH <'.$GRAPHTESTS .'>
	 {
		?testiri a 				mf:QueryEvaluationTest ;
				 mf:name    	?name ;
				 dawgt:approval dawgt:Approved ;
				 mf:action
				   [ qt:query  	?queryTest ;
					 qt:data    ?graphInput ] ;
				mf:result  ?graphOutput .
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
		$nbApprovedTests = QueryEvaluationTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/QueryEvaluationTest/CountTestsAssert";
		$labelAssert = "Compare the nb of valid tests with the nb of tests in the dataset.";
		if($nbTest !=  $nbApprovedTests ){
			echo "F";
		  $Report->addTestCaseFailure($iriTest,$iriAssert,$labelAssert,
					"NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n"	
					);
		}else{		
			echo ".";
			$Report->addTestCasePassed($iriTest,$iriAssert,$labelAssert);
		}
		
		foreach ($rows["result"]["rows"] as $row){
			$iriTest = trim($row["testiri"]);
			
			$iriAssertProtocol =$row["testiri"]."/"."Protocol";			
			$labelAssertProtocol = trim($row["name"])." : Test the protocol.";
			$iriAssertResponse =$row["testiri"]."/"."Response";			
			$labelAssertResponse = trim($row["name"])." : Test the response.";
			
			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}

			$test = new Test(trim($row["queryTest"]),trim($row["graphInput"]),trim($row["graphOutput"]));
			$test->doQuery(true);
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
