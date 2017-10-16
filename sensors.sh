#! /bin/bash

declare -A values

ram_free=$(cat /proc/meminfo | grep  MemAvailable | awk '/[0-9]/ {print $2}')
ram_total=$(cat /proc/meminfo | grep  MemTotal | awk '/[0-9]/ {print $2}')

values=( ["temperature_cpu"]=$(cat /sys/class/thermal/thermal_zone0/temp |awk '{printf("%0.f",$1/100)}')
        ["temperature_room"]=$(sudo /home/dmichel/script/thermometer.py | awk '{printf("%0.f",$1*10)}')
        ["ping1"]=$(ping -c 1 <FQDN> | grep -Po 'time=\K[\d.]+')
        ["ping2"]=$(ping -c 1 <FQDN> | grep -Po 'time=\K[\d.]+')
        ["load_cpu"]=$(cat /proc/loadavg | awk '/[0-9]/ {print $2*100}')
        ["ram"]=$(((($ram_total - $ram_free)*100/$ram_total))) )


query="INSERT INTO data_one_day(date"
for name in "${!values[@]}"; do
    query+=","
    query+=$name
done
query+=") VALUES (NOW()"
for name in "${!values[@]}"; do
    query+=",'"
    query+=${values[$name]}
    query+="'"
done
query+=")"

# Add thoses data into the "One Day Data" table
mysql -u sensors -p************ -D sensors -e "$query"

sleep 1
# Test if there are data older than 1 day+1hour
t=$(mysql -ss -u sensors -p************ -D sensors -e "SELECT count(*) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY - INTERVAL 1 HOUR")

if [ $t != 0 ];then
        # Calculate the average of the hour (from data older than 1day)
        tp_avg_date=$(mysql -ss -u sensors -p************ -ss -D sensors -e "SELECT AVG(UNIX_TIMESTAMP(date)) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
        avg_date=${tp_avg_date%.*}
	
        query="INSERT INTO data(date"

        for name in "${!values[@]}"; do
            query+=","
            query+=$name
        done

        query+=") VALUES (FROM_UNIXTIME('$avg_date')"
        
        for name in "${!values[@]}"; do
            query+=",'"
            tp=$(mysql -ss -u sensors -p************ -ss -D sensors -e "SELECT AVG(${values[$name]}) FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY")
            query+=${tp%.*}
            query+="'"
        done

	# Add the data into the database	
	mysql -u sensors -p************ -D sensors -e "$query"
	#Remove the old data from the "One Day Data" table
	mysql -u sensors -p************ -ss -D sensors -e "DELETE FROM data_one_day WHERE date < NOW() - INTERVAL 1 DAY"

fi
