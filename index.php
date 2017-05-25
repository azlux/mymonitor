<?php
include("bdd.php");

function average($data,$start,$stop) {
	$tp = array_slice($data,$start,$stop);
	$avg = array_sum($tp)/count($tp);
	return round($avg);
}
function avg_all_data($dataTime,$dataTempCpu,$dataPing1,$dataLoadCpu,$delta) {
	$first_pos = 0;
	$avg_dataTime = array();
	$avg_dataTempCpu = array();
	$avg_dataPing1 = array();
	$avg_dataLoadCpu = array();
	
	for ($i = 0; $i < count($dataTime); $i++) {
		if (abs($dataTime[$i]-$dataTime[$first_pos]) > $delta) {
			array_push($avg_dataTime, average($dataTime, $first_pos, $i+1));
			array_push($avg_dataTempCpu, average($dataTempCpu, $first_pos, $i+1));
			array_push($avg_dataPing1, average($dataPing1, $first_pos, $i+1));
			array_push($avg_dataLoadCpu, average($dataLoadCpu, $first_pos, $i+1));
			$first_pos = $i+1;
		}
	}
	return array($avg_dataTime,$avg_dataTempCpu,$avg_dataPing1,$avg_dataLoadCpu);
}

if (!empty($_POST['range']) && $_POST['range'] != "day") {
	if ($_POST['range'] == "week") {
		$req = $bdd->query('SELECT * FROM data WHERE date > UNIX_TIMESTAMP(NOW())-604800');
	}
	elseif ($_POST['range'] == "month") {
		$req = $bdd->query('SELECT * FROM data WHERE date > UNIX_TIMESTAMP(NOW())-2592000');
	}
	elseif ($_POST['range'] == "year") {
		$req = $bdd->query('SELECT * FROM data WHERE date > UNIX_TIMESTAMP(NOW())-31536000');
	}
	else {
		exit("LoL Nope");
	}
}
else {
	$req = $bdd->query('SELECT * FROM data_one_day WHERE date > UNIX_TIMESTAMP(NOW())-86400');
}

# My Disk utilization
$diskTotal = exec("df -h | grep /dev/root | awk '/[0-9]/ {print $5}'| sed 's/%//g'");
# My log2ram disk utilization
$diskLog = exec("df -h | grep log2ram | awk '/[0-9]/ {print $5}'| sed 's/%//g'");

$dataTime = array();
$dataTempCpu = array();
$dataPing1 = array();
$dataLoadCpu = array();

# Push every data into an array
while ($temp = $req->fetch(PDO::FETCH_ASSOC)) {
    array_push($dataTime, floatval($temp['date'])*1000);
    array_push($dataTempCpu, round($temp['temperature_cpu'])/10);
    array_push($dataPing1, $temp['ping1']);
    array_push($dataLoadCpu, $temp['load_cpu']);
}

/* Average for week/month/year data*/
if (!empty($_POST['range']) && $_POST['range'] != "day") {
	if ($_POST['range'] == "month") {
		list($dataTime,$dataTempCpu,$dataPing1,$dataLoadCpu) = avg_all_data($dataTime,$dataTempCpu,$dataPing1,$dataLoadCpu,18000);
	}
	elseif ($_POST['range'] == "year") {
		list($dataTime,$dataTempCpu,$dataPing1,$dataLoadCpu) = avg_all_data($dataTime,$dataTempCpu,$dataPing1,$dataLoadCpu,86400);
	}
}


?>

<html>
<head>
	<title>Sensors Azlux's Rasp</title>
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
    <link rel="stylesheet" href="css/morris.css">
	<link rel="stylesheet" href="css/main.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
    <script>
    if (!window.jQuery) document.write('<script src="js/jquery.min.js"><\/script>');
    if (!window.Raphael) document.write('<script src="js/raphael-min.js"><\/script>');
    if (!window.Morris) document.write('<script src="js/morris.min.js"><\/script>');
    
    setInterval(function() {
        $.ajax({
            url: location.href,
            cache: false,
            success: function(content) {
                $("body").html((new DOMParser()).parseFromString(content,"text/html").body.outerHTML);
        }});}, 1000*60*5);

    </script>

</head>
<body>
    <header>
    <h1>Sensors Raspberry</h1>
    </header>
    <div id="right" class="pourcentage">
        <?php echo "Disk usage : " .  $diskTotal . " %<br/><a href='https://github.com/azlux/log2ram'>Log2Ram</a> usage : " . $diskLog . " %"; ?>
    </div>
	<div id="left">
		<form action="." method="POST">
			<select name="range">
				<option value="day">Day</option>
				<option value="week">Week</option>
				<option value="month">Month</option>
				<option value="year">Year</option>
			</select>
		<input type="submit">
		</form>
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
        data:[<?php
				for ($i = 0; $i<count($dataTime); $i++) {
					echo '{ time: ' . $dataTime[$i] . ', value:' . $dataTempCpu[$i] . ' },';
				}
			?>],
        xkey: 'time',
        ykeys: ['value'],
        labels: ['température'],
        hideHover:true,
		pointSize:1
        });
    new Morris.Line({
        element: 'ping1',
        data:[<?php
				for ($i = 0; $i<count($dataTime); $i++) {
					echo '{ time: ' . $dataTime[$i] . ', value:' . $dataPing1[$i] . ' },';
				}
			?>],
        xkey: 'time',
        ykeys: ['value'],
        labels: ['ping'],
        hideHover:true,
		pointSize:1
        });
    new Morris.Line({
        element: 'loadCpu',
        data:[<?php
				for ($i = 0; $i<count($dataTime); $i++) {
					echo '{ time: ' . $dataTime[$i] . ', value:' . $dataLoadCpu[$i] . ' },';
				}
			?>],
        xkey: 'time',
        ykeys: ['value'],
        labels: ['Load'],
        hideHover:true,
		pointSize:1
        });

    </script>
</body>
</html>
