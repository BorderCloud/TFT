 <?php

 require_once __DIR__ . '/../vendor/autoload.php';

 use BorderCloud\SPARQL\Curl;

class FusekiTestSuite extends TestSuite
{
    /*
    Replace LOAD for fuseki :
    curl -v -X POST http://172.17.0.2:8080/test/data?graph=https://bordercloud.github.io/TFT-tests/sparql11-test-suite/syntax-update-2/manifest.ttl \
    -H "Content-Type:application/x-turtle" \
    -T tests/TFT-tests/sparql11-test-suite/syntax-update-2/manifest.ttl
    */
    function install()
    {
        global $modeDebug, $modeVerbose;//,$this->endpoint,$listFileTTL,$this->graph,$folderTests;
        $nb = 0;

        $listFileTTL = $this->listFileTTL();

        $nb = 0;
        $len = strlen($this->endpoint->getEndpointWrite());
        $urlGraphData = substr($this->endpoint->getEndpointWrite(), 0, $len - ($len - strrpos($this->endpoint->getEndpointWrite(), "update"))) . "data?graph=";
        //$urlGraphManifest = substr($this->endpoint->getEndpointUpdate(), 0, $len - ($len  - strrpos ( $this->endpoint->getEndpointUpdate(), "update")))."data?graph=".$this->graph;

        $header = array("Content-Type:text/turtle");
        foreach ($listFileTTL as $value) {
            $curl = new Curl($modeDebug);
            $path = $value[0];

            $content = FusekiTestSuite::fixTTL(file_get_contents($path), $path);

            $graph = "";
            if (is_string($value[1]) && preg_match("/manifest[^\.]*\.ttl$/i", $value[1])) {
                $graph = $this->graph;
            } else {
                $graph = str_replace($this->folder, $this->graph, $value[0]);
            }
            $url = $urlGraphData . $graph;

            $curl->sendPostContent(
                $url,
                $header,
                array(),
                $content);

            $code = $curl->getHttpResponseCode();

            if ($code < 200 && $code >= 300) {
                echo "\n" . $path . "\n";
                echo "ERROR " . $code . " : cannot import files TTL in Sesame!!";
                exit();
            }

            echo ".";
            $nb++;
        }

        echo "\n";
        echo $nb . " File imported \n";
    }

    function importData($content, $graph = "DEFAULT")
    {
        global $modeDebug, $modeVerbose, $TESTENDPOINT;
        $len = strlen($TESTENDPOINT->getEndpointWrite());
        $urlGraphData = substr($TESTENDPOINT->getEndpointWrite(), 0, strrpos($TESTENDPOINT->getEndpointWrite(), "update")) . "data?";

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
            echo "ERROR " . $code . " : cannot import files TTL in Fusiki !!";
            exit();
        }
    }

    function fixTTL($contentTTL, $path)
    {
        global $modeDebug, $modeVerbose;
        $resultContent = $contentTTL;

        $URI = str_replace($this->folder, $this->graph, $path);
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
}
