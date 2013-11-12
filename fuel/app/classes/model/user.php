<?php
class Model_User extends \Auth\Model\Auth_User
{


	public static function validate($factory)
	{
		$val = Validation::forge($factory);
		$val->add_field('username', 'Username', 'required|max_length[50]');
		$val->add_field('password', 'Password', 'required|max_length[255]');
		$val->add_field('group_id', 'Group Id', 'required|valid_string[numeric]');
		$val->add_field('email', 'Email', 'required|valid_email|max_length[255]');
		$val->add_field('last_login', 'Last Login', 'required|max_length[25]');
		$val->add_field('previous_login', 'Previous Login', 'required|max_length[25]');
		$val->add_field('login_hash', 'Login Hash', 'required|max_length[255]');
		$val->add_field('user_id', 'User Id', 'required|valid_string[numeric]');

		return $val;
	}

}
