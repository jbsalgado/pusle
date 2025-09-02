<?php

namespace app\helpers;


/**
 * Salty Logger
 * <p>
 * A simple way to log code
 * </p>
 * @author Junior Pires <juniorpiresu.pe@gmail.com>
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License, version 3
 * @version 1.0
 * 
 */
class Salg {
    
    const REPLACE = 0;
    const APPEND = 1;
    
    /**
     * log in a file
     * 
     * @param String $value The value to be logged
     * @param boolean $print If the log will be show in the browser. Default false.
     * @param String $filename The file name whrere will be logged. Default 'log'.
     * @param int $mode Insert mode. REPLACE will replace the file content. APPEND will put the value to begin of file. Default REPLACE.
     * 
     * Examples:<br>
     * 
     * Log in the default file salg/log.txt:<br>
     * <code>
     * <?php
     * //Salg::log($variable);
     * ?>
     * </code>
     * 
     * Log in the default file and show in the browser:<br>
     * <code>
     * <?php
     * //Salg::log($variable,true);
     * ?>
     * </code>
     * 
     * Using a custom file:<br>
     * <code>
     * <?php
     * //Salg::log($variable,true,"default");
     * ?>
     * </code>
     * 
     * Replace a log:<br>
     * <code>
     * <?php
     * //Salg::log($variable,true,"default",Salg::REPLACE);
     * ?>
     * </code>
     */
    public static function log($value, $print=false, $filename='log', $mode=self::REPLACE){
        
        $dir_name = "salg";
        $filename = $dir_name.DIRECTORY_SEPARATOR.$filename.".txt";
        
        if (!is_dir($dir_name)) {
            mkdir($dir_name);
        }
        
        $title = "At ".date("d-m-Y H:i:s").":\n";
        $body = print_r($value,true);
        $text = $title.$body;
        
        if ($mode==self::APPEND) {
            if(file_exists($filename)){
                $text .= file_get_contents($filename); 
            }
        }
        
        file_put_contents($filename,$text);
        
        if($print){
            self::printLog($filename);
        }
        
    }
    
   private static function printLog($filename){
        $log = file_get_contents($filename);
        
        echo '<span>Salg file path: '.realpath($filename).'</span>';
        echo '<div style="border:1px solid red;height:250px;overflow:auto">';
        echo "<pre>".$log."</pre>";
        echo '</div>';
   } 
}
