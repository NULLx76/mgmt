#!/usr/bin/python3
# -*- coding: utf-8 -*-

import os
from platform import system
import subprocess

import sys
from pymysql import connect
import uuid
import socket
from _thread import *

# API Key
key = "123456"

# Listening port
PORT = 6667

# Get Hardware UUID (Mac address in decimal)
uuid = uuid.getnode()

# Get ip
socket_get_ip = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
socket_get_ip.connect(('80.60.83.220', 1))
ip = socket_get_ip.getsockname()[0]

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
    db_conn = connect(host='mariadb', port=3306, user='mgmt', db='mgmt', password='mgmt_pass')

    try:
        with db_conn.cursor() as cursor:
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

        db_conn.commit()
    except Exception as e:
        print(e)

    db_conn.close()
    print("Database Init Complete")

# Check for package upgrades
def check_updates():
    # Connect to database
    db_conn = connect(host='mariadb', port=3306, user='mgmt', db='mgmt', password='mgmt_pass')

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
            with db_conn.cursor() as cursor:
                sqlUpdateUpdates = "UPDATE mgmt.updates " \
                                   "SET pending = %s, security = %s, packages = %s, fullcache = %s, reboot_required = %s " \
                                   "WHERE MAC = %s"

                cursor.execute(sqlUpdateUpdates,
                               (str(updates), str(security_updates), str(pkgs), str(fullcache), reboot_required, str(uuid)))

            db_conn.commit()
        except Exception as e:
            print(e)

    else:
        print("Sorry this platform is not supported")

    db_conn.close()
    print("Updates Checked!")

def client_thread(conn):
    while True:
        data = conn.recv(1024)
        data_d = data.decode().split(":")

        reply = "err"

        if key in data_d[0]:
            if data_d[1] == "reboot":
                print("Rebooted")
                reply = key + ":" + "Reboot Successful"
            elif data_d[1] == "update":
                check_updates()
                reply = key + ":" + "Updated Successful"
        else:
            reply = "err" + ":" + "Wrong Python API Key"

        if not data: break
        conn.sendall(reply.encode())
    conn.close()

if __name__ == "__main__":
    database_init()
    check_updates()
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        s.bind(('', PORT))
    except socket.error:
        print (socket.error)
        sys.exit()
    s.listen(10)

    while True:
        conn, addr = s.accept()
        start_new_thread(client_thread, (conn,))

    s.close()