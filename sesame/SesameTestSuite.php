<?php

class SesameToolsTestSuite  extends TestSuite {

/*
FIX for sesame :
curl -X POST dev.grid-observatory.org:8080/openrdf-sesame/repositories/rep/rdf-graphs/yourNewGraph
     -H "Content-Type:application/x-turtle" 
     -T your.ttl
	 
curl -X POST http://dev.grid-observatory.org:8080/openrdf-sesame/repositories/sparql/rdf-graphs/service?graph=http://dev.grid-observatory.org/sparql11-test-suite/ \
-H "Content-Type:application/x-turtle"  \
-T SESAME_sparql11-test-suite/sparql11-test-suite/syntax-fed/manifest.ttl  --trace-ascii /dev/stdout


	*/	
  function install(){  
		global $modeDebug,$modeVerbose;//global $output,$modeDebug,$modeVerbose,$ENDPOINT,$listFileTTL,$this->graph,$folderTests;		
		$nb = 0;		
		$success = true;	
		
		foreach ($listFileTTL as $value) {		
			$path = "SESAME_sparql11-test-suite/".$value[0];
			$dirname = dirname($path);
			if (!is_dir($dirname))
			{
				if(!mkdir($dirname, 0755, true))
				{
					die('Erreur dans la création du répertoire.');
				}
			}
			$fp = fopen($path, 'w');
					fwrite($fp,SesameTools::fixTTL(file_get_contents($value[0]),$value[0]));
					fflush($fp);
					fclose($fp);
			echo ".";
		}
		
		$nb = 0;	
		
		////http://openrdf.callimachus.net/sesame/2.7/docs/system.docbook?view
		$len = strlen($this->endpoint->getEndpointUpdate());
		$urlGraphData = substr($this->endpoint->getEndpointUpdate(), 0, $len - ($len  - strrpos ( $this->endpoint->getEndpointUpdate(), "statements")))."rdf-graphs/service?graph="; //test without len

		$header = array("Content-Type:application/x-turtle");
		foreach ($listFileTTL as $value) {
		$curl = new Curl($modeDebug);
			$path = getcwd()."/SESAME_sparql11-test-suite/".$value[0];	
			//$path = "/home/rafes/projects/gridobs3/tools/testsparql11/SESAME_sparql11-test-suite/sparql11-test-suite/entailment/manifest.ttl";
			$content = file_get_contents($path);

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
			if($code<200 || $code >= 300)
			{
				echo "\n".$path."\n";
				echo "ERROR ".$code." : cannot import files TTL in Sesame!!";
				
				$success = false;
				exit();
			}			
			echo ".";
			$nb++;
		}
		
		echo "\n";
		echo $nb." new graphs\n";
		return $success ;
   }
   
   function fixTTL($contentTTL,$path){	
		global $modeDebug,$modeVerbose;
		$resultContent = $contentTTL;
		
		$patternDetectBlankNodeWithoutSpace = '/(_:[^ ]+)\./im';
		$replacement = '$1 .';
		$resultContent = preg_replace($patternDetectBlankNodeWithoutSpace, $replacement, $resultContent);
		
		$patternDetectBlankNodeWithoutSpace = '/(_:[^ ]+),/im';
		$replacement = '$1 ,';
		$resultContent = preg_replace($patternDetectBlankNodeWithoutSpace, $replacement, $resultContent);
		
		$URI = str_replace($folderTests,$this->graph,$path);
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
	
	function importDataTest($endpoint,$graph,$content){				
		global $output,$modeDebug,$modeVerbose,$TESTENDPOINT;		
		$len = strlen($endpoint->getEndpointUpdate());
		
		$urlGraphData = substr($endpoint->getEndpointUpdate(), 0, strrpos ( $endpoint->getEndpointUpdate(), "statements"))."rdf-graphs/service?graph=";
		
		//http://www.csee.umbc.edu/courses/graduate/691/spring14/01/examples/sesame/openrdf-sesame-2.6.10/docs/system/ch08.html#d0e764
		$contentType =  "Content-Type:application/x-turtle";
		preg_match("/^.*\.([^\.]+)$/i",$graph, $matches);
		$extension = $matches[1];
			switch($extension){
				case "rdf":
					$contentType  =  "application/rdf+xml";
					break;
				case "nt":
					$contentType  =  "text/plain";
					break;
				case "ttl":
					$contentType  =  "application/x-turtle";
					break;
				default :
					
			echo "ERROR ".$extension." : Extension unknown in input!! (".$graph.")";
					exit();			
			}
		$header = array("Content-Type:".$contentType);
		$curl = new Curl($modeDebug);
		$contentFinal = SesameTools::fixTTL($content,$graph);
		
		$url = $urlGraphData.$graph ;

		 $curl->send_post_content(
			 $url, 
			 $header,
			 array(),
			 $contentFinal);
		
		$code = $curl->get_http_response_code();	
		
		if($code<200 || $code >= 300)
		{
			echo "\n".$graph."\n";
			echo "ERROR ".$code." : cannot import files TTL in sesame!!";
			echo $contentFinal;
		}	
	}
}