<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php
require_once('log.php');
require_once('airtable.php');

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


function get_airtable_metrics($offset) {
  global $local;

  if ($local)
    if (empty($offset))
      return file_get_contents("metrics-1.json");
    else
      return file_get_contents("metrics-2.json");

  $c = '';
  if (!empty($offset)) $c = '&offset=' . $offset;

  $url = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Metric?view=Main%20View" + $c;

  return get_airtable($url);
}


$offset = '';
do {
  $res = get_airtable_metrics($offset);
  echo $res . '<br><br>';
  $json = json_decode($res);
  $recs = $json->{'records'};
  for ($i = 0 ; $i < count($recs) ; $i++) {
    $rec = $recs[$i];
    echo $rec->{'id'} . " - ";
    echo $rec->{'fields'}->{'Name'} . '<br>';
    echo $rec->{'fields'}->{'Variables'} . '<br>';
    $vars = explode(',', $rec->{'fields'}->{'Variables'});
    //var_dump($vars);
    for ($v = 0 ; $v < count($vars) ; $v++)
      echo "\"<strong>" . trim($vars[$v]) . "</strong>\", ";
    echo '<br><br>';
  }

  if (isset($json->{"offset"})) $offset = $json->{"offset"};

} while (isset($json->{"offset"}));


echo 'Done!';

?>
  </body>
</html>
