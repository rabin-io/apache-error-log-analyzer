Useage
======

* Basic useage
    
     php -f apache-error-log-analyzer.php /var/log/httpd/error.log

* reformat the output with xmllint 

     php -f apache-error-log-analyzer.php /var/log/httpd/error.log | xmllint --reformat -

* The wrapper script is useful for cron jobs

     apache-error-log-analyzer-wrapper.sh /var/log/httpd/error.log
