<?php

class NegativeSyntaxTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:NegativeSyntaxTest11 ;
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
--------------------------------------------------------------------
TESTS : NegativeSyntaxTest\n";
		$Report = new TestsReport("NegativeSyntaxTest",$TAGTESTS.'-NegativeSyntaxTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?queryTest where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {				 
					?manifest a	      	mf:Manifest ;
						  mf:entries  	?collection .
						  ?collection 	rdf:rest*/rdf:first  ?testiri .
						  
					?testiri a 		mf:NegativeSyntaxTest11 ;
						 mf:name    	?name ;
						 dawgt:approval dawgt:Approved ;
						 mf:action 	?queryTest  .
				 }
		}
		 ORDER BY ?testiri
		';
		 
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();
		$iriTest = $GRAPH_RESULTS_EARL."/NegativeSyntaxTest11/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/NegativeSyntaxTest11/selectAssert";
		$labelAssert = "Select the NegativeSyntaxTest11";
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

		//Check the nb of tests
		$nbApprovedTests = NegativeSyntaxTest::countApprovedTests();
		
		$iriTest = $GRAPH_RESULTS_EARL."/NegativeSyntaxTest11/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/NegativeSyntaxTest11/CountTestsAssert";
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
		
		foreach ($rows["result"]["rows"] as $row){
			$iriTest = trim($row["testiri"]);
			
			$iriAssertSyntax =$row["testiri"]."/"."Syntax";			
			$labelAssertSyntax = trim($row["name"])." : Test the syntax.";
			
			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}
			
			$test = new Test(trim($row["queryTest"]));
			$test->doQuery();				
			$err = $test->GetErrors();
			$fail = $test->GetFails();
			
			if (count($err) > 0 || count($fail) > 0) {	
					    echo ".";
					$Report->addTestCasePassed($iriTest,$iriAssertSyntax,$labelAssertSyntax);
			}else{
					echo "F";//"\n".$nameTestQueryPassed." PASSED";	
					$error = "ERROR : Server cannot see this wrong query.\n Query :\n".$test->query;
					$error .= "Response of server :\n";
					$error .= print_r($test->ListGraphResult,true);
					$Report->addTestCaseFailure($iriTest,$iriAssertSyntax,$labelAssertSyntax,		
						$error);
					//echo $error;
			}
		}
		echo "\n";
	}
}
