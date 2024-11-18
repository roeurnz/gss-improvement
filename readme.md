Run Admin notifications script:

We can set a cronjob to automatically check data and send notifications
Use this command to cronjob
php report.php <notification_parameter>

- To daily mark employee that not come to office as absent we need to set a parameter to "daily-presence"<br/>
ex: php /var/www/report.php daily-presence
- To send report attendance of employee we can use "attendance" parameter<br/>
ex: php /var/www/report.php attendance daily  //to send report daily attendance<br/>
ex: php /var/www/report.php attendance weekly //to send report weekly attendance<br/>
ex: php /var/www/report.php attendance monthly //to send report monthly attendance
<br/>

Note:
All notifications and report will send to admin Telegram

<br/>

- dead-man-cron.php must add into cronjob if using dead-man feature to send user a task randomly. 
  <br/>better using every minutes cron for this file.

<br/> <br/> 
Sample Cron: <br />
0 17 * * * => to run at 17:00 everyday<br/>
0 0 * * 1 => to run at 00:00 on Monday<br/>
0 0 1 * * => to run at 00:00 on day-of-month 1<br/>
ref: <br/>
https://crontab.guru/

