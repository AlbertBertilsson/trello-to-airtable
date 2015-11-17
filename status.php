<html>
  <head>
    <title>Status</title>
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

if (!$verbose) {
  http_response_code(403);
  exit(0);
}




//Functions for processing card name
function get_cq($name) {
  $matches = array();
  preg_match('/\[CQ([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "CQ" . $matches[1];

  return "";
}


function find_cq($short) {
  global $cq_url_dictionary;

  if (isset($cq_url_dictionary[$short]))
    return $cq_url_dictionary[$short];
  else
    return $short;
}

//Get the trello cards
function get_trello() {
  global $verbose, $local;

  if ($local) return file_get_contents("trello-data.json");

  $trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
    "/cards?fields=name,idList,url&key=" . getenv("trello-key") . 
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

  $url = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Metric?view=Has%20incident" . $c;

  return get_airtable($url);
}



$cardsjson = get_trello();
$cards = json_decode($cardsjson);
$cq_url_dictionary = array();




$count = 0;
$tealiumfixed = 0;
echo "<b>Incidents:</b><table>";
echo "<thead><td>CQ#</td><td>Incident</td></thead>";

foreach ($cards as $card) {
  if (in_array($card->{'idList'}, $trello_active_lists)) {
    if ($card->{'idList'} == $trello_list_fixed_tealium) $tealiumfixed++;
    $name = $card->{'name'};
    $cq = get_cq($name);
    if (isset($card->{'url'})) {
      $url = $card->{'url'};
      if (strrpos($url, '/') !== false) {
        $comp = substr($url, 0, strrpos($url, '/'));
        $cq_url_dictionary[$comp] = $cq;
      }
      echo "<tr><td><a href='$url' target='_blank'>$cq</a></td><td>$name<td><tr>";
    }
    else
      echo "<tr><td>$cq</td><td>$name<td><tr>";

    $count++;
  }
}

if ($verbose) echo "</table>Total count: <b>$count</b> of which <b>$tealiumfixed</b> are fixed in tealium.<br><br><br>";



echo "<b>Affected metrics:</b><table>";
echo "<thead><td>Metric</td><td>Incidents</td></thead>";

$offset = '';
$count = 0;
do {
  $res = get_airtable_metrics($offset);
  $json = json_decode($res);
  $recs = $json->{'records'};
  for ($i = 0 ; $i < count($recs) ; $i++) {
    $rec = $recs[$i];
    if (isset($rec->{'fields'}->{'Trello links'})) {
      $count++;
      $m = $rec->{'fields'}->{'Name'};
      $ml = 'https://airtable.com/tblxdijsMWz8Q10vC/viwgalMoImhjKJbwv/' . $rec->{'id'};
      echo "<tr><td><a href='$ml' target='_blank'>$m</a></td><td>";
      $links = $rec->{'fields'}->{'Trello links'};
      $l = explode(',', $links);
      foreach ($l as $a) {
        $a = trim($a);
        $cq = find_cq($a);
        echo "<a href='$a' target='_blank'>$cq</a>, ";
      }
      echo '</td></tr>';
    }
  }

  if (isset($json->{"offset"})) $offset = $json->{"offset"};

} while (isset($json->{"offset"}));

if ($verbose) echo "</table>Total count: <b>" . $count . "</b><br><br><br>";



echo 'Done!';

?>
  </body>
</html>
