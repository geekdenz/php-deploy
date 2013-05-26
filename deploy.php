#!/usr/bin/php
<?php
/**
 * global config vars.
 * Please set in config.php only!
 */
$debug = false;
$x_color = 'cyan';
$output_color = 'white';
$bg_color = 'black';

/**
 * Included classes
 */
require_once('Colors.php');
/**
 * global variables and functions for convenience
 */
$colors = new Colors();
/**
 * global functions
 */
function e($str, $color = 'white', $bg_color = 'black') {
    global $colors;
    echo $colors->getColoredString($str ."\n", $color, $bg_color);
}
function x($command, $color = 'cyan', $bg_color = 'black') {
    global $debug,$colors;
    echo $colors->getColoredString($command, $color, $bg_color) ."\n";
    if (!$debug) {
        passthru($command);
    }
}
function d($v) {
    global $debug;
    if ($debug) {
        print_r($v);
        echo "\n";
    }
}
/**
 * Deploy class for handling all aspects of deployment
 */
class Deploy {
    var $time_format = 'Y-m-d_H-i-s';
    var $tmp_dir = '/tmp';
    var $local = array();
    var $remote = array();

    var $configFile;
    var $config;
    var $configRealPath;
    var $deploymentTime;
    function __construct($file = 'config.php') {
        $this->configRealPath = realpath($file);
        $this->configFile = $file;
        $this->configDir = dirname($this->configRealPath);
        $this->deploymentTime = time();
        $this->parseConfig($file);
    }
    function parseConfig($file) {
        global $debug,$x_color,$output_color,$bg_color;
        if (file_exists($this->configFile)) {
            $config = require_once($this->configFile);
            foreach ($config as $k => $v) {
                switch ($k) {
                    case 'debug':
                        $debug = $v;
                        break;
                    case 'x_color':
                        $x_color = $v;
                        break;
                    case 'output_color':
                        $output_color = $v;
                        break;
                    case 'bg_color':
                        $bg_color = $v;
                        break;
                    default:
                        $this->$k = $v;
                        break;
                }
            }
            $this->sourceDir = realpath($this->configDir .'/'. $this->local['directory']);
            $this->tmp_dir = $this->tmp_dir ."/deployer_". $this->deploymentTime;
            x("mkdir -p ". $this->tmp_dir);
        } else {
            throw new Exception('Config file: '. $this->configFile .' does not exist.');
        }
        $this->tmp_dir or $this->tmp_dir = '/tmp';
        isset($this->local['user']) or $this->local['user'] = trim(`whoami`);
        isset($this->remote['tmp_dir']) or $this->remote['tmp_dir'] = '/tmp';
        isset($this->remote['user']) or $this->remote['user'] = $this->local['user'];
    }
    function deploy() {
        //d($this);
        if ($this->deploy_db) {
            $this->dumpDb();
            $this->compressDb();
            $this->transferDb();
            $this->restoreDb();
        }
        $this->rsyncTo();
        $this->runTasks();
        $this->cleanup();
    }

    function getTime($time) {
        return date($this->time_format, $time);
    }
    function dumpDb() {
        $db = $this->local['db'];
        if (!$db) {
            return;
        }
        e("Dumping Database...");
        $user = $this->local['db_user'];
        $pass = $this->local['db_password'];
        $tmpDir = $this->tmp_dir;
        $time = $this->getTime($this->deploymentTime);
        $dumpFile = "$tmpDir/$time.sql";
        $c = "mysqldump -u$user -p$pass $db > $dumpFile";
        x($c);
        $this->dumpFile = $dumpFile;
    }
    function compressDb() {
        global $debug;
        $dumpFile = $this->dumpFile;
        if (!$debug && !file_exists($dumpFile)) {
            return;
        }
        e("Compressing Database...");
        $this->compressedFile = $compressedFile = "$dumpFile.tar.gz";
        $c = "tar cvfz $compressedFile $dumpFile";
        x($c);
    }
    function transferDb() {
        global $debug;
        $compressedFile = $this->compressedFile;
        if (!$debug && !file_exists($compressedFile)) {
            return;
        }
        e("Transferring compressed Database...");
        $dest = $this->remote['user'] ."@". $this->remote['host'] .":". $this->remote['tmp_dir'] ."/". basename($compressedFile);
        $c = "scp $compressedFile $dest";
        x($c);
    }
    function restoreDb() {
        e("Restoring Database...");
        e("                     (not implemented yet)");
    }
    function rsyncTo() {
        $delete = $this->rsync_delete;
        e("Synching files to remote". ($delete ? " and overwriting" : "") ."...");
        $srcDir = $this->sourceDir .'/';
        $destDir = $this->remote['user'] .'@'. $this->remote['host'] .':'. $this->remote['directory'] .'/';
        $deploymentTime = $this->deploymentTime;
        $exclude = $this->tmp_dir ."/exclude_$deploymentTime.txt";
        $include = $this->tmp_dir ."/include_$deploymentTime.txt";
        $toKeepTmp = $this->tmp_dir ."/to_keep/";

        e("     backing up to keep files...");
        foreach ($this->remote['to_keep'] as $toKeep) {
            x("rsync -apvPu --progress $toKeep $toKeepTmp");
        }
        //file_put_contents();
        e("     syncing from local to remote...");
        $c = "rsync -apvPu ".
                ($delete ? "--del " : "").
                //"--include-from=$include ".
                //"--exclude-from=$exclude ".
                "$srcDir $destDir";
        x($c);

        e("     redeploying to keep files...");
        $c = "rsync -apvPu $toKeepTmp $destDir";
        x($c);
    }
    function runTasks() {
        if (!isset($this->tasks) || !is_array($this->tasks)) {
            return;
        }
        foreach ($this->tasks as $task) {
            x($task);
        }
    }
    function cleanup() {
        e("Cleaning up...");
        x("rm -Rf ". $this->tmp_dir);
    }
}

$config = $argc > 1 ? $argv[1] : 'config.php';
$deploy = new Deploy($config);
$deploy->deploy();
