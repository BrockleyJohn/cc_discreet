<?php
/***************************************************************
*
* payment portal 
* - forward requests to payment gateway
*
* initial version against authorize.net AIM
*
* designed for OSCOM Phoenix
* version: 1.0 July 2020
* author: John Ferguson @BrockleyJohn oscommerce@sewebsites.net
* copyright (c) 2020 SE Websites
* 
* released under MIT licence without warranty express or implied
****************************************************************/

if (isset($_POST['server'])) {
  
  chdir('../../../../');
  ini_set('error_log', 'php-error.log');
  error_log("call to portal '". print_r($_POST, true) ."'");

  require_once('includes/configure.php');
  require_once('includes/functions/database.php');
// make a connection to the database... now
  tep_db_connect() or die('Unable to connect to database server!');
// set application wide parameters
  $configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from configuration');
  while ($configuration = tep_db_fetch_array($configuration_query)) {
    define($configuration['cfgKey'], $configuration['cfgValue']);
  }
  require_once('includes/functions/general.php');
  
  include('includes/languages/english/modules/payment/cc_discreet.php');
  include('includes/modules/payment/cc_discreet.php');

  $cc_discreet = new cc_discreet();
  
  echo $cc_discreet->sendToGateway();
}