<?php

class PositiveUpdateSyntaxTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:PositiveUpdateSyntaxTest11 ;
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
		TESTS : PositiveUpdateSyntaxTest\n";
		$Report = new TestsReport("PositiveUpdateSyntaxTest",$TAGTESTS.'-PositiveUpdateSyntaxTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?queryTest where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?manifest a           mf:Manifest ;
						  mf:entries  ?collection .
						  ?collection rdf:rest*/rdf:first  ?testiri .
			  
					?testiri a 	 	mf:PositiveUpdateSyntaxTest11 ;
						 mf:name    	?name ;
						 dawgt:approval dawgt:Approved ;
						 mf:action 	?queryTest .
				 }
		}
		 ORDER BY ?testiri
		';
				 
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();
		$iriTest = $GRAPH_RESULTS_EARL."/PositiveUpdateSyntaxTest11/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/PositiveUpdateSyntaxTest11/selectAssert";
		$labelAssert = "Select the PositiveUpdateSyntaxTest11";
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
		$nbApprovedTests = PositiveUpdateSyntaxTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/PositiveUpdateSyntaxTest11/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/PositiveUpdateSyntaxTest11/CountTestsAssert";
		$labelAssert = "Compare the nb of valid tests with the nb of tests in the dataset.";
		if($nbTest !=  $nbApprovedTests ){
			echo "NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n";

// 			echo "F";
// 			$Report->addTestCaseFailure($iriTest,$iriAssert,$labelAssert,
// 					"NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n"	
// 					);
		}else{		
// 			echo ".";
// 			$Report->addTestCasePassed($iriTest,$iriAssert,$labelAssert);
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

			$test = new Test(trim($row["queryTest"]));
			$test->doUpdate(false);
			$err = $test->GetErrors();
			$fail = $test->GetFails();
			if (count($err) != 0 || count($fail) != 0  ) {	
					echo "F";//echo "\n".$nameTestQueryPassed." ERROR";
					$Report->addTestCaseFailure($iriTest,$iriAssertResponse,$labelAssertResponse,
						print_r($err,true)."\n".print_r($fail,true));		
			}else{
					echo ".";
					$Report->addTestCasePassed($iriTest,$iriAssertResponse,$labelAssertResponse, 					
						$test->queryTime);
			}

		}
	}
}
