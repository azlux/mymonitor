# mymonitor
Personnal monitor used on raspberry

Sensors -> MySQL -> php page
I log one ping, the temperature of my CPU and the 5min load.
This project can be customized, but you will need to changes variables liked to the data. Some code are hard coded.

## MySQL
I use a database named "sensors", MySQL command to create it are in the BDD file

## Crontab
*/10   *  *  *  * /home/<home directory>/mymonitor/sensors.sh