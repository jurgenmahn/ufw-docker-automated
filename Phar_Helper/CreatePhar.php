<?php

ini_set('phar.readonly ', 0);
$pharFile = '../bin/ufw-docker-automated.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

$p = new Phar($pharFile);  
$p->buildFromDirectory('../App/');
$p->setDefaultStub('main.php', '/main.php'); 
$p->delete('Config/local.json');
echo "$pharFile successfully created\n\n";