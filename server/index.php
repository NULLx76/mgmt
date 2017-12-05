<!DOCTYPE html>
<html>
<head>
    <title>mgmt</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>
<h1>Inventory</h1>
<?php
error_reporting(E_ALL);

function int2macaddress($int){
    $hex = base_convert($int, 10, 16);
    while (strlen($hex) < 12)
        $hex = '0' . $hex;
    return strtoupper(implode(':', str_split($hex, 2)));
}

$mysqli = new mysqli("mariadb", "mgmt", "mgmt_pass", "mgmt", 3306);

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$sql = <<<EOT
SELECT mgmt.inventory.MAC, INET_NTOA(mgmt.inventory.ip), mgmt.inventory.Hostname, mgmt.inventory.OS,
mgmt.updates.`pending`, mgmt.updates.security, mgmt.updates.reboot_required
FROM mgmt.inventory 
INNER JOIN mgmt.updates 
ON mgmt.inventory.MAC=mgmt.updates.MAC;
EOT;

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
            <th>Pending Updates</th>
            <th>Pending Security Updates</th>
            <th>Reboot Required</th>
            <th>Update Cache</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($resultset as $index => $row) {
            $column_counter = 0;
            ?>
            <tr>
                <?php for ($i = 0; $i < count($columns); $i++): ?>
                    <td>
                        <?php
                        /* If it is the MAC Address column format it accordingly
                           Else just print the contents */
                        if ($column_counter == 0) {
                            echo int2macaddress($row[$columns[$column_counter++]]);
                        } elseif ($column_counter == 6){
                            if($row[$columns[$column_counter]]){
                                $column_counter++;
                                echo "Reboot Now: <a href=\"#\" class=\"reboot\" onclick=\"action(" . $row[$columns[0]] . ",'reboot');\"><img src=\"img/restart16x16.png\" alt=\"reboot\" title=\"Reboot\"></a>";
                            }else{
                                echo "Not required";
                                $column_counter++;
                            }
                        }else {
                            echo $row[$columns[$column_counter++]];
                        }
                        ?>
                    </td>
                <?php endfor; ?>
                <td>
                    <a href="#" onclick="action(<?php echo $row[$columns[0]];?>,'update');"><img src="img/update.png" alt="Update Package Cache" title="Update Package Cache"></a>
                </td>
            </tr>
        <?php } ?>

        </tbody>
    </table>

<?php } else { ?>
    <h4> Information Not Available </h4>
<?php } ?>
<script type="text/javascript">
    //TODO: Nice push notification for the result
    function action(mac, action) {
        if(confirm("Are you sure you want to " + action + "?")) {
            $.ajax({
                url: "handler.php",
                type: "GET",
                data: {
                    mac: mac,
                    action: action
                },
                success: function (result) {
                    console.log(result)
                    if (action === "update")
                        location.reload(true);
                }
            });
        }else {
            //cancelled
        }
    }
</script>

</body>

</html>
