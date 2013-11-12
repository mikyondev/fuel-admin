<?php
return array(
	'_root_'  => 'default/index',  // The default route
    'dashboard'  => 'default/index',  // The default route
	'_404_'   => 'welcome/404',    // The main 404 route
    'callback'=> 'default/callback',
    'oauth/google/oauth2callback' => 'default/callback',
	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),
);