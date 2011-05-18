<?php

/**
 * Smarty block function for sectioning off content which should only be displayed
 * during a specified time period.
 *
 * @param array Function parameters passed from Smarty
 * @param string Content of Smarty function block, which is completed and passed
 * to this function only on the second pass (execution of ending function block 
 * tag).  For information on why block functions are executed twice, read the
 * docs at http://smarty.net/manual/en/plugins.block.functions.php
 * @param object Reference to the Smarty template object
 * @param bool Repeat execution
 * @return string Content to be displayed from function block.
 */
function smarty_block_timeperiod($params, $content, &$smarty, &$repeat)
{
    if (!defined('SMARTY_TIMEPERIOD_NOW')) {
        // Allow override of current time for testing.
        if (!empty($_REQUEST['use_time'])) {
            define('SMARTY_TIMEPERIOD_NOW', strtotime($_REQUEST['use_time']));
            define('SMARTY_TIMEPERIOD_DEBUG', true);
            $smarty->trigger_error('Using test time for ' . __FUNCTION__ . ': ' . date('m/d/Y H:i:s', SMARTY_TIMEPERIOD_NOW), E_USER_NOTICE);
        } else {
            define('SMARTY_TIMEPERIOD_NOW', time());
            define('SMARTY_TIMEPERIOD_DEBUG', false);
        }
    }
    
    $start_time = false;
    $end_time = false;
    $now = SMARTY_TIMEPERIOD_NOW;
    $display = false;
    $comment = false;
    
    if (!isset($params['start']) && !isset($params['end'])) {
        $smarty->trigger_error("At least one parameter of 'start' or 'end' must be provided");
        return;
    }
    
    // Make timestamps from parameters
    if (isset($params['start'])) {
        $start_time = strtotime($params['start']);
        if (!$start_time) {
            $smarty->trigger_error("Invalid value passed for parameter 'start': {$params['start']}");
            return;
        }
    }
    if (isset($params['end'])) {
        $end_time = strtotime($params['end']);
        if (!$end_time) {
            $smarty->trigger_error("Invalid value passed for parameter 'end': {$params['end']}");
            return;
        }
    }
    // Validate the timestamps
    if ($start_time && $end_time && $start_time > $end_time) {
        $smarty->trigger_error("'start' value may not exceed 'end' value");
        return;
    }

    // Display content
    if (($start_time && $end_time) && ($now >= $start_time && $now <= $end_time)) {
        $display = 'a';
    } else if ($start_time && !$end_time && $now >= $start_time) {
        $display = 'b';
    } else if ($end_time && !$start_time && $now <= $end_time) {
        $display = 'c';
        
    } else if (SMARTY_TIMEPERIOD_DEBUG) {
        if ($start_time && $now < $start_time) {
            return '<!-- Content will be displayed in ' . smarty_block_timeperiod_diff($now, $start_time) . '. -->'; 
        } else if ($end_time && $now > $end_time) {
            return '<!-- Content stopped displaying ' . smarty_block_timeperiod_diff($now, $end_time) . ' ago. -->'; 
        }
    }
    
    if ($display) {
        return $content;
    } else {
        // Prevent calling of this function for end block tag if timestamps
        // are not satisfied.
        $repeat = false;
    }
}

function smarty_block_timeperiod_diff($a, $b)
{
    $diff = abs($a - $b);
    if ($diff > 86400) {
        return round($diff / 86400) . ' day(s)';
    } else if ($diff > 3600) {
        return round($diff / 3600) . ' hour(s)';
    } else if ($diff > 60) {
        return round($diff / 60) . ' minute(s)';
    } else {
        return $diff . ' second(s)';
    }
}

?>
