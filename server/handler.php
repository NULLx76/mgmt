<?php
/**
 * Created by IntelliJ IDEA.
 * User: victor
 * Date: 12/4/17
 * Time: 1:01 PM
 */

if($_GET['action'] == "reboot" && $_GET['mac'] != null){
    echo $_GET['mac'];
    //TODO: Send reboot request to python

}