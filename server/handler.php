<?php
/**
 * Created by IntelliJ IDEA.
 * User: victor
 * Date: 12/4/17
 * Time: 1:01 PM
 */

$key = "123456";
$port = 6667;

$mysqli = new mysqli("mariadb", "mgmt", "mgmt_pass", "mgmt", 3306);

function str_starts_with($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}
function str_ends_with($haystack, $needle)
{
    return strrpos($haystack, $needle) + strlen($needle) === strlen($haystack);
}

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

if($_GET['action'] != null && $_GET['mac'] != null){
    //TODO: Send reboot request to python
    $mac = $mysqli->real_escape_string($_GET['mac']);
    $action = $mysqli->real_escape_string($_GET['action']);

    $sql = "SELECT INET_NTOA(mgmt.inventory.ip) FROM mgmt.inventory WHERE MAC=" . $mac . " LIMIT 1";
    $sql_result = $mysqli->query($sql);

    $ip = mysqli_fetch_row($sql_result)[0];


    $msg = $key . ":" . $action;

    $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Failed to create socket");
    $result = socket_connect($socket, $ip, $port) or die("Could not connect to server\n");
    socket_write($socket,$msg, strlen($msg)) or die ("Could not send data to server\n");
    $socket_result = socket_read ($socket, 1024) or die("Could not read server response\n");
    $socket_result_a = explode(':', $socket_result);
    if( $socket_result_a[0] == "err" || $socket_result_a[0] == $key){
        echo $socket_result_a[1];
    }else{
        echo "Wrong PHP Api Key";
    }

}