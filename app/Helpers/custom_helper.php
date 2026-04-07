<?php

use Config\Database;

if (!function_exists('dump')) 
{
    function dump($data, $die = false, $add_var_dump = false, $add_last_query = true) 
    {
        $db = Database::connect();

        // GET VARIABLE NAME
        $bt     = debug_backtrace();
        $src    = file($bt[0]["file"]);
        $line   = $src[$bt[0]['line'] - 1];

        preg_match('#dump\((.+)\)#', $line, $match);

        $varname = $match[1] ?? 'Unknown';

        // TYPE CHECK
        if (is_object($data))      $message = 'OBJECT';
        elseif (is_array($data))   $message = 'ARRAY';
        elseif (is_string($data))  $message = 'STRING';
        elseif (is_int($data))     $message = 'INTEGER';
        elseif (is_bool($data))    $message = 'BOOLEAN';
        elseif (is_null($data))    $message = 'NULL';
        elseif (is_float($data))   $message = 'FLOAT';
        else                       $message = 'N/A';

        $output  =  '<div style="clear:both;"></div>';
        $output .=  '<meta charset="UTF-8" />';
        $output .=  '   <style>::selection{background-color:#E13300!important;color:#fff}::moz-selection{background-color:#E13300!important;color:#fff}::webkit-selection{background-color:#E13300!important;color:#fff}div.debugbody{background-color:#fff;margin:40px;font:9px/12px normal;font-family:Arial,Helvetica,sans-serif;color:#4F5155;min-width:500px}a.debughref{color:#039;background-color:transparent;font-weight:400}h1.debugheader{color:#444;background-color:transparent;border-bottom:1px solid #D0D0D0;font-size:12px;line-height:14px;font-weight:700;margin:0 0 14px;padding:14px 15px 10px;font-family:Consolas}code.debugcode{font-family:Consolas,Monaco,Courier New,Courier,monospace;font-size:12px;background-color:#f9f9f9;border:1px solid #D0D0D0;color:#002166;display:block;margin:10px 0;padding:5px 10px 15px}pre.debugpre{display:block;padding:0;margin:0;color:#002166;font:12px/14px normal;font-family:Consolas,Monaco,Courier New,Courier,monospace;background:0;border:0}div.debugcontent{margin:0 15px}p.debugp{margin:0;padding:0}.debugitalic{font-style:italic}.debutextR{text-align:right;margin-bottom:0;margin-top:0}.debugbold{font-weight:700}p.debugfooter{text-align:right;font-size:11px;border-top:1px solid #D0D0D0;line-height:32px;padding:0 10px;margin:20px 0 0}div.debugcontainer{margin:10px;border:1px solid #D0D0D0;-webkit-box-shadow:0 0 8px #D0D0D0}code.debug p{padding:0;margin:0;width:100%;text-align:right;font-weight:700;text-transform:uppercase;border-bottom:1px dotted #CCC;clear:right}code.debug span{float:left;font-style:italic;color:#CCC}</style>';
        $output .=  '   <div class="debugbody">';
        $output .=  '      <div class="debugcontainer">';
        $output .=  '          <h1 class="debugheader">'.$varname.'</h1>';
        $output .=  '          <div class="debugcontent">';
        $output .=  '              <code class="debugcode">';
        $output .=  '                  <p class="debugp debugbold debutextR">:: Variable Type</p>';
        $output .=                     $message;
        $output .=  '              </code>';

        // LAST QUERY
        if ($add_last_query) {
            try {
                $lastQuery = $db->getLastQuery();
                if ($lastQuery) {
                    $output .= '<code class="debugcode">';
                    $output .= '    <p class="debugp debugbold debutextR">:: $CI->db->last_query()</p>';
                    $output .=      $lastQuery;
                    $output .= '</code>';
                }
            } catch (\Exception $e) {}
        }

        // PRINT_R
        $output .= '<code class="debugcode">';
        $output .= '    <p class="debugp debugbold debutextR">:: print_r</p>';
        $output .= '    <pre class="debugpre">';
        ob_start();
        print_r($data);
        $output .= trim(ob_get_clean());
        $output .= '    </pre>';
        $output .= '</code>';

        // VAR_DUMP
        if ($add_var_dump) {
            $output .= '<code class="debugcode">';
            $output .= '    <p class="debugp debugbold debutextR">:: var_dump</p>';
            $output .= '    <pre class="debugpre">';
            ob_start();
            var_dump($data);
            $output .= trim(ob_get_clean());
            $output .= '    </pre>';
            $output .= '</code>';
        }

        $output .= '            </div>';
        $output .= '        </div>';
        $output .= '    </div>';
        $output .= '</meta>';

        if (PHP_SAPI == 'cli') {
            echo $varname . PHP_EOL;
            print_r($data);
            return;
        }

        echo $output;

        if ($die) {
            exit;
        }
    }
}