<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = false;
$local = false;

require_once('log.php');
require_once('airtable.php');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo 'POST request expected.';
  exit(0);
}

$data = file_get_contents('php://input');
$post = json_decode($data);


if (!$local && $post->{'action'}->{'data'}->{'board'}->{'shortLink'} !== getenv("trello-board")) {
  loggly_log($data);
  exit(0);
}


function get_airtable_metrics($offset) {
  global $local;

  if ($local)
    if (empty($offset))
      return file_get_contents("metrics-1.json");
    else
      return file_get_contents("metrics-2.json");

  $c = '';
  if (!empty($offset)) $c = '&offset=' . $offset;

  $url = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Metric?view=Main%20View" . $c;

  return get_airtable($url);
}


function update_airtable_metric($metric, $payload) {
  global $verbose, $local;

  if ($local) return;

  $airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Metric/" . $metric;
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


$variables = array();
$links = array();


function process_metric_variables($rec) {
  global $verbose, $variables, $links;

  $vars = explode(',', $rec->{'fields'}->{'Variables'});    
  $use = true;
  for ($v = 0 ; $v < count($vars) ; $v++)
    if (strpos(trim($vars[$v]), '/') !== false ||
        strpos(trim($vars[$v]), ' ') !== false ||
        strpos(trim($vars[$v]), '?') !== false ||
        strtoupper(trim($vars[$v])) === "TBA")
      $use = false;

  if (count($vars) === 0)
    $use = false;

  if ($use) {
    $variables[$rec->{'id'}] = $rec->{'fields'}->{'Variables'};
    if (!empty($rec->{'fields'}->{'Trello links'}))
      $links[$rec->{'id'}] = $rec->{'fields'}->{'Trello links'};
  }
}


$offset = '';
do {
  $res = get_airtable_metrics($offset);
  //echo $res . '<br><br>';
  $json = json_decode($res);
  $recs = $json->{'records'};
  for ($i = 0 ; $i < count($recs) ; $i++) {
    $rec = $recs[$i];
    process_metric_variables($rec);
  }

  if (isset($json->{"offset"})) $offset = $json->{"offset"};

} while (isset($json->{"offset"}));





$action = $post->{'action'};
$cardlink = $action->{'data'}->{'card'}->{'shortLink'};
$shortlink = 'https://trello.com/c/' . $cardlink;
echo 'Shortlink: ' . $shortlink . "\n";

$type = $action->{'type'};

//Label change
if ($type == 'addLabelToCard' || $type == 'removeLabelFromCard') {
  //Get airtable row id.
  $label = $action->{'data'}->{'label'}->{'name'};
  $rowid = '';
  foreach ($variables as $id => $var) {
    $e = explode(',', $var);
    foreach ($e as $v) {
      if (trim($v) == $label) {
        $rowid = $id;
        break;
      }
    }
    if (!empty($rowid)) break;
  }

  $new = '';
  if (isset($links[$rowid])) $new = $links[$rowid];
  $pos = strpos($new, $shortlink);
  if ($type == 'addLabelToCard') { //Add
    if ($pos === false) {
      if (empty($new))
        $new = $shortlink;
      else
        $new = trim($new) . ', ' . $shortlink;
    }
  } else { //Remove
    if ($pos !== false) {
      $l = explode(',', $new);
      if (count($l) > 1) {
        $new = '';
        foreach ($l as $i) {
          $i = trim($i);
          if ($i != $shortlink && !empty($i) ) {
            $new .= ', ' . $i;
          }
        }
        $new = trim(substr($new, 2));
      } else {
        $new = '';
      }
    }
  }

  if ($new != $links[$rowid]) {
    $data = "{ \"fields\": {\"Trello card\": " . json_encode($new) . "}}";
    loggly_log('Update: ' . $data);
  } else {
    loggly_log('No change!');
  }
}


//List update
/*
$active_lists = array(
  "55e5af5a7105ece0bb03d417",
  "55e58b8960a27158e41e7898",
  "55fc2a94fc78778668ac912e",
  "55e58b8960a27158e41e789a",
  "55e58b8960a27158e41e789b",
  );
*/

$signature = $type . ' - ' . $action->{'memberCreator'}->{'fullName'};
loggly_log($signature);
