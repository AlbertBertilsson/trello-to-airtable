<?php

function get_airtable($url) {
  global $verbose;

  if ($verbose) echo "Call: " . $url . "<br><br>";

  $ch = curl_init($url);

  $atheaders = array( 
      "Authorization: Bearer " . getenv("airtable-key")
  );

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);

  $atresult = curl_exec($ch);

  if(curl_errno($ch))
  {
    echo "Failed to get airtable! Curl error: " . curl_error($ch);
    http_response_code(200);
    exit(0);
  }
  curl_close ($ch);

  return $atresult;
}
