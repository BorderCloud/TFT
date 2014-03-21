<?php

class UpdateEvaluationTest { 
	function countApprovedTests(){   
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;
		
		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:UpdateEvaluationTest ;
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
		TESTS : UpdateEvaluationTest\n";
		$Report = new TestsReport("UpdateEvaluationTest",'sparql11-UpdateEvaluationTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?queryTest  ?graphInput ?graphOutput where
		 {GRAPH  <'.$GRAPHTESTS.'>
				 {
					?testiri a 				mf:UpdateEvaluationTest ;
							 mf:name    	?name ;
							 dawgt:approval dawgt:Approved ;
							mf:action [ 
										ut:request ?queryTest; 
										ut:data  ?graphInput    ] ;
							mf:result [ ut:data  ?graphOutput  ] .
				 }
		}
		 ORDER BY ?testiri
		 ';
	/*	 
		 :add01 rdf:type mf:UpdateEvaluationTest ;
    mf:name "ADD 1" ;
    rdfs:comment "Add the default graph to an existing graph" ;
    dawgt:approval dawgt:Approved;
    dawgt:approvedBy <http://www.w3.org/2009/sparql/meeting/2012-02-07#resolution_3> ;
    mf:action [ ut:request <add-01.ru> ;
                ut:data <add-default.ttl> ;
                ut:graphData [ ut:graph <add-01-pre.ttl> ;
                               rdfs:label "http://example.org/g1" ]
              ] ;
    mf:result [ ut:data <add-default.ttl> ;
                ut:graphData [ ut:graph <add-01-post.ttl> ;
                               rdfs:label "http://example.org/g1" ]
              ] .
		  
		 */
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
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

			$test = new Test(trim($row["queryTest"]),trim($row["graphInput"]),trim($row["graphOutput"]));
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
						$test->queryTime);						
					}

			}
		}
	}
}
