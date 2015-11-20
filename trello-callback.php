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
require_once('trello-lists.php');

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


//Get the trello cards
function get_trello() {
  global $verbose, $local;

  if ($local) return file_get_contents("trello-cardlist.json");

  $trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
    "/cards?fields=idList&key=" . getenv("trello-key") . 
    "&token=" . getenv("trello-token");

  $cardsjson = file_get_contents($trellocardsurl);

  return $cardsjson;
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


function process_metric_variables($rec) {
  global $verbose, $variables, $links, $archive;

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
    if (!empty($rec->{'fields'}->{'Trello archive'}))
      $archive[$rec->{'id'}] = $rec->{'fields'}->{'Trello archive'};
  }
}


$variables = array();
$links = array();
$archive = array();



$offset = '';
do {
  $res = get_airtable_metrics($offset);
  $json = json_decode($res);
  $recs = $json->{'records'};
  for ($i = 0 ; $i < count($recs) ; $i++) {
    $rec = $recs[$i];
    process_metric_variables($rec);
  }

  if (isset($json->{"offset"})) $offset = $json->{"offset"};

} while (isset($json->{"offset"}));


$cardsjson = get_trello();
$cards = json_decode($cardsjson);
$cardlist = array();
foreach ($cards as $card) {
  $cardlist[$card->{'id'}] = $card->{'idList'};
}



function get_var_rowid($label) {
  global $variables;  

  foreach ($variables as $id => $var) {
    $e = explode(',', $var);
    foreach ($e as $v) {
      if (trim($v) == $label) 
        return $id;
    }
  }

  return false;
}

function get_link_rowids($link) {
  global $links, $archive;  

  $rows = array();
  foreach ($links as $id => $var)
    if (strpos($var, $link) !== false)
      $rows[] = $id;

  foreach ($archive as $id => $var)
    if (strpos($var, $link) !== false)
      $rows[] = $id;

  return $rows;
}

function add_link($links, $link) {
  if (empty($links)) return $link;

  if (strpos($links, $link) !== false)
    return $links;

  return trim($links) . ', ' . $link;
}

function remove_link($links, $link) {
  if (empty($links)) return $links;

  if (strpos($links, $link) === false)
    return $links;

  $l = explode(',', $links);
  if (count($l) === 0) return '';

  $new = '';
  foreach ($l as $i) {
    $i = trim($i);
    if ($i != $link && !empty($i) ) {
      $new .= ', ' . $i;
    }
  }
  $new = trim(substr($new, 2));

  return $new;
}



$action = $post->{'action'};
$cardlink = $action->{'data'}->{'card'}->{'shortLink'};
$shortlink = 'https://trello.com/c/' . $cardlink;
echo 'Shortlink: ' . $shortlink . "\n";

$type = $action->{'type'};

//Label change
if ($type == 'addLabelToCard' || $type == 'removeLabelFromCard') {

  $rowid = get_var_rowid($action->{'data'}->{'label'}->{'name'});

  $new = $old = '';
  $list = $cardlist[$action->{'data'}->{'card'}->{'id'}];
  $active = in_array($list, $trello_affecting_lists);
  if ($active){
    if (isset($links[$rowid])) $old = $links[$rowid];
  } else {
    if (isset($archive[$rowid])) $old = $archive[$rowid];
  }

  if ($type == 'addLabelToCard')
    $new = add_link($old, $shortlink);
  else
    $new = remove_link($old, $shortlink);


  if ($new != $old) {
    echo "Old: $old\n";
    echo "New: $new\n";
    if ($active)
      $data = "{ \"fields\": {\"Trello links\": " . json_encode($new) . "}}";
    else
      $data = "{ \"fields\": {\"Trello archive\": " . json_encode($new) . "}}";

    update_airtable_metric($rowid, $data);
    loggly_log($data);
  }
}


//List change
if ($type == 'updateCard') {
  $after = $action->{'data'}->{'listAfter'}->{'id'};
  $before = $action->{'data'}->{'listBefore'}->{'id'};

  $rowids = get_link_rowids($shortlink);
  loggly_log(json_encode($rowids));

  if (in_array($before, $trello_affecting_lists) && !in_array($after, $trello_affecting_lists)){
    loggly_log("{ \"listchange\" : \"archive\"}");
    //Add to archive
    //Remove from links
  }
  if (!in_array($before, $trello_affecting_lists) && in_array($after, $trello_affecting_lists)){
    loggly_log("{ \"listchange\" : \"restore\"}");
    //Add to links
    //Remove from archive
  }
}



loggly_log(json_encode($action));
