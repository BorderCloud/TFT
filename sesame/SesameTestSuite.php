<?php
require_once __DIR__ . '/../vendor/autoload.php';

use BorderCloud\SPARQL\Curl;
use BorderCloud\SPARQL\SparqlClient;

class SesameTestSuite extends TestSuite
{

    /*
    FIX for sesame :
    curl -X POST dev.grid-observatory.org:8080/openrdf-sesame/repositories/rep/rdf-graphs/yourNewGraph
         -H "Content-Type:application/x-turtle"
         -T your.ttl

    curl -X POST http://dev.grid-observatory.org:8080/openrdf-sesame/repositories/sparql/rdf-graphs/service?graph=http://dev.grid-observatory.org/sparql11-test-suite/ \
    -H "Content-Type:application/x-turtle"  \
    -T SESAME_sparql11-test-suite/sparql11-test-suite/syntax-fed/manifest.ttl  --trace-ascii /dev/stdout
    */

    function install()
    {
        global $modeDebug, $modeVerbose, $endpointLogin, $endpointPassword;;//global $output,$modeDebug,$modeVerbose,$ENDPOINT,$listFileTTL,$this->graph,$folderTests;
        $nb = 0;
        $success = true;
        $listFileTTL = $this->listFileTTL();

        foreach ($listFileTTL as $value) {
            $path = "SESAME_sparql11-test-suite/" . $value[0];
            $dirname = dirname($path);
            if (!is_dir($dirname)) {
                if (!mkdir($dirname, 0755, true)) {
                    die('Erreur dans la création du répertoire.');
                }
            }
            $fp = fopen($path, 'w');
            fwrite($fp, $this->fixTTL(file_get_contents($value[0]), $this->graph));
            fflush($fp);
            fclose($fp);
            echo ".";
        }

        $nb = 0;

        ////http://openrdf.callimachus.net/sesame/2.7/docs/system.docbook?view
        $len = strlen($this->endpoint->getEndpointWrite());
        $urlGraphData = substr($this->endpoint->getEndpointWrite(), 0, $len - ($len - strrpos($this->endpoint->getEndpointWrite(), "statements"))) . "rdf-graphs/service?graph="; //test without len

        $header = array("Content-Type:application/x-turtle");
        foreach ($listFileTTL as $value) {
            $curl = new Curl($modeDebug);

            if ($endpointLogin != "" && $endpointPassword != "") {
                $curl->set_credentials($endpointLogin, $endpointPassword);
            }

            $path = getcwd() . "/SESAME_sparql11-test-suite/" . $value[0];
            //$path = "/home/rafes/projects/gridobs3/tools/testsparql11/SESAME_sparql11-test-suite/sparql11-test-suite/entailment/manifest.ttl";
            $content = file_get_contents($path);

            $graph = "";
            if (is_string($value[1]) && preg_match("/manifest[^\.]*\.ttl$/i", $value[1])) {
                $graph = $this->graph;
            } else {
                $graph = str_replace($this->folder, $this->graph, $value[0]);
            }

            $url = $urlGraphData . $this->graph;
            $curl->sendPostContent(
                $url,
                $header,
                array(),
                $content);

            $code = $curl->getHttpResponseCode();
            if ($code < 200 || $code >= 300) {
                echo "\n" . $path . "\n";
                echo "ERROR " . $code . " : cannot import files TTL in Sesame!!";

                $success = false;
                exit();
            }
            echo ".";
            $nb++;
        }

        echo "\n";
        echo $nb . " new graphs\n";
        return $success;
    }

    function fixTTL($contentTTL, $path)
    {
        global $modeDebug, $modeVerbose;
        $resultContent = $contentTTL;

        $patternDetectBlankNodeWithoutSpace = '/(_:[^ ]+)\./im';
        $replacement = '$1 .';
        $resultContent = preg_replace($patternDetectBlankNodeWithoutSpace, $replacement, $resultContent);

        $patternDetectBlankNodeWithoutSpace = '/(_:[^ ]+),/im';
        $replacement = '$1 ,';
        $resultContent = preg_replace($patternDetectBlankNodeWithoutSpace, $replacement, $resultContent);

        $URI = str_replace($folderTests, $this->graph, $path);
        $patternDetectNotUri = '/<>/im';
        $replacementNotUri = '<' . $URI . '>';
        $resultContent = preg_replace($patternDetectNotUri, $replacementNotUri, $resultContent);

        $len = strlen($URI);
        $prefix = substr($URI, 0, $len - ($len - strrpos($URI, "/")));
        $patternDetectNotUri = '/<([^:<>]+)>/im';
        $replacementNotUri = '<' . $prefix . '/$1>';
        $resultContent = preg_replace($patternDetectNotUri, $replacementNotUri, $resultContent);

        return $resultContent;
    }

    function importData($endpoint, $content, $graph = "DEFAULT")
    {
        global $modeDebug, $modeVerbose, $TESTENDPOINT;
        $len = strlen($TESTENDPOINT->getEndpointWrite());
        //$url = substr($TESTENDPOINT->getEndpointUpdate(), 0, strrpos( $TESTENDPOINT->getEndpointUpdate(),
        // "update/"))."data/";
        $urlGraphData = substr($endpoint->getEndpointWrite(), 0, strrpos($endpoint->getEndpointWrite(), "statements")) . "rdf-graphs/service?";

        if ($graph == "DEFAULT") {
            $postdata = array();
        } else {
            $postdata = array("graph" => $graph);
        }
        $headerdata = array("Content-Type: application/x-www-form-urlencoded");
        $curl = new Curl($modeDebug);
        $contentFinal = $this->fixTTL($content, $graph);

        $curl->sendPostContent(
            $urlGraphData,
            $headerdata,
            $postdata,
            $contentFinal);

        $code = $curl->getHttpResponseCode();

        if ($code < 200 || $code >= 300) {
            echo "ERROR " . $code . " : cannot import files TTL in 4store!!";
            exit();
        }
    }

    /*function importData($endpoint,$content,$graph = "DEFAULT",$contentType){
        global $output,$modeDebug,$modeVerbose,$TESTENDPOINT,$endpointLogin,$endpointPassword;
        $len = strlen($endpoint->getEndpointUpdate());
        $urlGraphData = substr($endpoint->getEndpointUpdate(), 0, strrpos ( $endpoint->getEndpointUpdate(), "statements"))."rdf-graphs/service?";
        if($graph == "DEFAULT"){
            $urlGraphData .= "graph=";
        }else{
            $urlGraphData .= "default";
        }

        $header = array("Content-Type:".$contentType);

        $curl = new Curl($modeDebug);
        if($endpointLogin != "" && $endpointPassword != ""){
           $curl->set_credentials($endpointLogin,$endpointPassword);
        }

        $contentFinal = $this->fixTTL($content,$graph);

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
    }*/
}
