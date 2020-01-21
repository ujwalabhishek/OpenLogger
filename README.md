# OpenLogger
Simple Logging Service API built with lumen framework

Author: Ujwal Abhishek (ujwalabhishek@gmail.com)

## About

OpenLogger is an easy-to-use [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
compliant logging service built using the lumen framework. It offers file based centralized logging service. The service exposes
read write and search capabilities via a restful API.

## Requirements

PHP 7.0 and above
Composer


## Basic Usage
clone the git repository https://github.com/ujwalabhishek/OpenLogger.git on your local machine running php7.0 and above

* open terminal on your desktop 
* change your terminal to point to the desired directory

    ```:/> cd /var/www```

* clone the repository 

    ```:/> git clone https://github.com/ujwalabhishek/OpenLogger.git;```
* cd to the cloned directory 

    ```:/> cd OpenLogger```

    ```:/> git checkout master```
* rename .env.example to .env
* now to run the application start the php inbuilt web server 
    ```:/> php -S localhost:8080 -t \public```

### Additional Options

OpenLogger supports additional options via constants in the .env fileHere's the full list:

Here's the full list:

| Option | Default | Description |
| ------ | ------- | ----------- |
| LOGDIRECTORY | customlogs | The directory within storage where logs will, be saved.
| LOGLEVEL | debug | Log threshold level
| DATEFORMAT | 'Y-m-d G:i:s.u' | The format of the date in the start of the log lone (php formatted) |
| FILEEXTENTION | 'txt' | The log file extension |
| FILENAME | [prefix][date].[extension] | Set the filename for the log file. **This overrides the prefix and extention options.** |
| FLUSHFREQUENCY | `false` (disabled) | How many lines to flush the output buffer after |
| PREFIX  | 'log_' | The log file prefix |
| LOGFORMAT | `false` | Format of log entries |
| APPENDCONTEXT | `true` | When `false`, don't append context to log entries |

### Log Formatting

The `logFormat` option lets you define what each line should look like and can contain parameters representing the date, message, etc.

When a string is provided, it will be parsed for variables wrapped in braces (`{` and `}`) and replace them with the appropriate value:

| Parameter | Description |
| --------- | ----------- |
| date | Current date (uses `dateFormat` option) |
| level | The PSR log level |
| level-padding | The whitespace needed to make this log level line up visually with other log levels in the log file |
| priority | Integer value for log level (see `$logLevels`) |
| message | The message being logged |
| context | JSON-encoded context |

#### Tab-separated

Same as default format but separates parts with tabs rather than spaces:

    $logFormat = "[{date}]\t[{level}]\t{message}";

#### Custom variables and static text

Inject custom content into log messages:

    $logFormat = "[{date}] [$var] StaticText {message}";

#### JSON

To output pure JSON, set `appendContext` to `false` and provide something like the below as the value of the `logFormat` option:

```
$logFormat = json_encode([
    'datetime' => '{date}',
    'logLevel' => '{level}',
    'message'  => '{message}',
    'context'  => '{context}',
]);
```

The output will look like:

    {"datetime":"2015-04-16 10:28:41.186728","logLevel":"INFO","message":"Message content","context":"{"1":"foo","2":"bar"}"}
    
#### Pretty Formatting with Level Padding

For the obsessive compulsive

    $logFormat = "[{date}] [{level}]{level-padding} {message}";

... or ...

    $logFormat = "[{date}] [{level}{level-padding}] {message}";

## API endpoints

logfile are just items. They're operated upon by methods , which are get, put, post, and live under `/v1/api/logfile`.

All endpoints are listed below, with required feilds and methods:

Endpoint  | Method | Description | Feilds
------|------------|---------|-------------
http://localhost:8080/api/v1/logfile/ | GET | lists all log files in the log directory |
http://localhost:8080/api/v1/logfile/ | PUT | writes log entries to the log file | errortype, message, context (array)
http://localhost:8080/api/v1/logfile/ | POST | writes log entries to the log file | errortype, message, context (array)
http://localhost:8080/api/v1/logfile/search | POST | search for log file based on Year, Month & Date. | year, month, day
http://localhost:8080/api/v1/logfile/read | POST | read log file contents based on file name. | filename


## Sample outputs
http://localhost:8080/api/v1/logfile/

    ``` 
    {
        "data": [
            {
                "fileName": "log_2020-01-15.txt",
                "fileExtention": "txt",
                "filePath": "/Users/ujwalabhishek/Sites/OpenLogger/storage/customlogs/log_2020-01-15.txt"
            },
            {
                "fileName": "syslog_2020-01-21.log",
                "fileExtention": "log",
                "filePath": "/Users/ujwalabhishek/Sites/OpenLogger/storage/customlogs/syslog_2020-01-21.log"
            },
            {
                "fileName": "log_2020-01-21.tlog",
                "fileExtention": "tlog",
                "filePath": "/Users/ujwalabhishek/Sites/OpenLogger/storage/customlogs/log_2020-01-21.tlog"
            },
            {
                "fileName": "log_2020-01-21.log",
                "fileExtention": "log",
                "filePath": "/Users/ujwalabhishek/Sites/OpenLogger/storage/customlogs/log_2020-01-21.log"
            },
            {
                "fileName": "log_2020-01-21.txt",
                "fileExtention": "txt",
                "filePath": "/Users/ujwalabhishek/Sites/OpenLogger/storage/customlogs/log_2020-01-21.txt"
            }
        ]
    }
    ```