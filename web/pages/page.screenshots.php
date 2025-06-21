<?php
/*************************************************************************
        This file is part of SourceBans++
*************************************************************************/

if(!defined("IN_SB")) { echo "You should not be here. Only follow links!"; die(); }

global $theme;

$playerid = isset($_GET['playerid']) ? preg_replace('/[^0-9]/','',$_GET['playerid']) : '';
$screenshots = array();
$pattern = $playerid ? SB_DEMOS . '/ss_' . $playerid . '_*.jpg' : SB_DEMOS . '/ss_*.jpg';
foreach(glob($pattern) as $shot) {
    $screenshots[] = basename($shot);
}

$theme->assign('playerid', $playerid);
$theme->assign('screenshots', $screenshots);
$theme->display('page_screenshots.tpl');
?>
