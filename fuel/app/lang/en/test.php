<?php
return array(
	'auth_model_group' => 
	array(
		'name' => 'Group name',
	),
	'auth_model_role' => 
	array(
		'name' => 'Role name',
		'filter' => 'Special permissions',
		'permissions' => 
		array(
			'' => 'None',
			'A' => 'Allow all access',
			'D' => 'Deny all access',
			'R' => 'Revoke assigned permissions',
		),
	),
	'auth_model_user' => 
	array(
		'name' => 'User name',
		'email' => 'Email address',
		'password' => 'Password',
		'group_id' => 'Group',
	),
	'test' => 'This is a test',
	'test2' => 'This is another test',
);
