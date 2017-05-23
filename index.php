<?php
include("bdd.php");
$req = $bdd->query('SELECT * FROM temperature WHERE date > UNIX_TIMESTAMP(NOW())-86400');

$diskTotal = exec("df -h | grep /dev/root | awk '/[0-9]/ {print $5}'| sed 's/%//g'");
$diskLog = exec("df -h | grep log2ram | awk '/[0-9]/ {print $5}'| sed 's/%//g'");

$dailyTime=array();
$dailyTempCpu=array();
$dailyPing1=array();
$dailyLoadCpu=array();

while ($temp = $req->fetch(PDO::FETCH_ASSOC)) {
    array_push($dailyTime, intval($temp['date'])*1000);
    array_push($dailyTempCpu, intval($temp['temperature_cpu'])/10);
    array_push($dailyPing1, $temp['ping1']);
    array_push($dailyLoadCpu, $temp['load_cpu']);
}
?>

<html>
<head>
    <link rel="stylesheet" href="css/morris.css">
	<link rel="stylesheet" href="css/main.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
    <script>
    if (!window.jQuery) document.write('<script src="js/jquery.min.js"><\/script>');
    if (!window.Raphael) document.write('<script src="js/raphael-min.js"><\/script>');
    if (!window.Morris) document.write('<script src="js/morris.min.js"><\/script>');
    </script>

</head>
<body>
    <header>
    <h1>Sensors Raspberry</h1>
    </header>
    <div id="right" class="pourcentage">
        <?php echo "Disk usage : " .  $diskTotal . " %<br/>Log2Ram usage : " . $diskLog . " %"; ?>
    </div>
    <header><h3>CPU temperature</h3></header>
    <div id="temp" style="height: 250px;"></div>
    <header><h3>Ping Xéfir</h3></header>
    <div id="ping1" style="height: 250px;"></div>
    <header><h3>CPU Load (5min)</h3></header>
    <div id="loadCpu" style="height: 250px;"></div>
    <script>
    new Morris.Line({
        element: 'temp',
        data:[
        <?php
        for ($i = 0; $i<count($dailyTime); $i++) {
            echo '{ time: ' . $dailyTime[$i] . ', value:' . $dailyTempCpu[$i] . ' },';
        }
        ?>
        ],
        xkey: 'time',
        ykeys: ['value'],
        xLabels: '5min',
        labels: ['température'],
        hideHover:true
        });
    new Morris.Line({
        element: 'ping1',
        data:[
        <?php
        for ($i = 0; $i<count($dailyTime); $i++) {
            echo '{ time: ' . $dailyTime[$i] . ', value:' . $dailyPing1[$i] . ' },';
        }
        ?>
        ],
        xkey: 'time',
        ykeys: ['value'],
        xLabels: '5min',
        labels: ['ping'],
        hideHover:true
        });
    new Morris.Line({
        element: 'loadCpu',
        data:[
        <?php
        for ($i = 0; $i<count($dailyTime); $i++) {
            echo '{ time: ' . $dailyTime[$i] . ', value:' . $dailyLoadCpu[$i] . ' },';
        }
        ?>
        ],
        xkey: 'time',
        ykeys: ['value'],
        xLabels: '5min',
        labels: ['Load'],
        hideHover:true
        });

    </script>
</body>
</html>
