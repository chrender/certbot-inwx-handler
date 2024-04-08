#!/usr/bin/php
<?php

$logFileHandle = NULL;
error_reporting(E_ALL);
require 'vendor/autoload.php';

if ($argc != 2) {
  print "\nSyntax: " . basename(__FILE__) . " <command>\n\n";
  exit;
}

function onShutdown() {
  global $logFileHandle;
  if (!is_null($logFileHandle)) {
    logToFile("Exiting " . basename(__FILE__) . ".");
    fclose($logFileHandle);
  }
}
register_shutdown_function('onShutdown');


function logToFile($output) {
  global $logFileHandle;
  $now = DateTime::createFromFormat('U.u', microtime(true));
  $timestamp = $now->format("m-d-Y H:i:s.u");
  fwrite($logFileHandle, "[" . $timestamp . "] " . $output . "\n");
}


$logFilename = dirname(__FILE__) . "/" . date("Y-m-d-H-i-s") . ".txt";
$logFileHandle = fopen($logFilename, "w");
logToFile("Starting " . basename(__FILE__) . ".");

$certbot_inwx_username = getenv('CERTBOT_INWX_USERNAME');
$certbot_inwx_password = getenv('CERTBOT_INWX_PASSWORD');
$certbot_inwx_mfatoken = getenv('CERTBOT_INWX_MFATOKEN');

logToFile("Using username \"" . $certbot_inwx_username . "\".");
logToFile("Length of password: " . strlen($certbot_inwx_password) . ".");
logToFile("Length of mfa-token: " . strlen($certbot_inwx_mfatoken) . ".");



function login() {
  global $certbot_inwx_username, $certbot_inwx_password,
    $certbot_inwx_mfatoken;

  $domrobot = new \INWX\Domrobot();

  logToFile("Starting login.");

  $result = $domrobot->setLanguage('en')
    ->useJson()
    ->useLive() // ->useOte()
    ->setDebug(true)
    ->login($certbot_inwx_username,
            $certbot_inwx_password,
            $certbot_inwx_mfatoken);

  logToFile(print_r($result, true));

  if ($result['code'] != 1000) {
  }

  return $domrobot;
}


function logout($domrobot) {
  logToFile("Logging out.");
  $domrobot->logout();
}


function getLeftAndRightSide($domain) {
  $domainParts = explode('.', $domain);
  $domainPartCount = count($domainParts);
  $leftSide = "";
  $i=0;
  while ($i < $domainPartCount - 2) {
    if (strlen($leftSide) > 0) { $leftSide .= "."; }
    $leftSide .= $domainParts[$i];
    $i++;
  }
  $rightSide
    = $domainParts[$domainPartCount - 2] . "."
    . $domainParts[$domainPartCount - 1];

  logToFile("Left side: \"" . $leftSide . "\".");
  logToFile("Right side: \"" . $rightSide . "\".");

  return [ 'leftSide' => $leftSide, 'rightSide' => $rightSide ];
}


if ($argv[1] == "pre-perform") {
}

elseif ($argv[1] == "perform") {
  $validation = getenv('validation');
  if (empty($validation)) {
	  print("validation empty.");
	  exit -1;
  }
  $bothSides = getLeftAndRightSide(getenv('txt_domain'));
  $domrobot = login();
  $result = $domrobot->call('nameserver', 'createRecord',
          [ 'domain' => $bothSides['rightSide'],
          'type' => 'TXT',
          'name' => $bothSides['leftSide'],
          'content' => $validation,
          'ttl' => 300,
          'testing' => false ]);

  if ($result['code'] != 1000) {
  }

  if ($result['code'] == 1000) {
    $resData = $result['resData'];
    $recordId = $resData['id'];
    print "id:" . $recordId;
  }

  logout($domrobot);
  sleep(30);
}

elseif ($argv[1] == "post-perform") {
}

elseif ($argv[1] == "pre-cleanup") {
}

elseif ($argv[1] == "cleanup") {
  $bothSides = getLeftAndRightSide(getenv('domain'));
  $domrobot = login();
  $validation = getenv('validation');
  $result = $domrobot->call('nameserver', 'info', [
    'domain' => $bothSides['rightSide'],
    'type' => 'TXT',
    'content' => $validation ]);
  logToFile(print_r($result, true));

  if ($result['code'] == 1000) {
    $resData = $result['resData'];
    if (count($resData['record']) == 1) {
      $id = $resData['record'][0]['id'];
      $result = $domrobot->call('nameserver', 'deleteRecord', [
        'id' => $id ]);
      logToFile(print_r($result, true));
    }
  }
  logout($domrobot);
}

elseif ($argv[1] == "post-cleanup") {
}

else {
}

?>

