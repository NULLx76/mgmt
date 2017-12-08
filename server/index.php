<!DOCTYPE html>
<html>
<head>
    <title>mgmt</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" integrity="sha256-R91pD48xW+oHbpJYGn5xR0Q7tMhH4xOrWn1QqMRINtA=" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js" integrity="sha256-yNbKY1y6h2rbVcQtf0b8lq4a+xpktyFc3pSYoGAY1qQ=" crossorigin="anonymous"></script>


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
                                ?>
                                Required: <a href="#" class="reboot" onclick="action(<?php $row[$columns[0]] ?>,'reboot');"><img src="img/restart16x16.png" alt="reboot" title="Reboot Now"></a>
                        <?php
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

    <div class='update' style='display:none'>Successfully updated</div>
    <div class='reboot' style='display:none'>Successfully rebooted</div>

<?php } else { ?>
    <h4> Information Not Available </h4>
<?php } ?>
<!--suppress JSUnusedGlobalSymbols -->
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
                    console.log(result);
                    toastr["success"](result);
                    if(action === "update"){
                    setTimeout(function () {
                        location.reload(true);
                    }, 5000)}
                }
            });
        }else {
            //cancelled
        }
    }
</script>
</body>
</html>
