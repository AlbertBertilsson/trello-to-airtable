<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = true;

if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) != "https") $verbose = false;
if ($_GET["debug"] != getenv("debug")) $verbose = false;


//Get airtable
/*
function get_airtable() {
  $airtablelisturl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incidents?view=Active";
  //if ($verbose) echo "Call: " . $airtablelisturl . "<br><br>";

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

  //if ($verbose) echo $atresult . "<br><br>";
  return $atresult;
}
*/
//$atresult = get_airtable();



//Hard coded status for the trello lists, only active lists are included
/*
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
      return $matches[1];

  return $name;
}
*/
// Log to airtable
function log_airtable($line) {
  global $verbose;

  $airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/TrelloImportLog";
  if ($verbose) echo "Call: " . $airtablelogurl . "<br><br>";

  $ch = curl_init($airtablelogurl);

  $atheaders = array( 
      "Authorization: Bearer " . getenv("airtable-key"),
      "Content-type: application/json"
  );

  $payload = '{"fields": {"Entry": "' . json_encode($line) . '","Time": "' . date('Y-m-d H:i:s') . '"}}';
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);

  if(curl_errno($ch)) {
    echo "Failed to log to airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}


// Update airtable
/*
function update_airtable($id, $payload) {
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

  if(curl_errno($ch)) {
    echo "Failed to get airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}
*/


//Get the cards
/*
$trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
  "/cards?fields=name,idList&key=" . getenv("trello-key") . 
  "&token=" . getenv("trello-token");

//if ($verbose) echo $trellocardsurl . "<br><br>";
$cardsjson = file_get_contents($trellocardsurl);
//if ($verbose) echo $cardsjson . "<br><br>";
$cards = json_decode($cardsjson);
*/

//Go through all trello cards and calculate changes
$changes = 0;
/*
for ($i = 0; $i < count($cards); $i++) {
  if (!empty($listarr[$cards[$i]->{'idList'}])) {
    $found = false;
    for ($j = 0; $j < count($rows); $j++) {
      if ($cards[$i]->{'id'} == $rows[$j]->{'fields'}->{'TrelloId'}) {
        $id = $rows[$j]->{'id'};
        $changes++;

        $json = '{"fields": {' .
        '"TrelloId": "'. $cards[$i]->{'id'} .'",' . 
        '"Title": "'. json_encode(get_title($cards[$i]->{'name'})) .'",' . 
        '"CQId": "'. json_encode(get_cq($cards[$i]->{'name'})) .'",' . 
        '"INC number": "'. json_encode(get_inc($cards[$i]->{'name'})) .'",' . 
        '"Status": "'. $listarr[$cards[$i]->{'idList'}] .'",' . 
        '"TrelloId": "'. $cards[$i]->{'id'} .'",' . 
        '}}';
        if ($verbose) echo $json . "<br><br>";
        //update_airtable($id, $json);

        $found = true;
        break;
      }
    }
    if (!$found) {
      if ($verbose) echo 'New card: ' . $cards[$i]->{'id'} . '<br>';
      $changes++;
      //Create row
    }
  }
}
*/

//Close cards
/*
for ($j = 0; $j < count($rows); $j++) {
  $found = false;
  $row = $rows[$j]->{'fields'};
  for ($i = 0; $i < count($cards); $i++) {
    if (!empty($listarr[$cards[$i]->{'idList'}])) {
      if ($cards[$i]->{'id'} == $row->{'TrelloId'}) {
        $found = true;
        break;
      }
    }
  }
  if (!$found) {
    if ($verbose) echo 'Closed card: ' . $row->{'TrelloId'} . '<br>';
    $changes++;
    //Set status closed
    $id = $rows[$j]->{'id'};
  }
}
*/


log_airtable('Integration done! ' . $changes . ' changes.');

echo '<br><br>Done!';

?>
  </body>
</html>
