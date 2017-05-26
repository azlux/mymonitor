#! /bin/bash

# Temperature of my CPU
cpu_temp=$(cat /sys/class/thermal/thermal_zone0/temp |awk '{printf("%0.f",$1/100)}')

# Ping my mumble server
ping1=$(ping -c 1 62.210.107.194 | grep -Po 'time=\K[\d.]+')

# The CPU load (average 5min)
load_cpu=$(cat /proc/loadavg | awk '/[0-9]/ {print $2*100}')

# Add thoses data into the "One Day Data" table
mysql -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -D sensors -e "INSERT INTO data_one_day(date,temperature_cpu,ping1,load_cpu) VALUES (NOW(), $cpu_temp,$ping1,$load_cpu)"


# Test if there are data older than 1 day+1hour
t=$(mysql -ss -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -D sensors -e "SELECT count(*) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR")

if [ $t != 0 ];then
	# Calculate the avrage of the hour (from data older than 1day)
	tp_avg_date=$(mysql -ss -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -ss -D sensors -e "SELECT AVG(UNIX_TIMETAMP(date)) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
	tp_avg_tmp_cpu=$(mysql -ss -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -ss -D sensors -e "SELECT AVG(temperature_cpu) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
	tp_avg_ping1=$(mysql -ss -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -ss -D sensors -e "SELECT AVG(ping1) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
	tp_avg_load_cpu=$(mysql -ss -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -ss -D sensors -e "SELECT AVG(load_cpu) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
	
	# transform it into integer
	avg_date=${tp_avg_date%.*}
	avg_tmp_cpu=${tp_avg_tmp_cpu%.*}
	avg_ping1=${tp_avg_ping1%.*}
	avg_load_cpu=${tp_avg_load_cpu%.*}
	
	# Add the data into the database	
	mysql -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -D sensors -e "INSERT INTO data(date,temperature_cpu,ping1,load_cpu) VALUES (FROM_UNIXTIME($avg_date),$avg_tmp_cpu,$avg_ping1,$avg_load_cpu)"
	# Rmove the old data from the "One Day Data" table
	mysql -u sensors -pth61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5 -ss -D sensors -e "DELETE FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY"
fi
