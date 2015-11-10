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

if (!$verbose) {
  http_response_code(403);
  exit;
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


$variables = array();


function process_metric_variables($rec) {
  global $verbose, $variables;

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

  if ($use)
      $variables[$rec->{'id'}] = $rec->{'fields'}->{'Variables'};
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






//Get url for trello
function get_trello_url($url) {
  if (strpos($url, '?') !== false)
    $url = $url . "&key=";
  else
    $url = $url . "?key=";

  $url = $url . getenv("trello-key") . "&token=" . getenv("trello-token");

  return $url;  
}


//Get the trello labels
function get_trello_labels() {
  global $verbose, $local;

  if ($local) return file_get_contents("labels.json");

  $url = "https://api.trello.com/1/boards/" . getenv("trello-board") . "/labels?limit=1000";

  return file_get_contents(get_trello_url($url));
}


$json = get_trello_labels();
//if ($verbose) echo $cardsjson . "<br><br>";
$labels = json_decode($json);


echo 'Missing in trello<br><br>';

foreach ($variables as $id => $var) {
  $e = explode(',', $var);
  foreach ($e as $v) {
    $p = trim($v);
    $found = false;
    foreach ($labels as $label) {
      if (empty($label->{'color'}))
        if ($label->{'name'} === $p) {
          $found = true;
          break;
        }
    }

    if (!$found) {
      echo $p . '<br>';
    }
  }
}

echo '<br><br><br>';

echo 'Missing in airtable<br><br>';

foreach ($labels as $label) {
  if (!empty($label->{'color'})) continue;
  $found = false;
  foreach ($variables as $id => $var) {
    $e = explode(',', $var);
    foreach ($e as $v) {
      $p = trim($v);
      if ($label->{'name'} === $p) {
        $found = true;
        break;
      }
    }
  }

  if (!$found) {
    echo $label->{'name'} . '<br>';
  }
}


echo '<br><br><br>';

echo 'Done!';

?>
  </body>
</html>
