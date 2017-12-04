#!/usr/bin/python3
# -*- coding: utf-8 -*-

import os
from platform import system
import subprocess
from pymysql import connect
import uuid
import socket

# Get Hardware UUID (Mac address in decimal)
uuid = uuid.getnode()

# Get ip
s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.connect(('80.60.83.220', 1))
ip = s.getsockname()[0]

# Get Hostname
hostname = socket.gethostname()

# Get Operating System
if system() == "Linux":
    OperatingSystem = subprocess.check_output(["lsb_release", "-ds"]).decode()
    OperatingSystem = OperatingSystem.rstrip()  # removes new lines
else:
    OperatingSystem = "Other"


# Check if system exists in DB, if not the case then it will add it
def database_init():
    # Connect to the database
    conn = connect(host='mariadb', port=3306, user='mgmt', db='mgmt', password='mgmt_pass')

    try:
        with conn.cursor() as cursor:
            # Inventory Table
            sqlCheckInventory = "SELECT 1 from mgmt.inventory WHERE MAC=%s LIMIT 1"
            cursor.execute(sqlCheckInventory, str(uuid))

            # If not in the inventory table add it
            if cursor.fetchone() is None:
                print("Adding to inventory")
                sqlAddToInventory = "INSERT INTO mgmt.inventory (MAC, IP, Hostname,OS) VALUES(%s, INET_ATON(%s), %s, %s)"
                cursor.execute(sqlAddToInventory, (str(uuid), str(ip), hostname, OperatingSystem))

            # Updates Table
            sqlCheckUpdates = "SELECT 1 from mgmt.updates WHERE MAC=%s LIMIT 1"
            cursor.execute(sqlCheckUpdates, str(uuid))

            # If not in the Updates table add it
            if cursor.fetchone() is None:
                print("Adding to Updates")
                sqlAddToUpdates = "INSERT INTO mgmt.updates (MAC) VALUES(%s)"
                cursor.execute(sqlAddToUpdates, (str(uuid)))

        conn.commit()
    except Exception as e:
        print(e)

    conn.close()
    print("Database Init Complete")

# Check for package upgrades
def check_updates():
    # Connect to database
    conn = connect(host='mariadb', port=3306, user='mgmt', db='mgmt', password='mgmt_pass')

    if "Ubuntu" in OperatingSystem:
        import ubuntu_apt

        fullcache = ubuntu_apt.get_update_packages()
        updates = len(fullcache)
        security_updates = len([x for x in fullcache if x.get('security')])

        pkgs = []
        for pkg in fullcache:
            pkgs.append(pkg.get('name'))

        reboot_required = os.path.isfile("/var/run/reboot-required")

        # Pushes data to the mysql database
        try:
            with conn.cursor() as cursor:
                sqlUpdateUpdates = "UPDATE mgmt.updates " \
                                   "SET pending = %s, security = %s, packages = %s, fullcache = %s, reboot_required = %s " \
                                   "WHERE MAC = %s"

                cursor.execute(sqlUpdateUpdates,
                               (str(updates), str(security_updates), str(pkgs), str(fullcache), reboot_required, str(uuid)))

            conn.commit()
        except Exception as e:
            print(e)

    else:
        print("Sorry this platform is not supported")

    conn.close()
    print("Updates Checked!")

if __name__ == "__main__":
    database_init()
    check_updates()


