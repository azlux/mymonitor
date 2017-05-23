#! /bin/bash
cpu_temp=$(cat /sys/class/thermal/thermal_zone0/temp |awk '{printf("%0.f",$1/100)}')
date=$(date +%s)
storage_total=$(df -h | grep /dev/root | awk '/[0-9]/ {print $5}'| sed 's/%//g')
storage_log=$(df -h | grep log2ram | awk '/[0-9]/ {print $5}'| sed 's/%//g')
ping1=$(ping -c 1 62.210.107.194 | sed -ne '/.*time=/{;s///;s/\..*//;p;}')
load_cpu=$(cat /proc/loadavg | awk '/[0-9]/ {print $2*100}')

mysql -u sensors -p**************** -D sensors -e "INSERT INTO temperature(date,temperature_cpu,ping1,load_cpu) VALUES ($date, $cpu_temp,$ping1,$load_cpu)"

