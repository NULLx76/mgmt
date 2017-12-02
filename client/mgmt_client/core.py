#!/usr/bin/python3
import platform
import subprocess
import pymysql as mysql
import uuid
import socket

uuid = uuid.getnode()

# Get ip
s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.connect(('80.60.83.220', 1))
ip = s.getsockname()[0]

# Get Hostname
hostname = socket.gethostname()

# Get Operating System
if platform.system() == "Linux":
    os = subprocess.check_output(["lsb_release", "-ds"]).decode()
else:
    os = "Other"

# Connect to Database
conn = mysql.connect(host='mariadb', port=3306, user='mgmt', db='mgmt', password='mgmt_pass')

# Check if in Database
try:
    with conn.cursor() as cursor:
        # Inventory Table
        sqlCheckInventory = "SELECT * from mgmt.inventory WHERE MAC=%s"
        cursor.execute(sqlCheckInventory, str(uuid))

        if cursor.fetchone() is None:
            print("Adding to inventory")
            sqlAddToInventory = "INSERT INTO mgmt.inventory (MAC, IP, Hostname,OS) VALUES(%s, INET_ATON(%s), %s, %s)"
            cursor.execute(sqlAddToInventory, (str(uuid), str(ip), hostname, os))

        # Updates Table
        sqlCheckUpdates = "SELECT * from mgmt.updates WHERE MAC=%s"
        cursor.execute(sqlCheckUpdates, str(uuid))

        if cursor.fetchone() is None:
            print("Adding to Updates")
            sqlAddToUpdates = "INSERT INTO mgmt.updates (MAC) VALUES(%s)"
            cursor.execute(sqlAddToUpdates, (str(uuid)))

    conn.commit()
finally:
    conn.close()

# Check for package upgrades
if "Ubuntu" in os:
    import ubuntu_apt
    pkgs = ubuntu_apt.get_update_packages()
    updates = len(pkgs)
    security_updates = len([x for x in pkgs if x.get('security')])
else:
    print("Sorry this platform is not supported")
