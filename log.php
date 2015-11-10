<?php

function loggly_log($data) {
  global $local;

  if ($local) {
    echo 'Log: ' . $data . "\n";
    return;
  }

  $logurl = "https://logs-01.loggly.com/inputs/" . getenv("loggly-token") . "/tag/" . $_SERVER["SERVER_NAME"] . "/";

  $ch = curl_init($logurl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $atresult = curl_exec($ch);

  if(curl_errno($ch)) {
    echo "Failed to log! Curl error: " . curl_error($ch) . "<br><br>";
  }

  curl_close ($ch);
}
