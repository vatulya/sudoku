<?php

$acl = new Zend_Acl();

// Roles

$guest      = new Zend_Acl_Role('GUEST');
$user       = new Zend_Acl_Role('USER');

$acl->addRole($guest);
$acl->addRole($user, $guest);

$acl->addResource(new Zend_Acl_Resource('sudoku'));

// Access rights
$acl->deny(null, null, null);

$acl->allow($guest, 'sudoku');

return $acl;
