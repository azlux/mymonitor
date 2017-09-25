
<?php
include("bdd.php");

function average($data,$start,$len) {
	$tp = array_slice($data,$start,$len);
	$avg = array_sum($tp)/count($tp);
	return round($avg);
}

function avg_data_delta($dataTime,$data,$delta) {
	$avg_dataTime = array();
    $avg_data = array();
	
    $first_pos = 0;
	for ($i = 0; $i < count($dataTime); $i++) {
 		if ((abs($dataTime[$i]-$dataTime[$first_pos])) > ($delta*1000)) {
            array_push($avg_dataTime, average($dataTime, $first_pos, $i-$first_pos));
			array_push($avg_data, average($data, $first_pos, $i-$first_pos));
            
            $first_pos = $i+1;
		}
	}
	return array($avg_data);
}

$xlabel = "hour";

if (!empty($_POST['range']) && $_POST['range'] != "day") {
	if ($_POST['range'] == "week") {
		$req = $bdd->query('SELECT * FROM data WHERE date > NOW() - INTERVAL 1 WEEK');
	        $xlabel = "day";
        }
	elseif ($_POST['range'] == "month") {
		$req = $bdd->query('SELECT * FROM data WHERE date > NOW() - INTERVAL 1 MONTH');
	        $xlabel = "week";
        }
	elseif ($_POST['range'] == "year") {
		$req = $bdd->query('SELECT * FROM data WHERE date > NOW() - INTERVAL 1 YEAR');
	        $xlabel = "month";
        }
	else {
		exit("LoL Nope");
	}
}
else {
	$req = $bdd->query('SELECT * FROM data_one_day WHERE date > NOW() - INTERVAL 1 DAY');
}

# My Disk utilization
$diskTotal = exec("df -h | grep /dev/root | awk '/[0-9]/ {print $5}'| sed 's/%//g'");
# My log2ram disk utilization
$diskLog = exec("df -h | grep log2ram | awk '/[0-9]/ {print $5}'| sed 's/%//g'");

$dataTime = array();
$all_data = array();

# Push every data into an array
while ($temp = $req->fetch(PDO::FETCH_ASSOC)) {
    array_push($dataTime, floatval(strtotime($temp['date']))*1000);
    $all_data[0][]= round($temp['temperature_cpu'])/10;
    $all_data[1][]= round($temp['temperature_room'])/10;
    $all_data[2][]= $temp['ping1'];
    $all_data[3][]= $temp['ping2'];
    $all_data[4][]= $temp['load_cpu'];
    $all_data[5][]= $temp['ram'];
}

/* Average for week/month/year data*/
if (!empty($_POST['range'])) {
    if ($_POST['range'] == "month" && $_POST['range'] == "year") {
        if ($_POST['range'] == "month") {
            $delta = 5*60*60;
        }
        elseif ($_POST['range'] == "year") {
            $delta = 24*60*60;
        }
        $dataTime=avg_data_delta($dataTime,$dataTime,$delta);
        $i=0;
        foreach ($all_data as $data) {
            $all_data[$i]=avg_data_delta($dataTime,$data,$delta);
            $i++;
        }
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
    <header><h3>Temperature</h3></header>
    <div id="temp" style="height: 250px;"></div>
    <header><h3>Ping</h3></header>
    <div id="ping" style="height: 250px;"></div>
    <header><h3>CPU Load (5min)</h3></header>
    <div id="loadCpu" style="height: 250px;"></div>
    <script>
    
    new Morris.Line({
        element: 'temp',
        data:[<?php
	    for ($i = 0; $i<count($dataTime); $i++) {
		echo '{ time: ' . $dataTime[$i] . ', value1:' . $all_data[0][$i] .', value2:' . $all_data[1][$i] . ' },';
            }
	?>],
        xkey: 'time',
        ykeys: ['value1','value2'],
        labels: ['CPU','Room'],
        hideHover:true,
        pointSize:1,
        yLabelFormat : function (y) { return y.toString() + '°C'; },
        xLabels:'<?php echo $xlabel ?>'
        });
        
    new Morris.Line({
        element: 'ping',
        data:[<?php
	    for ($i = 0; $i<count($dataTime); $i++) {
		echo '{ time: ' . $dataTime[$i] . ', value1:' . $all_data[2][$i].', value2:' . $all_data[3][$i] . ' },';
	    }
	?>],
        xkey: 'time',
        ykeys: ['value1','value2'],
        labels: ['crystalyx','linuxgaming'],
        hideHover:true,
        pointSize:1,
        yLabelFormat : function (y) { return y.toString() + ' ms'; },
        xLabels:'<?php echo $xlabel ?>'
        });
        
    new Morris.Line({
        element: 'loadCpu',
        data:[<?php
	    for ($i = 0; $i<count($dataTime); $i++) {
		echo '{ time: ' . $dataTime[$i] . ', value1:' . $all_data[4][$i] . ', value2:' . $all_data[5][$i] . ' },';
	    }
	?>],
        xkey: 'time',
        ykeys: ['value1','value2'],
        labels: ['Load','Ram'],
        hideHover:true,
        pointSize:1,
        xLabels:'<?php echo $xlabel ?>'
        });

    </script>
</body>
</html>
