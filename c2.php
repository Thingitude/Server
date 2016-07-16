<?php 
/* chart.php - display the hotspot prototype data
 * (c) Copyright Mark Stanley, June 2016
 *
 * Takes a TTN node ID as a parameter, 
 * gets its data from a MondoDB database populated by HotspotMq2Db.php
 * and displays it graphically.
 *
 * Node is currently a Raspberry Pi sending data over USB 
 * to a pre-release Things UNO.
 * Pi contains wifi, temperature, humidity and sound sensor.
 * Things UNO is an Arduino Leonardo and RN2483 LoraWAN radio.
 *
 */

$nodeId =isset($_GET['node']) ? $_GET['node'] : "0004A30B001B67C9";

//Mongodb configuration
$dbhost = 'localhost';
$dbname = 'thingithondb';
$m = new Mongo("mongodb://$dbhost");
$db = $m->$dbname;
$collection = $db->senseData;

// Search for latest record and populate necessary variables
$where = array( 'node' => $nodeId, 'msgData' =>array( '$exists' => true ));
$sort = array ( 'time' => -1);
$cursor = $collection->find($where)->sort($sort)->limit(1);
if($cursor->hasNext()) {
  $cursor->next();
  $currentRec=$cursor->current();
  if (count($currentRec['msgData']) == 0) {
    $msgData=$currentRec['msgData'];
  } else {
    $msgData = print_r($currentRec['msgData'],true);
  }
  $msgTime = isset($currentRec['time']) ? $currentRec['time'] : 'No record found';
  $mac = isset($currentRec['msgData']['mac']) ? $currentRec['msgData']['mac'] : 'null';
  $temperature = isset($currentRec['msgData']['t']) ? $currentRec['msgData']['t'] : 'null';
  $humidity = isset($currentRec['msgData']['h']) ? $currentRec['msgData']['h'] : 'null';
} else {
  $msgData='No record found';
  $msgTime='No record found';
  $mac='null';
  $temperature='null';
  $humidity='null';
}

//echo "Mac - $mac, Temp - $temperature, Humidity - $humidity, Message - $msgData \n";

//Now lets get the MAC data from the last 10 records for the sparklines
$where = array( 'node' => $nodeId, 'msgData.mac' =>array( '$exists' => true ));
$sort = array ( 'time' => -1);
$cursor = $collection->find($where)->limit(20);
$numRecs=$cursor->count();
$macRecs=array();
$i=0;
while ($cursor->hasNext()) {
  $cursor->next();
  $thisRec=$cursor->current();
  if($thisRec['msgData']['mac']!=null) {
    $macRecs[$i]=$thisRec['msgData']['mac'];
    $i++;
  }
}

//Now lets get the temperature data from the last 10 records for the sparklines
$where = array( 'node' => $nodeId, 'msgData.t' =>array( '$exists' => true ));
$sort = array ( 'time' => -1);
$cursor = $collection->find($where)->limit(20);
$numRecs=$cursor->count();
$temperatureRecs=array();
$i=0;
while ($cursor->hasNext()) {
  $cursor->next();
  $thisRec=$cursor->current();
  if($thisRec['msgData']['t']!=null) {
    $temperatureRecs[$i]=$thisRec['msgData']['t'];
    $i++;
  }
}

//Now lets get the humidity data from the last 10 records for the sparklines
$where = array( 'node' => $nodeId, 'msgData.h' =>array( '$exists' => true ));
$sort = array ( 'time' => -1);
$cursor = $collection->find($where)->limit(20);
$numRecs=$cursor->count();
$humidityRecs=array();
$i=0;
while ($cursor->hasNext()) {
  $cursor->next();
  $thisRec=$cursor->current();
  if($thisRec['msgData']['h']!=null) {
    $humidityRecs[$i]=$thisRec['msgData']['h'];
    $i++;
  }
}

//echo "Temperature: ", implode(",",$temperatureRecs), "\n";
//echo "Humidity: ", implode(",",$humidityRecs), "\n";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <title>Reading Hotspot</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/workshop.css" rel="stylesheet">

    <script src="js/jquery.js"></script>
    <script src="js/jquery.sparkline.js"></script>
    <script src="js/raphael-2.1.4.min.js"></script>
    <script src="js/justgage.js"></script>

</head>
<body>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="banner">
	<a href="http://thingitude.org"><img src="img/thingitude200x40tx.png"/></a>
        <h1>Reading Hotspot prototype</h1>
        <p>This is a simple site showing data gathered from the TTN API for the node identified in the URL.</p>
        <p>If you are interested in building the Things Network in your town or want to talk to us about our project please visit our website.</p>
        <p><a href="http://ttnreading.org">TTN Reading</a>  and  <a href="http://thingitude.com">Thingitude</a></p>
    </div>

    <div class="c-container">
        <!-- Example row of columns -->
        <div class="row-fluid span6 ">
            <div class="col-md-4 span3">
            </div>
            <div class="col-md-4 span6">
                <h2>People: <?php echo $mac; ?></h2>
                <div id="xMac"></div>
                <span class="dynamicsparkline" id='sp1'>Loading..</span>
            </div>
            <div class="col-md-4 span3">
            </div>
        </div>
        <div class="row-fluid span6 ">
            <div class="col-md-4 span6">
                <h2>Temperature</h2>
                <div id="gTemp"></div>
                <span class="dynamicsparkline" id='sp2'>Loading..</span>
            </div>
            <div class="col-md-4 span6">
                <h2>Humidity</h2>
                <div id="gHumidity"></div>
                <span class="dynamicsparkline" id='sp3'>Loading..</span>
            </div>
        </div>
    </div>
    <div class="infobox">
        <p>Node: <?php echo $nodeId; ?></p>
        <p id="msgData">Message: <?php echo $msgData; ?></p>
        <p id="range">Date/time: <?php echo $msgTime; ?></p>
        <?php echo"<form method='get' class='form-inline pull-right' action='".$_SERVER['PHP_SELF']."' >"; ?>
        <input type="text" class="input-small" id="node" name="node" value="<?php echo $nodeId; ?>" placeholder="Node ID">
        <button type="submit" class="btn" id="node_btn" value="node">Refresh</button>
        </form>
    </div>

    <div class="container control-group" id="nodeSelect">
    </div>


    <script>
      document.addEventListener('DOMContentLoaded',function() {
        $('#sp1').sparkline(<?php echo "[",implode(",",$macRecs),"]"; ?>, {type:'bar', chartRangeMin:'0', barColor:'#FF0088', negBarColor:'#4bacc6', barWidth:'15px', barSpacing:'2px', height:'150px'});
        $('#sp2').sparkline(<?php echo "[",implode(",",$temperatureRecs),"]"; ?>, {type:'bar', barColor:'#6666EE', negBarColor:'#4bacc6', barWidth:'5px', barSpacing:'2px', height:'50px'});
        $('#sp3').sparkline(<?php echo "[",implode(",",$humidityRecs),"]"; ?>, {type:'bar', barColor:'#6666EE', negBarColor:'#4bacc6', barWidth:'5px', barSpacing:'2px', height:'50px'});
        gMac = new JustGage({
            id: "gMac",
            value: <?php echo $mac; ?>,
            min: 0,
            gaugeWidthScale: 1,
            counter: true,
            hideInnerShadow: true
        });
        gTemp = new JustGage({
            id: "gTemp",
            value: <?php echo $temperature; ?>,
            min: -10,
            max: 40,
            gaugeWidthScale: 1,
            counter: true,
            hideInnerShadow: true
        });
        gHumidity = new JustGage({
            id: "gHumidity",
            value: <?php echo $humidity; ?>,
            min: 0,
            max: 100,
            gaugeWidthScale: 1,
            counter: true,
            hideInnerShadow: true
        });
      });
    </script>
</body>
</html>

