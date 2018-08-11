<?php
/**
 * Created by PhpStorm.
 * User: karima
 * Date: 10/08/18
 * Time: 13:41
 */

abstract class abstractTest
{

    protected $_errors;
    protected $_fails;

    protected $_time = 0;

    function __construct()
    {
        $this->_errors = array();
        $this->_fails = array();
    }

    function AddError($error) {
        if (!in_array($error, $this->_errors)) {
            $this->_errors[] = $error;
        }
        return true;
    }
    function AddFail($fail) {
        if (!in_array($fail, $this->_errors)) {
            $this->_fails[] = $fail;
        }
        return true;
    }

    function GetErrors() {
        return $this->_errors;
    }
    function ResetErrors() {
        $this->_errors = array();
    }
    function GetFails() {
        return $this->_fails;
    }
    function ResetFails() {
        $this->_fails = array();
    }

    function GetTime() {
        return $this->_time;
    }

    public function LoadContentFile($urlToDownload){
        global $listTestSuite;
        $file = "";
        foreach ($listTestSuite as $URL => $folder) {
            if (0 === strpos($urlToDownload, $URL)) {
                $file = str_replace($URL, $folder, $urlToDownload);
            }
        }
        if (empty($file)) {
            $this->AddFail($urlToDownload. " is unknown in the testsuite of the config.ini.");
            return "";
        }
        if (!file_exists($file)) {
            $this->AddFail("File ".$file." of this query doesn't exist.");
            return "";
        }
        return file_get_contents($file);
    }
}
