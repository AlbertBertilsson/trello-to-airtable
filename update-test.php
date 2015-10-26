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


//Hard coded status for the trello lists, only active lists are included

$listarr = array(
  "55e5af5a7105ece0bb03d417" => "Open",
  "55e58b8960a27158e41e7898" => "Open",
  "55fc2a94fc78778668ac912e" => "Requires backend fix",
  "55e58b8960a27158e41e789a" => "Fixed in Tealium",
  "55e58b8960a27158e41e789b" => "Waiting for verification",
  );

//Functions for processing card name
function get_cq($name) {
  $matches = array();
  preg_match('/\[CQ([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "CQ" . $matches[1];

  return "";
}

function get_inc($name) {
  $matches = array();
  preg_match('/\[INC([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "INC" . $matches[1];

  return "";
}


function get_title($name) {
  $matches = array();
  preg_match('/.*\](.*)/', $name, $matches);
  if (isset($matches[1]))
    if (strlen(trim($matches[1])) > 0)
      return trim($matches[1]);

  return $name;
}

function get_field($row, $field) {
  if (isset($row->{$field}))
    return $row->{$field};

  return "";
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
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);
  if ($verbose) echo $atresult . '<br><br>';

  if(curl_errno($ch)) {
    echo "Failed to update row in airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}

$cards = json_decode('{"id":"561b6c1cb394acc46b1e5ea2","name":"(8) [CQ75] [INSERT ID] Quick View event not set for Quick View pages (2)","idList":"55e5af5a7105ece0bb03d417"}');
$json = card_to_row_json($cards);
//$json = json_decode('{"fields": {"CQId": "CQ275"}}');
update_airtable("recLFGi40f8HHEycT", $json);

?>
