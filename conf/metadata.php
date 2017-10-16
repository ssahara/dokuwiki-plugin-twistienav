<?php
/**
 * Options for the TwistieNav plugin
 *
 * @author: Simon Delage <simon.geekitude@gmail.com>
 */

$meta['enableTwistie'] = array('multicheckbox', '_choices' => array('youarehere','breadcrumbs','pagebox'), '_other' => 'exists');

$meta['startPagesOnly']     = array('onoff');
$meta['exclusions']         = array('multicheckbox', '_choices' => array('start','sidebar'));
//$meta['pageIdTrace']        = array('onoff');
//$meta['pageIdExtraTwistie'] = array('onoff');
$meta['style']              = array('multichoice', '_choices' => array('svg','fa'));
$meta['distinctHome']       = array('onoff');
