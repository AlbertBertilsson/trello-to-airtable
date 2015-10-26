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

// Update airtable
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
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);
  if ($verbose) echo $atresult . '<br><br>';

  if(curl_errno($ch)) {
    echo "Failed to update row in airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}

function card_to_row_json($card) {
  global $verbose, $listarr;

  $json = '{"fields": {' .
    '"TrelloId": "'. $card->{'id'} .'",' . 
    '"Title": '. json_encode(get_title($card->{'name'})) .',' . 
    '"CQId": '. json_encode(get_cq($card->{'name'})) .',' . 
    '"INC number": '. json_encode(get_inc($card->{'name'})) .',' . 
    '"Status": '. json_encode($listarr[$card->{'idList'}]) . 
    '}}';

  if ($verbose) echo $json . "<br><br>";

  return $json;
}

$card = json_decode('{"TrelloId":"561b6c1cb394acc46b1e5ea2","Title":"Quick View event not set for Quick View pages","Status":"Open","CQId":"CQ75"},"createdTime":"2015-10-23T12:25:21.000Z"}');
$json = card_to_row_json($cards);
update_airtable($id, $json);
