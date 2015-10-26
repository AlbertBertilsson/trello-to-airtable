<html>
  <head>
    <title>Test</title>
  </head>
  <body>
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


//Get airtable
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
  //$payload = '{"fields": {"Entry": "Test!","Time": "' . date('Y-m-d H:i:s') . '"}}';
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);
  if ($verbose) echo $atresult . '<br><br>';

  if(curl_errno($ch)) {
    echo "Failed to log to airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
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


// Update airtable
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
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

  $atresult = curl_exec($ch);

  if(curl_errno($ch)) {
    echo "Failed to create row in airtable! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);
}


//Get the trello cards
function get_trello() {
  global $verbose, $local;

  if ($local) return file_get_contents("trello-data.json");

  $trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
    "/cards?fields=name,idList&key=" . getenv("trello-key") . 
    "&token=" . getenv("trello-token");

  $cardsjson = file_get_contents($trellocardsurl);

  return $cardsjson;
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


$cardsjson = get_trello();
if ($verbose) echo $cardsjson . "<br><br>";
$cards = json_decode($cardsjson);


$rowsjson = get_airtable();
if ($verbose) echo $rowsjson . "<br><br>";
$rows = json_decode($rowsjson)->{"records"};




//Go through all trello cards and calculate changes
$changes = 0;

for ($i = 0; $i < count($cards); $i++) {
  if (!empty($listarr[$cards[$i]->{'idList'}])) {
    $found = false;
    for ($j = 0; $j < count($rows); $j++) {
      $row = $rows[$j]->{'fields'};
      if ($cards[$i]->{'id'} == $row->{'TrelloId'}) {
        $updated = false;

        if (get_title($cards[$i]->{'name'}) != trim(get_field($row, 'Title'))) $updated = true;
        if (get_cq($cards[$i]->{'name'}) != get_field($row, 'CQId')) $updated = true;
        if (get_inc($cards[$i]->{'name'}) != get_field($row, 'INC number')) $updated = true;
        if ($listarr[$cards[$i]->{'idList'}] != get_field($row, 'Status')) $updated = true;

        if ($updated) {
          if ($verbose) echo 'Changed card: ' . $cards[$i]->{'id'} . '<br>';
          $changes++;

          $json = card_to_row_json($cards[$i]);
          $id = $rows[$j]->{'id'};
          update_airtable($id, $json);
        }

        $found = true;
        break;
      }
    }

    if (!$found) {
      if ($verbose) echo 'New card: ' . $cards[$i]->{'id'} . '<br>';
      $changes++;

      $json = card_to_row_json($cards[$i]);
      create_airtable($json);
    }
  }
}


//Close cards
for ($j = 0; $j < count($rows); $j++) {
  $row = $rows[$j]->{'fields'};
  if ($row->{'Status'} != 'Closed') {
    $found = false;

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
      update_airtable($id, '{"fields": {"Status": "Closed"}}');
    }
  }
}


log_airtable('Integration done! ' . $changes . ' changes.');

echo '<br><br>Done!';

?>
  </body>
</html>
