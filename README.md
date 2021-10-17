Quick Start
===========

* Basic usage
```bash
    $ php -f apache-error-log-analyzer.php /var/log/httpd/error.log
```

* reformat the output with xmllint 
```bash
    $ php -f apache-error-log-analyzer.php /var/log/httpd/error.log | xmllint --reformat -
```

* The wrapper script is useful for cron jobs
```bash
    $ apache-error-log-analyzer-wrapper.sh /var/log/httpd/error.log
```
