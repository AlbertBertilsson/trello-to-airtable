<?php

$verbose = false;
$local = false;

if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))
  if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "https")
    if (isset($_GET["debug"]))
      if ($_GET["debug"] == getenv("debug"))
        $verbose = true;

if (isset($_SERVER["HTTP_HOST"]))
  if ($_SERVER["HTTP_HOST"] == "localhost:8080") {
    $local = true;
    $verbose = true;    
  }

// Log to airtable
function log_airtable($line) {
  global $verbose, $local;

  if ($local || $verbose) {
    echo $line;
    return;
  }

  $airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/TrelloImportLog";
  if ($verbose) echo "Call: " . $airtablelogurl . "<br><br>";

  $ch = curl_init($airtablelogurl);

  $atheaders = array( 
      "Authorization: Bearer " . getenv("airtable-key"),
      "Content-type: application/json"
  );

  $payload = '{"fields": {"Entry": ' . json_encode($line) . ',"Time": "' . date('Y-m-d H:i:s') . '"}}';
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);
  if ($verbose) echo $atresult . '<br><br>';

  if(curl_errno($ch)) {
    echo "Failed to log to airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}

$changes = 1;

log_airtable('Integration done! ' . $changes . ' changes.');


?>
