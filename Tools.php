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
   
    function loadData($endpoint,$urldata,$graph = "DEFAULT"){
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
        
    public static function array_diff_assoc_unordered( $rs1,  $rs2) {
         $difference=array();
          //A/ Check the variables lists in the header are the same.
         if(! isset($rs1['result']['variables']) && ! isset($rs2['result']['variables'])){
              return $difference; //return true ;
          }elseif (! isset($rs1['result']['variables']) || ! isset($rs2['result']['variables']) ) {
              $difference[1]=$rs1['result']['variables'];
              $difference[2]=$rs2['result']['variables'];
              return $difference; //return false ;
          }

          $difference=array_diff($rs1,$rs2);
          if (count($difference) != 0) {
              return $difference; //return false ;
          }

          //B/ Check the result set have the same number of rows.
          if(count($rs1['result']['rows']) != count($rs2['result']['rows'])) {
              $difference[1]="Nb rows :".count($rs1['result']['rows']);
              $difference[2]="Nb rows :".count($rs2['result']['rows']);
              return $difference; //return false ;
          }

          //C/ Pick a row from the test results, scan the expected results
          //   to find a row with same variable/value bindings, and remove
          //   from the expected results. If all test rows, match then
          //   (because of B) the result sets have the same rows.
          //   
          //return equivalent(convert(rs1), convert(rs2), new BNodeIso(NodeUtils.sameValue)) ;
          $clone1 = $rs1['result']['rows'];
          $clone2 = $rs2['result']['rows'];
          //echo "AVANT";
          //     print_r($clone1);
           //    print_r($clone2);
          foreach ($rs1['result']['rows'] as $key1=>&$value1) {
              $tmpclone2 = $clone2;
               foreach ($tmpclone2 as $key2=>&$value2) {
                   
               //print_r($value1);
               //print_r($value2);
                  if(count(array_diff_assoc($value1,$value2)) == 0 && 
                      count(array_diff_assoc($value2,$value1)) == 0 ){
                       unset($clone1[$key1]);
                       unset($clone2[$key2]);
                  }
               }
               //print_r($clone1);
               //print_r($clone2);
          }

          if(count($clone1) != 0 || 
             count($clone2) != 0 ){
              $difference[1]=$clone1;
              $difference[2]=$clone2;
              return $difference; //return false ;
          }

          return $difference;
    }
}