<?php

class ServiceDescriptionTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:ServiceDescriptionTest ;
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
		TESTS : ServiceDescriptionTest\n";
		$Report = new TestsReport("ServiceDescriptionTest",'sparql11-ServiceDescriptionTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?approval ?approvedBy  where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?testiri a 				mf:ServiceDescriptionTest ;
							 mf:name    	?name ;
							 dawgt:approval ?approval ;
							 dawgt:approvedBy ?approvedBy.
				 }
		}
		 ORDER BY ?testiri
		 ';
		 
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();
		$iriTest = $GRAPH_RESULTS_EARL."/ServiceDescriptionTest/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/ServiceDescriptionTest/selectAssert";
		$labelAssert = "Select the ServiceDescriptionTest";
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
		$nbApprovedTests = ServiceDescriptionTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/ServiceDescriptionTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/ServiceDescriptionTest/CountTestsAssert";
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

            echo "S";//echo "\n".$nameTestQueryDataPassed." SKIP";
			$Report->addTestCaseSkipped($iriTest,$iriAssertResponse,$labelAssertResponse,
				"Test: Not yet implemented.\n".trim($row["approval"])."\n".trim($row["approvedBy"])
				);
			
		}
		
	}
}
 
