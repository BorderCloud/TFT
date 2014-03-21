<?php

class ProtocolTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:ProtocolTest ;
							 dawgt:approval dawgt:Approved .}} '; 
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"]; 
   }
	function doAllTests(){ 	
		global $modeDebug,$modeVerbose,$ENDPOINT,$CURL,$GRAPHTESTS,$GRAPH_RESULTS_EARL,$TAGTESTS;;
		 //////////////////////////////////////////////////////////////////////
		echo "
		TESTS : ProtocolTest\n";
		$Report = new TestsReport("ProtocolTest",$TAGTESTS.'-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?approval where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?testiri a 				mf:ProtocolTest ;
							 mf:name    	?name ;
							 dawgt:approval ?approval .
				 }
		}
		 ORDER BY ?testiri
		 ';
		 
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();
		
		$iriTest = $GRAPH_RESULTS_EARL."/ProtocolTest/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/ProtocolTest/selectAssert";
		$labelAssert = "Select the ProtocolTest";
		 if ($err) {
			echo "F => Cannot ".$labelAssert;		 
			$Report->addTestCaseFailure($iriTest,$iriAssert,$labelAssert,print_r($err,true));
			return;
		 }else{			
			echo ".";
			$Report->addTestCasePassed($iriTest,$iriAssert,$labelAssert);
		 }
	
	
		//print_r($rows);
		$nbTest = count($rows["result"]["rows"]);
		echo "Nb tests : ".$nbTest."\n";
		//exit();
		$nbApprovedTests = ProtocolTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/ProtocolTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/ProtocolTest/CountTestsAssert";
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
		
		//exit();
		foreach ($rows["result"]["rows"] as $row){
			$iriTest = trim($row["testiri"]);
			
			$iriAssertProtocol =$row["testiri"]."/"."Protocol";			
			$labelAssertProtocol = trim($row["name"])." : Test the protocol.";
			$iriAssertResponse =$row["testiri"]."/"."Response";			
			$labelAssertResponse = trim($row["name"])." : Test the response.";
			
			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}

			$uriNotClassified = "http://www.w3.org/2001/sw/DataAccess/tests/test-dawg#NotClassified";
			if(trim($row["approval"]) == $uriNotClassified){
				echo "S";//echo "\n".$nameTestQueryDataPassed." SKIP";
				$Report->addTestCaseSkipped($iriTest,$iriAssertResponse,$labelAssertResponse,
				"Test: Not yet implemented"
				);
				continue;
			}
		}
	}
}

 

