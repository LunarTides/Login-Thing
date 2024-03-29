<?php

$prevent_temp_logout = true;
require_once "../other/functions.php";
$prevent_temp_logout = false;
require_once "../other/dbh.php";

if (!isset($_POST["submit"]) || !$settings->enable_reset_pass) {
    header("location: ../../login");
    exit();
}

session_start();

$uid = $_POST["uid"];
$uidRepeat = $_POST["uidRepeat"];

if (password_verify($_POST["token"], getTable($conn, "passwordtokens", ["id", $_SESSION["ptid"]])["token"])) {

    if ($uid !== $uidRepeat) {
        header("location: ../../resetpass?t=" . $_POST["token"] . "&error=pwdmatch");
        exit();
    }

    if (empty($uid) || empty($uidRepeat)) {
        header("location: ../../resetpass?t=" . $_POST["token"] . "&error=emptyinput");
        exit();
    }
    if (!preg_match("/^[a-zA-Z0-9&-_., ]*$/", $uid)) {
        header("location: ../../resetpass?t=" . $_POST["token"] . "&error=invaliduid");
        exit();
    }
    if (uidExists($conn, $uid, "") !== false) {
        header("location: ../../resetpass?t=" . $_POST["token"] . "&error=usernametaken");
        exit();
    }

    updateTable($conn, "users", "uid", $uid, ["id", getTable($conn, "passwordtokens", ["id", $_SESSION["ptid"]])["userid"]]);
    deleteTable($conn, "passwordtokens", ["id", $_SESSION["ptid"]]);

    if (isset($_SESSION["tempacc"]) && $_SESSION["tempacc"]) {
        deleteTable($conn, "modhelpgroups", ["id", $_SESSION["modhelpgroup"]]);
        deleteTable($conn, "modhelpmessages", ["groupId", $_SESSION["modhelpgroup"]]);
    }

    $_SESSION["ptid"] = null;

} else {
    header("location: ../../login?error=invalidtoken");
    exit();
}

header("location: ../../login?error=none");