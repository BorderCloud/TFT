 <?php

class NegativeUpdateSyntaxTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:NegativeUpdateSyntaxTest11 ;
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
		TESTS : NegativeUpdateSyntaxTest\n";
		$Report = new TestsReport("NegativeUpdateSyntaxTest",$TAGTESTS.'-NegativeUpdateSyntaxTest-junit.xml');

		$q = Test::PREFIX.' 
		 select DISTINCT ?testiri ?name ?queryTest where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?testiri a 				mf:NegativeUpdateSyntaxTest11 ;
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
		$iriTest = $GRAPH_RESULTS_EARL."/NegativeUpdateSyntaxTest11/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/NegativeUpdateSyntaxTest11/selectAssert";
		$labelAssert = "Select the NegativeUpdateSyntaxTest11";
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
		$nbApprovedTests = NegativeUpdateSyntaxTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/NegativeUpdateSyntaxTest11/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/NegativeUpdateSyntaxTest11/CountTestsAssert";
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
			
			$iriAssertSyntax =$row["testiri"]."/"."Syntax";			
			$labelAssertSyntax = trim($row["name"])." : Test the syntax.";
			
			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}

			$test = new Test(trim($row["queryTest"]));
			$test->doUpdate(false);
			$err = $test->GetErrors();
			$fail = $test->GetFails();
			if (count($err) != 0 || count($fail) != 0  ) {	
					echo ".";//echo "\n".$nameTestQueryPassed." PASSED";					
					$Report->addTestCasePassed($iriTest,$iriAssertSyntax,$labelAssertSyntax);
			}else{
					echo "F";//echo "\n".$nameTestQueryPassed." ERROR";					
					$Report->addTestCaseFailure($iriTest,$iriAssertSyntax,$labelAssertSyntax,									
						"<![CDATA[".print_r($err,true).print_r($fail,true)."]]>");
			}
		}
	}
}

