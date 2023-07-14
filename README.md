# AutoDB - A Relational Database Assistant

AutoDB is a relational database assistant tool written in PHP, JavaScript, and AJAX. The primary goal of this tool is to provide a web interface for browsing and manipulating records in a MySQL relational database. AutoDB supports create, read, update, and delete operations (CRUD) in addition to advanced relational features that make working with data a breeze.

## Technologies Used

* [PHP](https://www.php.net/docs.php)
* [AJAX](https://developer.mozilla.org/en-US/docs/Web/Guide/AJAX) / XMLHttpRequest for dynamic page updates
* [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
* [MySQL](https://www.mysql.com/)

## Setup

### Prerequisites

* A webserver with a PHP module enabled
* PHP >= 7.2 compiled with mysql support
* MySQL Installation

The following commands were used to successfully deploy this tool on an Azure VM instance running `Ubuntu 22.04.2 LTS`. (See guide [Here](https://www.digitalocean.com/community/tutorials/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-22-04) for more detailed instructions on setting up a LEMP stack).

### Server Software Installation

```
$ sudo apt update
$ sudo apt install nginx
$ sudo ufw app list
$ sudo ufw status
$ sudo apt install mysql-server
$ sudo mysql_secure_installation
$ sudo apt install php8.1-fpm php-mysql
```

### NGINX Configuration

Create a directory to server the application, and set ownership to a non-root user.

```
$ sudo mkdir /var/www/adb
$ sudo chown -R $USER:$USER /var/www/adb
```

As root, create a file at `/etc/nginx/sites-available/adb` and add the following to configure NGINX to serve a new domain called `adb`:

```
server {
    listen 80;
    server_name adb www.adb;
    root /var/www/adb;

    index index.html index.htm index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
     }

    location ~ /\.ht {
        deny all;
    }
}
```

Now symlink the configuration file to the sites-enabled directory of your NGINX installation, and unlink the default site, as follows:

```
$ sudo ln -s /etc/nginx/sites-available/adb /etc/nginx/sites-enabled/
$ sudo unlink /etc/nginx/sites-enabled/default
$ sudo systemctl reload nginx
```

Create a file at /var/www/adb/index.php with the following contents:

```
<?php phpinfo(); ?>
```

Ensure that you can now access the web server at `http://<public_ip_address>/`. You should see the PHP info page, if everything worked properly. This is also a good time to scroll through the output and ensure that mysqli support is present.

#### .htaccess Configuration

Due to the intentionally insecure nature of AutoDB, it is strongly advised that you configure basic authentication, at a minimum. This can be done by creating an `.htaccess` file at the root directory of adb referencing a password file stored in a secure location.

```
$ sudo apt install apache2-utils
$ sudo htpasswd -c /var/www/.htpasswd_adb adb_user
New password:
Re-type new password:
Adding password for user adb_user
```

Add the following to the existing `location /` section of `/etc/nginx/sites-enabled/adb`

```diff
    location / {
        try_files $uri $uri/ =404;
+       auth_basic           "AutoDB Basic Authentication";
+       auth_basic_user_file /var/www/.htpasswd_adb;
    }
```

Now restart NGINX.

```
$ sudo systemctl reload nginx
```

### MySQL Setup

Verify that you can connect to the MySQL database using the following command. When prompted, enter the password that you entered during the `mysql_secure_installation` command.

```
$ mysql -u root -p
```

While logged in as root, create a database to hold the tables served by AutoDB.

```
mysql> CREATE DATABASE autodb;
```

 It's recommended that you create a non-root user to access your database from the application. In this case, we will create a user called `adb_demo`. **Make sure to replace 'password' with a secure password of your choosing!** In this case, as both the application and the MySQL server are hosted on the same server, `localhost` access should suffice.

```
mysql> CREATE USER 'adb_demo'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';
```

Next, you'll need to grant access to the database you wish to serve using AutoDB. The following command grants access to `adb_demo` for the autodb_demo database created above.

```
mysql> GRANT ALL ON autodb.* TO 'adb_demo'@'localhost';
```

### AutoDB Installation

Clone the AutoDB git repository for this project by removing the existing index.php that was created, and cloning the repository into /var/www/adb.

```
/var/www/adb$ git clone git@github.com:gjnance/autodb.git .
```

### AutoDB Configuration

Open `adb_config.php` in an editor and update the variables `MYSQL_USER` and `MYSQL_PASS` to `adb_demo` and the password provided above for the user.

```
define('MYSQL_USER', 'adb_user');
define('MYSQL_PASS', 'password');
```

AutoDB requires two tables to be present, `autodb_rel` and `autodb_prefs`. The former is used by AutoDB to provide relational information for a better user experience (see [Report Generation](./README.md#report-generation])). The latter is used to provide a cache for table display variables such as LIMIT, ORDER, and WHERE clauses. Create the tables using [autodb.sql](./sql/autodb.sql).

```
mysql> use autodb;
mysql> source sql/autodb.sql
```

You should have two tables in your database at this point, `autodb_rel` and `autodb_prefs`.

```
mysql> show tables;
+------------------+
| Tables_in_autodb |
+------------------+
| autodb_prefs     |
| autodb_rel       |
+------------------+
2 rows in set (0.00 sec)
```

You should now be able to access AutoDB in your web browser, and should see the two autodb tables present.

## AutoDB Usage

The remainder of this document assumes a functioning AutoDB installation with the tables from [demo.sql](./sql/demo.sql) loaded and populated with data. Create the demo tables now.

```
mysql> source ./sql/demo.sql
```

### Relational Rules

One of AutoDB's most useful features is the ability to id columns from relational tables. This is useful when you have a column in a table which is an integer `id` that links to another table. In SELECT example above, the `locality_id`, `region_id`, and `country_id` columns are displayed as integers, which is not terribly useful. Further, when inserting new data, you must know the proper `id` to specify from the adjoining table in order to link the two. AutoDB Relational Rules take the guesswork out of this by providing mappings between tables.

To link the `locality_id`, `region_id`, and `country_id` columns to their respective tables, create the following entries in the `autodb_rules` table:

![AutoDB Example Rules](https://github.com/gjnance/autodb/assets/7406768/11bef4b4-d14a-471e-a447-a9c0f56b42c1)

The columns in autodb_rules are explained below.

| Column         | Description |
| -------------- | ------------- |
| adb_t1         | The table containing references to relational ids  |
| adb_t1_relcol  | The column in t1 containing the relational id |
| adb_t2         | The table containing the entries for the relational id |
| adb_t2_relcol  | The column in t2 containing the relational id |
| adb_t2_dspcol  | The column in t2 to be displayed |
| adb_t2_remhost | If t2 is a remote table, the host where t2 can be found |
| adb_t2_remuser | If t2 is a remote table, the username to use when connecting to the remote database |
| adb_t2_rempass | If t2 is a remote table, the password to use when connecting to the remote database |

With the relational rules in place, when displaying the `contacts` table now instead of seeing numeric values for `locality_id`, `region_id`, and `country_id`, you should see the values from the relational tables instead, displayed in italics to indicate that they are relational values.

Hovering over the relational entry will display the actual id contained in the column for that row.

![AutoDB Example Rules Demo](https://github.com/gjnance/autodb/assets/7406768/1c5d86a2-18a8-410e-91e7-c4cbcea2d24c)

### Operations

#### SELECT Mode

When you first log into the UI, you should be presented with a simple drop-down asking you to select a database, if AUTODB_DB is not set in `adb_config.php`, or a table from the specified database if it has been.

![SELECT Mode Demo Image 1](https://github.com/gjnance/autodb/assets/7406768/5488492e-c2f3-478c-bd4b-174749ee69fe)

Select the `contacts` database to see a list of 30 beloved comic book characters and their contact info (as provided by [ChatGPT-4](https://chat.openai.com/share/6aa8535b-e720-4aa3-85c8-2de16eef7dca)).

![SELECT Mode Demo Image 2](https://github.com/gjnance/autodb/assets/7406768/104b9dde-eeb1-4f2d-a0a2-3598f1f9a48c)

#### INSERT Mode

From the `Action` drop-down at the top of the page, select INSERT to display a form. The form is automatically generated using a `DESCRIBE` statement on the selected table, and has features such as:

* Disabling entry for Auto Increment columns
* Display required fields in red (determined by Null=NO)
* For relational columns, display a drop-down of choices pulled from an adjacent table rather than an integer (see [Relational Rules](./README.md#relational-rules])).

![INSERT Mode Demo Image 1](https://github.com/gjnance/autodb/assets/7406768/a5f007e3-9c07-45fa-ab90-6c19c63ff3b2)

#### EXPORT Mode

AutoDB supports exporting of displayed table data using the EXPORT Action. Simply pull down the drop-down and select EXPORT for a CSV file with the table's contents. Exporting data with a LIMIT, ORDER, or WHERE clause in place results in only those selected rows being exported, useful for generating simple sub-reports.

#### WHERE

AutoDB provides a WHERE text box in the action bar that can be used to refine the rows displayed to any valid MySQL query. Use the WHERE field to select contacts whose phone numbers begin with the area code `415`.

TODO: screenshot

#### REPORT Mode

AutoDB provides a simple mechanism for creating custom reports or pages based on displayed table data. Reports are dynamically discovered by AutoDB based on a filename convention and are a useful way to customize data presentation.

This is best illustrated with an example. Given the table from the demo above, we will create a custom report to send back a JSON formatted file of selected entries, taking LIMIT, ORDER, and WHERE into consideration, and send it back to the user's web browser.

The first step is to create the report PHP file, which should be named:

```
./reports/<database>.<table>.<report_name>.php
```

For this example, we will create a script at `./reports/autodb.contacts.export_json.php`. Copy and paste the following script into the file and save it.

```php
<?php

// If bInclude is set, simply echo the report title and exit.
if(isset($bInclude) && $bInclude) {
        echo "Export Contacts as JSON";
        exit(0);
}

// Include adb configuration and necessary functions
include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo data instead of forcing a file download
$debug = false;

// Connect to the database
$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
mysqli_select_db($adb_dblink, AUTODB_DB);

$qDBTable = "autodb.contacts";
$qWhere = GetCachedVar($qDBTable, "where");
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = mysqli_query($adb_dblink, $query);
if(!$res)
        die(mysqli_error($adb_dblink));

$content = "Last Name, First Name, Email Address\n";

// Array to hold all rows
$rows = array();

while($row = mysqli_fetch_assoc($res)) {
        //$content .= "{$row['cst_name_last']},{$row['cst_name_first']},{$row['cst_email']}\n";
        $rows[] = $row;
}

$json = json_encode($rows);

if($debug) {
        echo nl2br($json);
} else {
        header("Cache-Control: no-store, no-cache, must-revalidate, private");
        header("Pragma: no-cache");
        header("Content-Type: application/json");
        header("Content-Length: " . strlen($json));
        header("Content-Disposition: attachment; filename=contacts.json");
        echo $json;
}
?>
```

With the report in place, access the UI and select REPORTS from the `Action` drop-down. You should see the title of the report, `Export Contacts as JSON`, which you can now click, resulting in a JSON file containing the selected table data.