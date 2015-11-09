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


function process_metric_variables($rec) {
  global $verbose;
  
  $vars = explode(',', $rec->{'fields'}->{'Variables'});    
  $use = true;
  for ($v = 0 ; $v < count($vars) ; $v++)
    if (strpos(trim($vars[$v]), '/') !== false ||
        strpos(trim($vars[$v]), ' ') !== false ||
        strpos(trim($vars[$v]), '?') !== false ||
        strtoupper(trim($vars[$v])) === "TBA")
      $use = false;

  if ($verbose) {
    echo $rec->{'id'} . " - ";
    echo $rec->{'fields'}->{'Name'} . '<br>';
    echo $rec->{'fields'}->{'Variables'} . '<br>';
    if ($use) {
      for ($v = 0 ; $v < count($vars) ; $v++)
        echo "\"<strong>" . trim($vars[$v]) . "</strong>\", ";
    } else {
      echo "\"<strong>Do not use!</strong>\", ";
    }
    echo '<br><br>';
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


echo 'Done!';

?>
  </body>
</html>
