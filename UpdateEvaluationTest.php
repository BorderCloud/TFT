<?php

class UpdateEvaluationTest {
    static function countApprovedTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;

		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(DISTINCT ?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:UpdateEvaluationTest ;
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
		TESTS : UpdateEvaluationTest\n";
		$Report = new TestsReport("UpdateEvaluationTest",$TAGTESTS.'-UpdateEvaluationTest-junit.xml');

		$q = Test::PREFIX.' 
SELECT DISTINCT ?testiri ?name ?queryTest 
?dataInput ?dataOutput
?dataInputExist ?graphDataInputExist ?serviceDataInputExist
?dataOutputExist ?graphDataOutputExist
WHERE {
    GRAPH <'.$GRAPHTESTS.'> {
      #  VALUES ?testiri {<http://www.w3.org/2009/sparql/docs/tests/data-sparql11/construct/manifest#constructwhere04>}
        ?manifest   a mf:Manifest ;
                    mf:entries  ?collection .
					?collection rdf:rest*/rdf:first  ?testiri .
					
		?testiri 	a mf:UpdateEvaluationTest ;
					mf:name	?name ;
					dawgt:approval dawgt:Approved ;
					mf:action [ ut:request ?queryTest ]
		OPTIONAL {
			?testiri mf:action [ ut:data ?dataInput	]							
		}				
		OPTIONAL {
			?testiri mf:action [ ut:graphData ?graphDataInput	]							
		}	
		OPTIONAL {
			?testiri mf:action [ ut:serviceData ?serviceDataInput ]							
		}
		OPTIONAL {
			?testiri mf:result [ ut:data ?dataOutput ]					
		}				
		OPTIONAL {
			?testiri mf:result [ ut:graphData ?graphDataOutput ]		
		}
        BIND(BOUND(?dataInput) AS ?dataInputExist)
        BIND(BOUND(?graphDataInput) AS ?graphDataInputExist)
        BIND(BOUND(?serviceDataInput) AS ?serviceDataInputExist)	
    
        BIND(BOUND(?dataOutput) AS ?dataOutputExist)
        BIND(BOUND(?graphDataOutput) AS ?graphDataOutputExist)
    }
}
ORDER BY ?testiri
 ';
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q,"rows");
		//print_r($rows);
		//exit();
		$err = $ENDPOINT->getErrors();
		$iriTest = $GRAPH_RESULTS_EARL."/UpdateEvaluationTest/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/UpdateEvaluationTest/selectAssert";
		$labelAssert = "Select the UpdateEvaluationTest";
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
		$nbApprovedTests = UpdateEvaluationTest::countApprovedTests();

		$iriTest = $GRAPH_RESULTS_EARL."/UpdateEvaluationTest/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/UpdateEvaluationTest/CountTestsAssert";
		$labelAssert = "Compare the nb of valid tests with the nb of tests in the dataset.";
		if($nbTest !=  $nbApprovedTests ){
// 			echo "F";
			echo "NB of tests (".$nbTest."/".$nbApprovedTests ." in theory) is incorrect.\n";
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
			/*print_r($row);
			echo trim($row["queryTest"])."|".trim($row["graphInput"])."|".trim($row["graphOutput"])."|".trim($row["ChangeDefaultGraph"])."|".trim($row["ChangeMultiGraph"])."\n";
			if($row["ChangeDefaultGraph"])
				echo "ok";
			exit();*/

			$test = new Test(trim($row["queryTest"]));

            $graphName = "DEFAULT";
            if($row["dataInputExist"]){
                $test->addGraphInput(trim($row["dataInput"]),$graphName,$graphName);
            }
            if($row["graphDataInputExist"]){
                $test->readAndAddMultigraphInput($GRAPHTESTS,$iriTest,false); //todo check error http://www.w3.org/2009/sparql/docs/tests/data-sparql11/exists/exists03.rq
            }
            if($row["serviceDataInputExist"]){
                throw new Exception("not tested");
                $test->readAndAddService($GRAPHTESTS,$iriTest);
            }

            $graphName = "DEFAULT";
            if($row["dataOutputExist"]){
                $test->addGraphOutput(trim($row["dataOutput"]),$graphName,$graphName);
            }
            if($row["graphDataOutputExist"]){
                $test->readAndAddMultigraphOutput($GRAPHTESTS,$iriTest,false); //todo check error http://www.w3.org/2009/sparql/docs/tests/data-sparql11/exists/exists03.rq
            }
			/*echo "ListGraphInput";
			echo $iriTest;
			echo "ListGraphInput";
			print_r($test->ListGraphInput);
			echo "ListGraphOutput";
			print_r($test->ListGraphOutput);*/
			//continue;
			$test->doUpdate(true);
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
					$Report->addTestCasePassed($iriTest,$iriAssertProtocol,$labelAssertProtocol);

					if(count($fail) != 0){
						echo "F";
						$Report->addTestCaseFailure($iriTest,$iriAssertResponse,$labelAssertResponse,
						print_r($fail,true));
					}else{
					    echo ".";
						$Report->addTestCasePassed($iriTest,$iriAssertResponse,$labelAssertResponse,
                            $test->GetTime());
					}

			}

		}
	}
}
