 <?php

class FusekiTestSuite extends TestSuite {

/*
FIX for fuseki :

curl -v -H "Content-type: application/x-www-form-urlencoded" \
-H "Accept: application/sparql-results+xml" -X POST \
--data-urlencode \
'update=prefix hc: <http://www.hebrideanconnections.com/hebridean.owl#> 
insert data { hc:633 hc:associatedWith hc:633 }' \
http://dev.grid-observatory.org:3030/tests/update

curl -v -H "Accept: application/sparql-results+xml" \
--data-urlencode \
'query=select * where { GRAPH ?g  { ?s ?p ?o }} LIMIT 10' \
http://dev.grid-observatory.org:3030/tests/query

curl -v -X POST http://dev.grid-observatory.org:3030/tests/data?graph=locale:manifest.ttl \
-H "Content-Type:application/x-turtle" \
-T sparql11-test-suite/syntax-update-1/manifest.ttl

****************************************** BUG
curl -v -H "Content-type: application/x-www-form-urlencoded" \
-H "Accept: application/sparql-results+xml" -X POST \
--data-urlencode \
'update=
prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#>
INSERT DATA {  GRAPH <locale:s> {
<locale:syntax-service-01.rq> rdf:resource  <http://example.com/sparql11-test-suite/syntax-fed/syntax-service-01.rq> .
<http://example.com/sparql11-test-suite/syntax-fed/syntax-service-01.rq> rdfs:member <http://example.com/sparql11-test-suite/syntax-fed> .
}}' \
http://localhost:3030/tests/update

curl -v -H "Accept: application/sparql-results+xml" \
--data-urlencode \
'query=prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#>
select * where { 
GRAPH ?g  { ?queryTest  rdf:resource ?queryTestHref.
OPTIONAL {
?queryTestHref rdfs:member ?queryTestBase. 
}}}' \
http://localhost:3030/tests/query

curl -v -H "Accept: application/sparql-results+xml" \
--data-urlencode \
'query=prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix rdfs:    <http://www.w3.org/2000/01/rdf-schema#>
select * where { GRAPH ?g  { 
 ?queryTestHref rdfs:member ?queryTestBase.
OPTIONAL {?queryTest  rdf:resource ?queryTestHref. 
}}}' \
http://localhost:3030/tests/query

**********************************************************

curl -v -X POST http://dev.grid-observatory.org:3030/tests/data?graph=locale:my2 \
-H "Content-Type:application/x-turtle" \
-T sparql11-test-suite/bind/manifest.ttl

curl -v -H "Accept: application/sparql-results+xml" \
--data-urlencode \
'query= select * where { GRAPH <locale:my2> { 
 ?o ?p ?v.
}}' \
http://localhost:3030/tests/query



curl -v -H "Accept: application/sparql-results+xml" \
--data-urlencode \
'query=prefix ex:	<http://www.example.org/schema#>
prefix in:	<http://www.example.org/instance#>

select  ?x ?p where {
graph ?g {
{select * where {?x ?p ?y}}
}
}' \
http://localhost:3030/tests/query

(~nauroy/jena/jena-fuseki-1.0.0)
clear :
ps aux | grep fuseki
kill X

cd ~nauroy/jena/jena-fuseki-1.0.0
nohup ./fuseki-server --update --mem /tests &

	*/	
   function install(){  
		global $modeDebug,$modeVerbose;//,$this->endpoint,$listFileTTL,$this->graph,$folderTests;		
		$nb = 0;		
		
		$listFileTTL = $this->listFileTTL();
		
		$nb = 0;
		$len = strlen($this->endpoint->getEndpointUpdate());
		$urlGraphData = substr($this->endpoint->getEndpointUpdate(), 0, $len - ($len  - strrpos ( $this->endpoint->getEndpointUpdate(), "update")))."data?graph=";
		//$urlGraphManifest = substr($this->endpoint->getEndpointUpdate(), 0, $len - ($len  - strrpos ( $this->endpoint->getEndpointUpdate(), "update")))."data?graph=".$this->graph;
		
		$header = array("Content-Type:application/x-turtle");
		foreach ($listFileTTL as $value) {
		$curl = new Curl($modeDebug);
			$path = $value[0];	

			$content = FusekiTestSuite::fixTTL(file_get_contents($path),$path);

			$graph = "";
			if (preg_match("/manifest[^\.]*\.ttl$/i", $value[1])) {				
				$graph = $this->graph;
			} else {
				$graph = str_replace($this->folder,$this->graph,$value[0]);
			}
			$url = $urlGraphData.$graph ;

			 $curl->send_post_content(
				 $url, 
				 $header,
				 array(),
				 $content);
			
			$code = $curl->get_http_response_code();	
		
			if($code<200 && $code >= 300)
			{
				echo "\n".$path."\n";
				echo "ERROR ".$code." : cannot import files TTL in Sesame!!";
				exit();
			}				
			
			echo ".";
			$nb++;
		}
		
		echo "\n";
		echo $nb." File imported \n";
   }
   
   	function importData($endpoint,$content,$graph = "DEFAULT",$contentType){	
		global $modeDebug,$modeVerbose;		
		$len = strlen($endpoint->getEndpointUpdate());
		$urlGraphData = substr($endpoint->getEndpointUpdate(), 0,  strrpos ( $endpoint->getEndpointUpdate(), "update"))."data?";
		if($graph == "DEFAULT"){
			$urlGraphData .= "graph=";
		}else{
			$urlGraphData .= "default";
		}
		//$header = array("Content-Type:application/x-turtle");
		$header = array("Content-Type:".$contentType);
		$curl = new Curl($modeDebug);
		$contentFinal = FusekiTestSuite::fixTTL($content,$graph);

		$url = $urlGraphData.$graph ;

		 $curl->send_post_content(
			 $url, 
			 $header,
			 array(),
			 $contentFinal);
		
		$code = $curl->get_http_response_code();	
		
		if($code<200 || $code >= 300)
		{
			echo "\n".$path."\n";
			echo "ERROR ".$code." : cannot import files TTL in fuseki!!";
		}	
	}
   
   function fixTTL($contentTTL,$path){
		global $modeDebug,$modeVerbose;
		$resultContent = $contentTTL;
		
		$URI = str_replace($this->folder,$this->graph,$path);
		$patternDetectNotUri = '/<>/im';
		$replacementNotUri = '<'.$URI.'>';
		$resultContent = preg_replace($patternDetectNotUri, $replacementNotUri, $resultContent);
		
		$len = strlen($URI);
		$prefix = substr($URI, 0, $len - ($len  - strrpos ( $URI , "/")));
		$patternDetectNotUri = '/<([^:<>]+)>/im';
		$replacementNotUri = '<'.$prefix .'/$1>';
		$resultContent = preg_replace($patternDetectNotUri, $replacementNotUri, $resultContent);
		
		return $resultContent;
	}
}
