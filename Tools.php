<?php

class Tools
{
    public static function count(){
		global $modeDebug,$modeVerbose,$ENDPOINT;

		$ENDPOINT->ResetErrors();
		$q = 'SELECT (COUNT(?s) AS ?count) WHERE {GRAPH ?g {  ?s ?p ?v .}} ';
		$res = $ENDPOINT->query($q, 'row');
		$err = $ENDPOINT->getErrors();
		if ($err) {
			return -1;
		}
		return $res["count"]; //todo trycatch //test with sesame */
   }

    public static function printNbTriples(){
		$nbTriples = Tools::count();
		return ($nbTriples < 0)? "Error read the number of triples(see Debug)":$nbTriples." triples";
   }

    public static function loadData($endpoint,$urldata,$graph = "DEFAULT"){
		global $modeDebug,$modeVerbose;
		$endpoint->ResetErrors();
		if($graph == "DEFAULT"){
			$q = 'LOAD <'.$urldata.'>';
		}else{
			$q = 'LOAD <'.$urldata.'> INTO GRAPH <'.$graph.'>';
		}

		$res = $endpoint->queryUpdate($q);
		$err = $endpoint->getErrors();
		 if ($err) {
			print_r($err);
			$success = false;
			$endpoint->ResetErrors();
		 }
	}



}
