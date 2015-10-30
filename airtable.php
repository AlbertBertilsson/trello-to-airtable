<?php



function get_airtable() {
  global $verbose, $local;

  if ($local) return file_get_contents("airtable-data.json");

  $airtablelisturl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incidents?view=Active";
  if ($verbose) echo "Call: " . $airtablelisturl . "<br><br>";

  $ch = curl_init($airtablelisturl);

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



function update_airtable($id, $payload) {
  global $verbose, $local;

  if ($local) return;

  $airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incidents/" . $id;
  if ($verbose) echo "Call: " . $airtablelogurl . "<br><br>";

  $ch = curl_init($airtablelogurl);

  $atheaders = array( 
      "Authorization: Bearer " . getenv("airtable-key"),
      "Content-type: application/json"
  );

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);
  if ($verbose) echo $atresult . '<br><br>';

  if(curl_errno($ch)) {
    echo "Failed to update row in airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}



function create_airtable($payload) {
  global $verbose, $local;

  if ($local) return;

  $airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incidents";
  if ($verbose) echo "Call: " . $airtablelogurl . "<br><br>";

  $ch = curl_init($airtablelogurl);

  $atheaders = array( 
      "Authorization: Bearer " . getenv("airtable-key"),
      "Content-type: application/json"
  );

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);

  if(curl_errno($ch)) {
    echo "Failed to create row in airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}
