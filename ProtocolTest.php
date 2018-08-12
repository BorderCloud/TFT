<?php

class ProtocolTest {
    static function countApprovedTests(){
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
    static function doAllTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$CURL,$GRAPHTESTS,$GRAPH_RESULTS_EARL,$TAGTESTS;;
		 //////////////////////////////////////////////////////////////////////
		echo "
		TESTS : ProtocolTest\n";
		$Report = new TestsReport("ProtocolTest",$TAGTESTS.'-ProtocolTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?jmeterPlanTest where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?manifest a 		mf:Manifest ;
						  mf:entries  	?collection .
						  ?collection 	rdf:rest*/rdf:first  ?testiri .
						  
					?testiri a 		mf:ProtocolTest ;
						 mf:name    	?name ;
						 mf:action 	?jmeterPlanTest  ;
						 dawgt:approval dawgt:Approved .
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


        //Check the nb of tests
        //print_r($rows);
		$nbTest = count($rows["result"]["rows"]);
		echo "Nb tests : ".$nbTest."\n";
		//exit();
		$nbApprovedTests = ProtocolTest::countApprovedTests();

		$iriTest = $GRAPH_RESULTS_EARL."/ProtocolTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/ProtocolTest/CountTestsAssert";
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

			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
			}

			$test = new TestJmeter($row["jmeterPlanTest"]);

//			$uriNotClassified = "http://www.w3.org/2001/sw/DataAccess/tests/test-dawg#NotClassified";
//			if(trim($row["approval"]) == $uriNotClassified){
//				echo "S";//echo "\n".$nameTestQueryDataPassed." SKIP";
//				$Report->addTestCaseSkipped($iriTest,$iriAssertResponse,$labelAssertResponse,
//				"Test: Not yet implemented"
//				);
//				continue;
//			}

            $test->doTestPlan();
            $err = $test->GetErrors();
            $fail = $test->GetFails();

            if (!(count($err) > 0 || count($fail) > 0)) {
                echo ".";
                $Report->addTestCasePassed($iriTest,$iriAssertProtocol,$labelAssertProtocol);
            }else{
                echo "F";//"\n".$nameTestQueryPassed." PASSED";

                $Report->addTestCaseFailure($iriTest,$iriAssertProtocol,$labelAssertProtocol,
                    print_r($fail,true). print_r($err,true));
                //echo $error;
            }
		}
	}
}



