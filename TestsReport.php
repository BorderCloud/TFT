<?php
class TestsReport { 

	private $_exportJunit = false;
	private $_exportEARL = false;
	
	//For junit
	private $_fileOpen = null;
	private $_fileName = "";
	
	//counter
	private $_nbTests = 0;
	private $_nbErrors = 0;
	private $_nbFailures = 0;
	private $_nbSkip = 0;
	
	private $_nameTestSuite;

	function __construct($nameTestSuite,$fileName) 
	{		
		global $OUTPUT_FOLDER_RESULTS_JUNIT,$GRAPH_RESULTS_EARL;
		
		$this->_nameTestSuite = $nameTestSuite;
		
		if($OUTPUT_FOLDER_RESULTS_JUNIT != ""){		
			$this->_fileName = $OUTPUT_FOLDER_RESULTS_JUNIT."/".$fileName;
			//echo $this->_fileName;
			//exit();
			$this->_fileOpen = fopen($this->_fileName, 'w');	
			$this->_exportJunit = true;			
		}
		
		if($GRAPH_RESULTS_EARL != ""){
			$this->_exportEARL = true;			
		}
		
		if($this->_exportJunit){
			$this->beginReportJunit();
		}
		
		if($this->_exportEARL){
			$this->beginReportEARL();
		}
    }
	
	function __destruct() 
	{	
		if($this->_exportJunit){
			$this->endReportJunit();
		}
		
		if($this->_exportEARL){
			//TODO ??
		}
    }
	
	function addTestCasePassed($iriTest,$iriAssert,$labelAssert,$time=0,$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		
		if($this->_exportJunit){
			$this->addTestCasePassedJunit($iriAssert,$labelAssert,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		
		if($this->_exportEARL){
		    $this->addTestCasePassedEARL($iriTest,$iriAssert,$labelAssert,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		
		//Counters
		$this->_nbTests++;
	}
	
	function addTestCaseSkipped($iriTest,$iriAssert,$labelAssert,$text)
	{
		if($this->_exportJunit){
			$this->addTestCaseSkippedJunit($iriAssert,$labelAssert,$text);
		}
		
		if($this->_exportEARL){
			$this->addTestCaseSkippedEARL($iriTest,$iriAssert,$labelAssert,$text);
		}
		
		//Counters
		$this->_nbTests++;
		$this->_nbSkip++;
	}
	
	function addTestCaseFailure($iriTest,$iriAssert,$labelAssert,$text,$time="",$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		if($this->_exportJunit){
			$this->addTestCaseFailureJunit($iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		
		if($this->_exportEARL){
			$this->addTestCaseFailureEARL($iriTest,$iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		
		//Counters
		$this->_nbTests++;
		$this->_nbFailures++;
	}
	
    function addTestCaseError($iriTest,$iriAssert,$labelAssert,$text,$time="",$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		if($this->_exportJunit){
			$this->addTestCaseErrorJunit($iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		
		if($this->_exportEARL){
			$this->addTestCaseErrorEARL($iriTest,$iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr);
		}
		//Counters
		$this->_nbTests++;
		$this->_nbErrors++;
	}	
	
	//////////////////////////////////////////////////// EARL
	
	function beginReportEARL()
	{
		global $ENDPOINT,$GRAPH_RESULTS_EARL,
		$SOFTWARE_NAME_EARL,$SOFTWARE_DESCRIBE_EARL,$SOFTWARE_DESCRIBE_TAG_EARL,
		$TFT_NAME_EARL,$TFT_DESCRIBE_EARL,$TFT_DESCRIBE_TAG_EARL,$DATETEST;
		
		$ENDPOINT->ResetErrors();
	
	//git config --get remote.origin.url
		$date = $DATETEST;//date("c", time());
		//prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
		$q = '
prefix sd: <http://www.w3.org/ns/sparql-service-description#> 
prefix git: <http://www.w3.org/ns/git#> 
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix xsd:  <http://www.w3.org/2001/XMLSchema#>
INSERT DATA {  
 GRAPH <'.$GRAPH_RESULTS_EARL .'> {

	<'.$GRAPH_RESULTS_EARL .'/Service> a sd:Service ;
		sd:supportedLanguage sd:SPARQL11Query ;
		sd:server <'.$GRAPH_RESULTS_EARL .'/Software> ;
		sd:testedBy <'.$GRAPH_RESULTS_EARL .'/Tester> ;
		sd:testedDate "'.$date.'"^^xsd:dateTime  .
		
		<'.$GRAPH_RESULTS_EARL .'/Software> a git:Project ;
								git:name  "'.addslashes($SOFTWARE_NAME_EARL).'" ;
								git:describeTag "'.$SOFTWARE_DESCRIBE_TAG_EARL.'" 
';

		if($SOFTWARE_DESCRIBE_EARL !="")
			$q .= '	;
					git:describe "'.$SOFTWARE_DESCRIBE_EARL.'" 
			';

$q .= '	.	
		<'.$GRAPH_RESULTS_EARL .'/Tester> a git:Project ;
								git:name  "'.addslashes($TFT_NAME_EARL).'" ;
								git:describeTag "'.$TFT_DESCRIBE_TAG_EARL.'" 
';
		if($TFT_DESCRIBE_EARL !="")
			$q .= '	;
					git:describe "'.$TFT_DESCRIBE_EARL.'" 
			';

$q .= '	.
}
}
';
		$rows = $ENDPOINT->queryUpdate($q);
		$err = $ENDPOINT->getErrors(); 
		if ($err) {
			echo "Error";
			print_r($err);
		}
	}
	
	function addTestCasePassedEARL($iriTest,$iriAssert,$labelAssert,$time,$assertions,$status,$arraySystemOut,$arraySystemErr)
	{
		    global $ENDPOINT,$GRAPH_RESULTS_EARL,$DATETEST;
			$ENDPOINT->ResetErrors();
			
		$date = $DATETEST;//date("c", time());
			$iriAssertResultId = $iriAssert."/".$date;
			//prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
			$q = '
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix xsd:  <http://www.w3.org/2001/XMLSchema#>
prefix earl: <http://www.w3.org/ns/earl#>
INSERT DATA {  
	 GRAPH <'.$GRAPH_RESULTS_EARL .'> {
		<'.$iriAssert.'> a earl:Assertion ;
						earl:test <'.$iriTest.'> ;
						rdf:label "'.addslashes($labelAssert).'";
						earl:result <'.$iriAssertResultId.'>.
		<'.$iriAssertResultId.'>	a earl:TestResult ;
						earl:date "'.$date.'"^^xsd:dateTime ;
						earl:duration '.$time.' ;
						earl:outcome earl:passed . 
	}
}
';

			$rows = $ENDPOINT->queryUpdate($q);
			$err = $ENDPOINT->getErrors(); 
			if ($err) {
				echo "Error save the result of test.(".$iriTest.",".$iriAssert.",".$labelAssert.")";
				print_r($err);
			}
			
			/*			
C'est beau .... pt etre un jour
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX xsd:  <http://www.w3.org/2001/XMLSchema#>
prefix earl: <http://www.w3.org/ns/earl#>
                        INSERT DATA {
                             GRAPH <http://dev.grid-observatory.org/testsVirtuoso> {
                                        <http://dev.grid-observatory.org/testsVirtuoso/dfd> a earl:Assertion ;
                                        earl:test <http://dev.grid-observatory.org/testsVirtuoso/dfdfdf>  ;
                                        rdf:label "sdfsdfds sf sfsd fs";
										BIND(UUID() AS ?iriResult)
                                        earl:result ?iriResult.
										?iriResult a earl:TestResult ;
										earl:date NOW() ;
										earl:duration "PT0.0S"^^xsd:duration ;
										earl:outcome earl:pass .
                                }
                        }
						*/
	}
	
	function addTestCaseSkippedEARL($iriTest,$iriAssert,$labelAssert,$text)
	{
		    global $ENDPOINT,$GRAPH_RESULTS_EARL,$DATETEST;
			$ENDPOINT->ResetErrors();
			
		$date = $DATETEST;//date("c", time());
			$iriAssertResultId = $iriAssert."/".$date;
			//prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
			$q = '
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix xsd:  <http://www.w3.org/2001/XMLSchema#>
prefix earl: <http://www.w3.org/ns/earl#>
INSERT DATA {  
	 GRAPH <'.$GRAPH_RESULTS_EARL .'> {
		<'.$iriAssert.'> a earl:Assertion ;
						earl:test <'.$iriTest.'> ;
						rdf:label "'.addslashes($labelAssert).'";
						earl:result <'.$iriAssertResultId.'>.
		<'.$iriAssertResultId.'>	a earl:TestResult ;
						earl:date "'.$date.'"^^xsd:dateTime ;
						earl:info """'.addslashes( htmlspecialchars(trim($text))).'""";
						earl:outcome earl:untested . 
	}
}
';

			$rows = $ENDPOINT->queryUpdate($q);
			$err = $ENDPOINT->getErrors(); 
			if ($err) {
				echo "Error save the result of test.(".$iriTest.",".$iriAssert.",".$labelAssert.")";
				print_r($err);
			}
			
	}
	
	private function addTestCaseFailureEARL($iriTest,$iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr)
	{
			global $ENDPOINT,$GRAPH_RESULTS_EARL,$DATETEST;
			$ENDPOINT->ResetErrors();
		$date = $DATETEST;//date("c", time());
			$iriAssertResultId = $iriAssert."/".$date;
			//prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
			$q = '
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix xsd:  <http://www.w3.org/2001/XMLSchema#>
prefix earl: <http://www.w3.org/ns/earl#>
INSERT DATA {  
	 GRAPH <'.$GRAPH_RESULTS_EARL .'> {
		<'.$iriAssert.'> a earl:Assertion ;
						earl:test <'.$iriTest.'> ;
						rdf:label "'.addslashes($labelAssert).'";
						earl:result <'.$iriAssertResultId.'>.
		<'.$iriAssertResultId.'>	a earl:TestResult ;
						earl:date "'.$date.'"^^xsd:dateTime ;
						earl:info """'.addslashes( htmlspecialchars(trim($text))).'""";
						earl:outcome earl:failed . 
	}
}
';

			$rows = $ENDPOINT->queryUpdate($q);
			$err = $ENDPOINT->getErrors(); 
			if ($err) {
				echo "Error save the result of test.(".$iriTest.",".$iriAssert.",".$labelAssert.")";
				print_r($err);
			}
	}
		
	private function addTestCaseErrorEARL($iriTest,$iriAssert,$labelAssert,$text,$time,$assertions,$status,$arraySystemOut,$arraySystemErr)
	{
			global $ENDPOINT,$GRAPH_RESULTS_EARL,$DATETEST;
			$ENDPOINT->ResetErrors();
		
			$date = $DATETEST;//date("c", time());
			$iriAssertResultId = $iriAssert."/".$date;
			//prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
			$q = '
prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
prefix xsd:  <http://www.w3.org/2001/XMLSchema#>
prefix earl: <http://www.w3.org/ns/earl#>
INSERT DATA {  
	 GRAPH <'.$GRAPH_RESULTS_EARL .'> {
		<'.$iriAssert.'> a earl:Assertion ;
						earl:test <'.$iriTest.'> ;
						rdf:label "'.addslashes($labelAssert).'";
						earl:result <'.$iriAssertResultId.'>.
		<'.$iriAssertResultId.'>	a earl:TestResult ;
						earl:date "'.$date.'"^^xsd:dateTime ;
						earl:info """'.addslashes(htmlspecialchars(trim($text))).'""";
						earl:outcome earl:error . 
	}
}
';

			$rows = $ENDPOINT->queryUpdate($q);
			$err = $ENDPOINT->getErrors(); 
			if ($err) {
				echo "Error save the result of test.(".$iriTest.",".$iriAssert.",".$labelAssert.")";
				print_r($err);
			}
	}
	//////////////////////////////////////////////////// JUNIT
	
	private function iriToClass($iri){
	//todo...clean
	////$class = trim(str_replace(array("http://www.w3.org/2009/sparql/docs/tests/","/","#"),array("QueryEvaluationTest.",".","."),$row["testiri"]));
		return trim(str_replace(array("http://www.w3.org/2009/sparql/docs/tests/","/","#"),array("QueryEvaluationTest.",".","."),$iri));
	}
	private function beginReportJunit() 
	{
			$headFile = '<?xml version="1.0" encoding="UTF-8"?> 
<testsuite name="'.$this->_nameTestSuite.'" tests="NBTESTS" errors="NBERRORS" failures="NBFAILURES" skip="NBSKIP">'."\n";

			fwrite($this->_fileOpen, $headFile);
			 
			fflush($this->_fileOpen);
	}
	
	private function endReportJunit() 
	{	
		if(is_resource($this->_fileOpen)){
			fwrite($this->_fileOpen,'</testsuite>');
			fflush($this->_fileOpen);
			fclose($this->_fileOpen);
		}
		
		// write nb tests, etc.
		$reading = fopen($this->_fileName, 'r');
		$writing = fopen($this->_fileName.'.tmp', 'w');

		$replaced = false;
		while (!feof($reading)) {
		  $line = fgets($reading);
		  if (stristr($line,'NBTESTS')) {
			$tag = array('NBTESTS', 'NBERRORS','NBFAILURES','NBSKIP');
			$counters   = array($this->_nbTests,$this->_nbErrors,$this->_nbFailures,$this->_nbSkip);
			$line  = str_replace($tag, $counters, $line);
			$replaced = true;
		  }
		  fputs($writing, $line);
		}
		fclose($reading); fclose($writing);
		// might as well not overwrite the file if we didn't replace anything
		if ($replaced) 
		{
		  rename($this->_fileName.'.tmp', $this->_fileName);
		} else {
		  unlink($this->_fileName.'.tmp');
		}
    }
	
	private function addTestCasePassedJunit($iri,$name,$time="",$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		$classname = $this->iriToClass($iri);
		$tagclassname = str_replace ('"','',$classname);
		$tagname = str_replace ('"','',trim($name));
		fwrite($this->_fileOpen,"\t".'<testcase classname="'.$tagclassname.'" name="'.$tagname.'" time="'.$time.'" >'."\n");	
		
		fwrite($this->_fileOpen,"\t".'</testcase>'."\n");
	}
	
	private function addTestCaseSkippedJunit($iri,$name,$text)
	{
		$classname = $this->iriToClass($iri);
		$tagclassname = str_replace ('"','',$classname);
		$tagname = str_replace ('"','',trim($name));
		fwrite($this->_fileOpen,"\t".'<testcase classname="'.$tagclassname.'" name="'.$tagname.'">'."\n");		
		fwrite($this->_fileOpen,"\t".'<skipped><![CDATA['.$text.']]></skipped>'."\n");
		fwrite($this->_fileOpen,"\t".'</testcase>'."\n");
	}
	
	private function addTestCaseFailureJunit($iri,$name,$text,$time="",$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		$classname = $this->iriToClass($iri);
		$tagclassname = str_replace ('"','',$classname);
		$tagname = str_replace ('"','',trim($name));
		fwrite($this->_fileOpen,"\t".'<testcase classname="'.$tagclassname.'" name="'.$tagname.'">'."\n");	
		fwrite($this->_fileOpen,"\t".'<failure><![CDATA['.$text.']]></failure>'."\n");
		fwrite($this->_fileOpen,"\t".'</testcase>'."\n");
	}
	
    private function addTestCaseErrorJunit($iri,$name,$text,$time="",$assertions="",$status="",$arraySystemOut=null,$arraySystemErr=null)
	{
		$classname = $this->iriToClass($iri);
		$tagclassname = str_replace ('"','',$classname);
		$tagname = str_replace ('"','',trim($name));
		fwrite($this->_fileOpen,"\t".'<testcase classname="'.$tagclassname.'" name="'.$tagname.'">'."\n");		
		fwrite($this->_fileOpen,"\t".'<error><![CDATA['.$text.']]></error>'."\n");
		fwrite($this->_fileOpen,"\t".'</testcase>'."\n");		
	}
	
}


/////////////////////// RDF EARL
//http://www.openrdf.org/earl/sesame-sparql11-earl.ttl
//doc http://www.w3.org/TR/EARL10-Schema/#outcomevalue
/*
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix doap: <http://usefulinc.com/ns/doap#> .
@prefix earl: <http://www.w3.org/ns/earl#> .
@prefix dcterms: <http://purl.org/dc/terms/> .

_:node17pcvrdq0x1 a doap:Project ;
	doap:name "OpenRDF Sesame" ;
	doap:release [
		a doap:Version ;
		doap:name "Sesame 2.7.0" ;
			doap:created "2013-04-30"^^xsd:date .
	]	;
	doap:homepage <http://www.openrdf.org/> .

<earl:Software rdf:about="#cooltool">
  <doap:name xml:lang="en">Cool Tool</doap:name>
  <doap:description xml:lang="en">My favorite tool!</doap:description>
  <doap:created>2011-04-27</doap:created>
  <doap:homepage rdf:resource="http://example.org/tools/cool/"/>
  <doap:release>
    <doap:revision>1.0.3</doap:revision>
  </doap:release>
</earl:Software>

_:node17pcvrdq0x2 

<$GRAPH_RESULTS_EARL> a earl:Software ;
	<http://purl.org/dc/elements/1.1/title> "OpenRDF SPARQL 1.1 compliance tests" .

<trucunique...> a earl:Assertion ;
	earl:test <http://www.w3.org/2009/sparql/docs/tests/data-sparql11/aggregates/manifest#agg01> ;
	earl:result [ 
					a earl:TestResult ;
					earl:outcome earl:pass .
				]

_:node17pcvrdq0x6 a earl:Assertion ;
	earl:assertedBy _:node17pcvrdq0x3 ;
	earl:mode earl:automatic ;
	earl:subject _:node17pcvrdq0x1 ;
	earl:test <http://www.w3.org/2009/sparql/docs/tests/data-sparql11/aggregates/manifest#agg02> ;
	earl:result _:node17pcvrdq0x7 .

_:node17pcvrdq0x7 a earl:TestResult ;
	earl:outcome earl:pass .
	
	
*/
/////////////////////// xsd junit
/*
https://svn.jenkins-ci.org/trunk/hudson/dtkit/dtkit-format/dtkit-junit-model/src/main/resources/com/thalesgroup/dtkit/junit/model/xsd/junit-4.xsd

<?xml version="1.0" encoding="UTF-8" ?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">

    <xs:element name="failure">
        <xs:complexType mixed="true">
            <xs:attribute name="type" type="xs:string" use="optional"/>
            <xs:attribute name="message" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="error">
        <xs:complexType mixed="true">
            <xs:attribute name="type" type="xs:string" use="optional"/>
            <xs:attribute name="message" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="properties">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="property" maxOccurs="unbounded"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>

    <xs:element name="property">
        <xs:complexType>
            <xs:attribute name="name" type="xs:string" use="required"/>
            <xs:attribute name="value" type="xs:string" use="required"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="skipped" type="xs:string"/>
    <xs:element name="system-err" type="xs:string"/>
    <xs:element name="system-out" type="xs:string"/>

    <xs:element name="testcase">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="skipped" minOccurs="0" maxOccurs="1"/>
                <xs:element ref="error" minOccurs="0" maxOccurs="unbounded"/>
                <xs:element ref="failure" minOccurs="0" maxOccurs="unbounded"/>
                <xs:element ref="system-out" minOccurs="0" maxOccurs="unbounded"/>
                <xs:element ref="system-err" minOccurs="0" maxOccurs="unbounded"/>
            </xs:sequence>
            <xs:attribute name="name" type="xs:string" use="required"/>
            <xs:attribute name="assertions" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="classname" type="xs:string" use="optional"/>
            <xs:attribute name="status" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="testsuite">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="properties" minOccurs="0" maxOccurs="1"/>
                <xs:element ref="testcase" minOccurs="0" maxOccurs="unbounded"/>
                <xs:element ref="system-out" minOccurs="0" maxOccurs="1"/>
                <xs:element ref="system-err" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
            <xs:attribute name="name" type="xs:string" use="required"/>
            <xs:attribute name="tests" type="xs:string" use="required"/>
            <xs:attribute name="failures" type="xs:string" use="optional"/>
            <xs:attribute name="errors" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="disabled" type="xs:string" use="optional"/>
            <xs:attribute name="skipped" type="xs:string" use="optional"/>
            <xs:attribute name="timestamp" type="xs:string" use="optional"/>
            <xs:attribute name="hostname" type="xs:string" use="optional"/>
            <xs:attribute name="id" type="xs:string" use="optional"/>
            <xs:attribute name="package" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="testsuites">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="testsuite" minOccurs="0" maxOccurs="unbounded"/>
            </xs:sequence>
            <xs:attribute name="name" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="tests" type="xs:string" use="optional"/>
            <xs:attribute name="failures" type="xs:string" use="optional"/>
            <xs:attribute name="disabled" type="xs:string" use="optional"/>
            <xs:attribute name="errors" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>


</xs:schema>

*/