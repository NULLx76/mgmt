<!DOCTYPE html>
<html>
<head>
    <title>mgmt</title>
    <meta charset="UTF-8">

</head>
<body>
<h1>Inventory</h1>
<?php
error_reporting(E_ALL);

function int2macaddress($int)
{
    $hex = base_convert($int, 10, 16);
    while (strlen($hex) < 12)
        $hex = '0' . $hex;
    return strtoupper(implode(':', str_split($hex, 2)));
}

$mysqli = new mysqli("mariadb", "mgmt", "mgmt_pass", "mgmt", 3306);

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$sql = "SELECT mgmt.inventory.MAC,INET_NTOA(mgmt.inventory.ip),mgmt.inventory.Hostname,mgmt.inventory.OS, mgmt.updates.`pending`, mgmt.updates.security FROM mgmt.inventory INNER JOIN mgmt.updates ON mgmt.inventory.MAC=mgmt.updates.MAC;";

$result = $mysqli->query($sql);
$columns = array();
$resultset = array();


while ($row = mysqli_fetch_assoc($result)) {
    if (empty($columns)) {
        $columns = array_keys($row);
    }
    $resultset[] = $row;
}

if ($resultset > 0) {
    ?>
    <table class="table table-bordered">
        <thead>
        <tr class='info'>
            <th>MAC</th>
            <th>IP</th>
            <th>Hostname</th>
            <th>OS</th>
            <th>Pending Upgrades</th>
            <th>Security</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($resultset as $index => $row) {
            $column_counter = 0;
            ?>
            <tr class='success'>
                <?php for ($i = 0; $i < count($columns); $i++): ?>
                    <td> <?php
                        if ($column_counter == 0) {
                            echo int2macaddress($row[$columns[$column_counter++]]);
                        } else {
                            echo $row[$columns[$column_counter++]];
                        }
                        ?> </td>
                <?php endfor; ?>
            </tr>
        <?php } ?>

        </tbody>
    </table>

<?php } else { ?>
    <h4> Information Not Available </h4>
<?php } ?>
</body>
</html>