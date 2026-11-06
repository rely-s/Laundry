@echo off
echo Starting XAMPP MariaDB on port 3307...
"C:\xampp\mysql\bin\mysqld.exe" --port=3307 --datadir="C:\xampp\mysql\data_laundry" --skip-innodb-doublewrite

