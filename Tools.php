<?php

class Tools 
{ 
	function count(){   
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
   
   function printNbTriples(){
		$nbTriples = Tools::count();
		return ($nbTriples < 0)? "Error read the number of triples(see Debug)":$nbTriples." triples";
   }
   
    function loadData($endpoint,$graph,$urldata){
		global $modeDebug,$modeVerbose;	
		$endpoint->ResetErrors();
		$q = 'LOAD <'.$urldata.'> INTO GRAPH <'.$graph.'>';
		$res = $endpoint->queryUpdate($q);
		$err = $endpoint->getErrors();
		 if ($err) {
			print_r($err);
			$success = false;
			$endpoint->ResetErrors();
		 }
	}
	
   	function array_diff_assoc_recursive($array1, $array2) {
		$difference=array();
		foreach($array1 as $key => $value) {
			if( is_array($value) ) {
				if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
					$difference[$key] = $value;
				} else {
					$new_diff = Tools::array_diff_assoc_recursive($value, $array2[$key]);
					if( !empty($new_diff) )
						$difference[$key] = $new_diff;
				}
			} else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
				$difference[$key] = $value;
			}
		}
		return $difference;
	}
	
   	function array_diff_assoc_recursive_with_blanknode($array1, $array2) {
		$difference=array();
		$node = array();
		foreach($array1 as $key => $value) {
			if( is_array($value) ) {
				if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
					$difference[$key] = $value;
				} else {
					$new_diff = Tools::array_diff_assoc_recursive_with_blanknode($value, $array2[$key]);
					if( !empty($new_diff) )
						$difference[$key] = $new_diff;
				}
			} else if( !array_key_exists($key,$array2) ) {
				$difference[$key] = $value;			
			} else if( $array2[$key] !== $value && floatval($array2[$key]) !== floatval($value)) {	
				if(isset($value[0]) && $value[0] == "_"){//(array_key_exists("type",$array1) && $array1["type"] == "bnode"){
					if(!array_key_exists($value,$node )){
						$node[$value] = $array2[$key];
					}else if($node[$value] !==  $array2[$key] ){								
						$difference[$key] = $value;
					}
				}else{
					$difference[$key] = $value;
				}
			}
		}
		return $difference;
	}
}