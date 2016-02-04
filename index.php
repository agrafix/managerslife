<?php
require "include.php";

Framework::loadController((isset($_GET['q']) ? $_GET['q'] : ''));
?>