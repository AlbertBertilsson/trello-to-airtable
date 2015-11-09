<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = false;
$local = false;

require_once('log.php');

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

  $url = "https://api.trello.com/1/boards/" . getenv("trello-board") . "/labels";

  return file_get_contents(get_trello_url($url));
}


$json = get_trello_labels();
//if ($verbose) echo $cardsjson . "<br><br>";
$labels = json_decode($json);


//Go through all trello cards and calculate changes

for ($i = 0; $i < count($labels); $i++) {
  $label = $labels[$i];
  echo "Id: " . $label->{'id'} . "<br>";
  echo "Name: " . $label->{'name'} . "<br>";
  echo "Color: " . $label->{'color'} . "<br>";
  echo "<br>";
}


//loggly_log(file_get_contents('php://input'));
echo 'Done!';

?>
  </body>
</html>
