<?php

class PositiveSyntaxTest {
    static function countApprovedTests(){
		global $modeDebug,$modeVerbose,$ENDPOINT,$GRAPHTESTS;

		$ENDPOINT->ResetErrors();
		$q = Test::PREFIX.'
		SELECT (COUNT(?s) AS ?count) WHERE {
			GRAPH <'.$GRAPHTESTS .'> { ?s a mf:PositiveSyntaxTest11 ;
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
--------------------------------------------------------------------
TESTS : PositiveSyntaxTest\n";
		$Report = new TestsReport("PositiveSyntaxTest",$TAGTESTS.'-PositiveSyntaxTest-junit.xml');

		$q = Test::PREFIX.' 

		 select DISTINCT ?testiri ?name ?queryTest where
		 {GRAPH <'.$GRAPHTESTS.'>
				 {
					?manifest a 		mf:Manifest ;
						  mf:entries  	?collection .
						  ?collection 	rdf:rest*/rdf:first  ?testiri .
			  
					?testiri a  		mf:PositiveSyntaxTest11 ;
						 mf:name    	?name ;
						 dawgt:approval dawgt:Approved ;
						 mf:action 	?queryTest  .

				 }
		}
		 ORDER BY ?testiri
		';
		 /*

		 				  OPTIONAL{
					 ?queryTest  rdf:resource ?queryTestHref.
					  ?queryTestHref rdfs:member ?queryTestBase.
					}
					*/
		//echo $q;
		$ENDPOINT->ResetErrors();
		$rows = $ENDPOINT->query($q, 'rows');
		$err = $ENDPOINT->getErrors();
		$iriTest = $GRAPH_RESULTS_EARL."/PositiveSyntaxTest11/select";
		$iriAssert = $GRAPH_RESULTS_EARL."/PositiveSyntaxTest11/selectAssert";
		$labelAssert = "Select the PositiveSyntaxTest11";
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
		//Check the nb of tests
		$nbApprovedTests = PositiveSyntaxTest::countApprovedTests();

		$iriTest = $GRAPH_RESULTS_EARL."/PositiveSyntaxTest11/CountTests";
		$iriAssert = $GRAPH_RESULTS_EARL."/PositiveSyntaxTest11/CountTestsAssert";
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
			$nameTestQueryPassed = trim($row["name"])." TestProtocol";
			$nameTestQueryDataPassed = trim($row["name"])." TestData";

			if($modeVerbose){
				echo "\n".$iriTest.":".trim($row["name"]).":" ;
				$class = trim(str_replace(array("http://www.w3.org/2009/sparql/docs/tests/","/","#"),array("PositiveSyntaxTest.",".","."),$row["testiri"]));
				echo "\n".$class.":".$nameTestQueryDataPassed." Tests :";
			}

			$test = new Test(trim($row["queryTest"]));
			$test->doQuery();
			$err = $test->GetErrors();
			$fail = $test->GetFails();
			if (count($err) == 0 && count($fail) == 0) {
				echo ".";
					$Report->addTestCasePassed($iriTest,$iriAssertSyntax,$labelAssertSyntax);
			}else{
				echo "F";
					$Report->addTestCaseFailure($iriTest,$iriAssertSyntax,$labelAssertSyntax,
				print_r($fail,true)."\n".print_r($err,true));
			}
		}
		echo "\n";
	}
}

