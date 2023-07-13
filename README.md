# AutoDB - A Relational Database Assistant

AutoDB is a relational database assistant tool written in PHP. The primary goal of this tool is to provide a web interface for browsing and manipulating records in a MySQL relational database. AutoDB supports create, read, update, and delete operations (CRUD) in addition to advanced relational features that make working with data a breeze.

## Technologies Used

* [PHP](https://www.php.net/docs.php) The primary language in which the tool was written
* [AJAX](https://developer.mozilla.org/en-US/docs/Web/Guide/AJAX) AJAX via XMLHttpRequest for dynamic page updates
* [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
* [MySQL](https://www.mysql.com/)

## Setup

### Prerequisites

* A webserver with a PHP module enabled
* PHP >= 7.2 compiled with mysql support
* MySQL Installation

The following commands may prove useful and were used to successfully deploy this tool on an Azure VM instance running `Ubuntu 22.04.2 LTS`. (See guide [Here](https://www.digitalocean.com/community/tutorials/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-22-04) for more detailed instructions on setting up a LEMP stack).

#### Server Software Installation

```
$ sudo apt update
$ sudo apt install nginx
$ sudo ufw app list
$ sudo ufw status
$ sudo apt install mysql-server
$ sudo mysql_secure_installation
$ sudo apt install php8.1-fpm php-mysql
```

#### NGINX Configuration

Create a directory to server the application, and set ownership to a non-root user.

```
$ sudo mkdir /var/www/adb
$ sudo chown -R $USER:$USER /var/www/adb
```

As root, create a file at `/etc/nginx/sites-available/adb` and add the following contents to configure NGINX to serve a new domain called `adb`:

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

Now symlink the configuration file to the sites-enabled directory of your NGINX installation as follows:

```
$ sudo ln -s /etc/nginx/sites-available/adb /etc/nginx/sites-enabled/
$ sudo unlink /etc/nginx/sites-enabled/default
$ sudo systemctl reload nginx
```

Create a file at /var/www/adb/index.php with the following contents:

```
<?php phpinfo(); ?>
```

Ensure that you can now access the web server at `http://<public_ip_address>/`

You should see the PHP info page, if everything worked properly.

#### .htaccess Configuration

Due to the intentionally insecure nature of AutoDB, it is strongly advised that you configure basic authentication, at a minimum. This can be done by dropping an `.htaccess` file at the root directory of adb referencing a password file stored in a secure location.

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
+   auth_basic           "AutoDB Basic Authentication";
+   auth_basic_user_file /var/www/.htpasswd_adb;
}
```

Now restart NGINX using the following command:

```
$ sudo systemctl reload nginx
```

#### Database Setup

Verify that you can connect to the MySQL database using the following command. When prompted, enter the password that you entered during the `mysql_secure_installation` command.

```
$ mysql -u root -p
```

While logged in as root, create a database to hold the tables served by AutoDB.

```
mysql> CREATE DATABASE autodb;
```

 It's recommended that you create a non-root user to access your database from the application. In this case, we will create an `adb` user. Make sure to replace 'password' with a secure password of your choosing. In this case, as the both the application and the MySQL server are hosted on the same server, `localhost` access should suffice.

```
mysql> CREATE USER 'adb_demo'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';
```

Next, you'll need to grant access to the database you wish to serve using AutoDB. The following command grants access to 'adb' for the autodb_demo database created above.

```
mysql> GRANT ALL ON autodb.* TO 'adb_demo'@'localhost';
```

### AutoDB Installation

Clone the AutoDB git repository for this project by removing the existing index.php that was created, and cloning the repository into /var/www/adb.

```
/var/www/adb$ git clone git@github.com:gjnance/autodb.git .
```

### Configuration

Open `adb_config.php` in an editor and update the variables `MYSQL_USER` and `MYSQL_PASS` to `adb_demo` and the password provided above for the user.

```
define('MYSQL_USER', 'adb_user');
define('MYSQL_PASS', 'password');
```

AutoDB requires two tables to function, `autodb_rel` and `autodb_prefs`. `autodb_rel` is used by AutoDB to provide relational information for a better user experience (see [Report Generation](./README.md#report-generation])). These tables can be created using the SQL file [autodb.sql](./sql/autodb.sql).

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

## Usage

The remainder of this document assumes a functioning AutoDB installation with the tables from [demo.sql](./sql/demo.sql) loaded and populated with some entries. You can source the file as follows:

```
mysql> source ./sql/demo.sql
```

### Operations

#### SELECT Mode

When you first log into the UI, you should be presented with a simple drop-down asking you to select a database, if AUTODB_DB is not set in `adb_config.php`, or a table from the specified database if it has been.

demo1.png

Select the `contacts` database to see a list of 30 beloved comic book characters and their contact info (as provided by [ChatGPT-4](https://chat.openai.com/share/6aa8535b-e720-4aa3-85c8-2de16eef7dca))

demo2.png

#### INSERT Mode

From the `Action` drop-down at the top of the page, select INSERT to display a form. The form is automatically generated based on a DESCRIBE, and has features such as:

* Auto Increment columns cannot be provided
* Display required fields in red (determined by Null=NO)
* For relational columns, display a drop-down of choices pulled from an adjacent table rather than an integer (see [Relational Rules](./README.md#relational-rules])).

demo3.png

#### REPORT Mode

### Relational Rules

One of AutoDB's more useful features is the ability to id columns from relational columns (i.e., an ID in Table A which points to an entry in Table B). In the demo, you will have noticed that the `locality_id`, `region_id`, and `country_id` columns are displayed as integers, which is not terribly useful. Further, when inserting new data, you must know the proper `id` to specify from the adjoining table in order to link the two. AutoDB Relational Rules take the guesswork out of this by providing mappings between tables.

To link the `locality_id`, `region_id`, and `country_id` columns to their respective tables, create the following entries in the `autodb_rules` table:

demo4.png

#### Export Mode



### Report Generation

AutoDB provides a simple mechanism for creating custom reports or pages based on displayed table data. Reports are dynamically discovered by AutoDB, provided they follow the necessary naming conventions, and are a useful way to customize data presentation.

This is best illustration with an example. Given the table from the demo above, we will create a custom report to send back a JSON formatted file to the user's web browser for use outside of AutoDB.

The first step is to create the report PHP file, which should be named:

```
./reports/<database>.<table>.<report_name>.php
```

For this example, we are going to create `XXX`.

```
<?
// Start buffering output
ob_start();

if(isset($bInclude) && $bInclude) {
	echo "My Example Report";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo data instead of forcing a file download
$debug = 0;

$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
mysqli_select_db($adb_dblink, "gnanceco_demo");

$qDBTable = "gnanceco_demo.customers";
$qWhere = GetCachedVar($qDBTable, "where");
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = mysqli_query($adb_dblink, $query);
if(!$res)
	die(mysqli_error($adb_dblink));

$content = "Last Name, First Name, Email Address\n";

while($row = mysqli_fetch_assoc($res)) {
	$content .= "{$row['cst_name_last']},{$row['cst_name_first']},{$row['cst_email']}\n";
}

if($debug) {
	echo nl2br($content);
} else {
	header("Cache-Control: no-store, no-cache, must-revalidate, private");
	header("Pragma: no-cache");
	header("Content-Type: text/csv");
	header("Content-Length: " . strlen($content));
	header("Content-Disposition: attachment; filename=contacts.csv");
	echo $content;
}
?>

```