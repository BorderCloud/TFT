<?php

class TestJmeter extends AbstractTest
{
    public $URLJmeterTestPlan = "";

    function __construct($URLJmeterTestPlan)
    {
        parent::__construct();
        $this->URLJmeterTestPlan = $URLJmeterTestPlan;
    }

    function doTestPlan()
    {
        global $modeDebug, $modeVerbose, $TESTENDPOINT, $CURL, $TTRIPLESTORE, $listTestSuite, $TESTENDPOINT_HOSTNAME, $TESTENDPOINT_PORT, $TESTENDPOINT_PATH;
        $message = "";
        $test = false;

        $fileJmeterTestPlan = "";
        foreach ($listTestSuite as $URL => $folder) {
            if (0 === strpos($this->URLJmeterTestPlan, $URL)) {
                $fileJmeterTestPlan = str_replace($URL, $folder, $this->URLJmeterTestPlan);
            }
        }

        if (empty($fileJmeterTestPlan)) {
            $this->AddFail($this->URLJmeterTestPlan. " is unknown in the testsuite of the config.ini.");
            return;
        }


        if (!file_exists($fileJmeterTestPlan)) {
            $this->AddFail("File of this Jmeter Test plan doesn't exist.");
            return;
        }

        $resultFile = str_replace(".jmx", ".jtl", $fileJmeterTestPlan);

        //TODO example to install jmeter with export PATH=$PATH:/home/LOGIN/dev/apache-jmeter-4.0/bin/
        $command = "jmeter -n -t $fileJmeterTestPlan -JHOSTNAME=$TESTENDPOINT_HOSTNAME -JPORT=$TESTENDPOINT_PORT -JPATH=$TESTENDPOINT_PATH  -l $resultFile -X";
        $output = shell_exec($command);
        //print_r($output);
        if (!file_exists($fileJmeterTestPlan)) {
            $this->AddFail("ERROR IN JMETER TEST : \n >>> " . $command . "\n" . $output . "\n");
            return;
        } else {
            //$this->AddFail(file_get_contents($resultFile));
            $report = file_get_contents($resultFile);
            if ($modeVerbose || $modeDebug) {
                print_r("JMETER TEST OK of " . $this->URLJmeterTestPlan . " :\n" . $report);
            }
            if (strpos($report, 'ERROR') !== false) {
                $this->AddFail("ERROR IN JMETER TEST of " . $this->URLJmeterTestPlan . " :\n" . $report);
                return;
            }
        }

        if ($test) {
            echo $message;
            print_r($this->_fails);
            exit();
        }
    }
}
