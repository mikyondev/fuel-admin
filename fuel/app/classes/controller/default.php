<?php



/**
 * Class Controller_Default
 * @author ${USER} ${USER}
 */
class Controller_Default extends Controller_Base
{

    /**
     * @param $a
     * @param $b
     */
    public function action_index($a, $b)
    {
        $i = '';


        logger(\Fuel::L_INFO, 'This is it!', __METHOD__);
        \Lang::load('testc.db');
        //$lang = array('sir' => array('no' => 'non monsieur'));
        //\Lang::set('test2', 'This is another test');
        Debug::dump(\Lang::$lines);
        \Lang::set('sir.no', 'Non, !');
        try {
            \Lang::save('testc.db', 'sir');
        } catch (Exception $ex) {
            \Messages::error($ex->getMessage());
        }

        \Messages::success(__('yes'));
        $data["subnav"] = array('action1' => 'active');
        $this->template->title = 'Index &raquo; Action1';
        $this->template->content = View::forge('default/action1', $data);
    }

    /**
     * @param null $provider
     */
    public function action_oauth($provider = null)
    {
        // bail out if we don't have an OAuth provider to call
        if ($provider === null) {
            \Messages::error(sprintf(__('login.no-provider-specified')));
            \Response::redirect_back();
        }

        if (\Auth::check()) {
            \Messages::warning(sprintf(__('login.username-already-logged-in')));
            \Response::redirect_back();
        }
        // load Opauth, it will load the provider strategy and redirect to the provider
        \Auth_Opauth::forge();
    }

    /**
     * @throws FuelException
     */
    public function action_callback()
    {
        // Opauth can throw all kinds of nasty bits, so be prepared
        try {
            // get the Opauth object
            $opauth = \Auth_Opauth::forge(false);

            // and process the callback
            $status = $opauth->login_or_register();
            $url = 'dashboard';
            $admin_group_id = Config::get('auth.driver', 'Simpleauth') == 'Ormauth' ? 6 : 100;
            $is_admin = Auth::member($admin_group_id);
            \Session::set_flash('success', \Auth::get_screen_name());


            // fetch the provider name from the opauth response so we can display a message
            $provider = $opauth->get('auth.provider', '?');

            // deal with the result of the callback process
            switch ($status) {
                // a local user was logged-in, the provider has been linked to this user
                case 'linked':
                    // inform the user the link was succesfully made
                    \Messages::success(sprintf(__('login.provider-linked'), ucfirst($provider)));
                    // and set the redirect url for this status
                    $url = $is_admin ? 'admin' : 'dashboard';
                    break;

                // the provider was known and linked, the linked account as logged-in
                case 'logged_in':
                    // inform the user the login using the provider was succesful
                    \Messages::success(sprintf(__('login.logged_in_using_provider'), ucfirst($provider)));
                    // and set the redirect url for this status
                    $url = $is_admin ? 'admin' : 'dashboard';
                    break;

                // we don't know this provider login, ask the user to create a local account first
                case 'register':
                    // inform the user the login using the provider was succesful, but we need a local account to continue
                    \Messages::info(sprintf(__('login.register-first'), ucfirst($provider)));
                    // and set the redirect url for this status
                    $url = 'default/auto_register';
                    break;

                // we didn't know this provider login, but enough info was returned to auto-register the user
                case 'registered':
                    // inform the user the login using the provider was succesful, and we created a local account
                    \Messages::success(__('login.auto-registered'));
                    // and set the redirect url for this status
                    $url = $is_admin ? 'admin' : 'dashboard';
                    break;

                default:
                    throw new \FuelException('Auth_Opauth::login_or_register() has come up with a result that we dont know how to handle.');
            }

            // redirect to the url set
            \Response::redirect($url);
        } // deal with Opauth exceptions
        catch (\OpauthException $e) {
            \Messages::error($e->getMessage());
            \Response::redirect_back();
        }

            // catch a user cancelling the authentication attempt (some providers allow that)
        catch (\OpauthCancelException $e) {
            // you should probably do something a bit more clean here...
            exit('It looks like you canceled your authorisation.' . \Html::anchor('default/oauth/' . $provider, 'Click here') . ' to try again.');
        }

    }

    /**
     *
     */
    public function action_register()
    {

        // is registration enabled?
        if (!\Config::get('application.user.registration', true)) {
            // inform the user registration is not possible
            \Messages::error('login.registation-not-enabled');

            // and go back to the previous page (or the homepage)
            \Response::redirect_back();
        }

        // create the registration fieldset
        $form = \Fieldset::forge('registerform');

        // add a csrf token to prevent CSRF attacks
        $form->form()->add_csrf();


        // and populate the form with the model properties
        $form->add_model('Model\\Auth_User');

        // add the fullname field, it's a profile property, not a user property
        $form->add_after('fullname', __('login.form.fullname'), array(), array(), 'username')->add_rule('required');

        // add a password confirmation field
        $form->add_after('confirm', __('login.form.confirm'), array('type' => 'password'), array(), 'password')->add_rule('required');

        // make sure the password is required
        $form->field('password')->add_rule('required');

        // and new users are not allowed to select the group they're in (duh!)
        $form->disable('group_id');

        // since it's not on the form, make sure validation doesn't trip on its absence
        $form->field('group_id')->delete_rule('required')->delete_rule('is_numeric');
        $form->add('register', 'envoyer', array('type' => 'submit', 'value' => 'envoyer', 'class' => 'btn btn-lg btn-primary btn-block'));


        // fetch the oauth provider from the session (if present)
        $provider = \Session::get('auth-strategy.authentication.provider', false);

        // if we have provider information, create the login fieldset too
        if ($provider) {
            // disable the username, it was passed to us by the Oauth strategy
            //$form->field('username')->set_attribute('readonly', true);

            // create an additional login form so we can link providers to existing accounts
            $login = \Fieldset::forge('loginform');
            $login->form()->add_csrf();
            $login->add_model('Model\\Auth_User');

            // we only need username and password
            $login->disable('group_id')->disable('email');

            // since they're not on the form, make sure validation doesn't trip on their absence
            $login->field('group_id')->delete_rule('required')->delete_rule('is_numeric');
            $login->field('email')->delete_rule('required')->delete_rule('valid_email');
        }

        // was the registration form posted?
        if (\Input::method() == 'POST') {

            // was the login form posted?
            if ($provider and \Input::post('login')) {
                // check the credentials.
                if (\Auth::instance()->login(\Input::param('username'), \Input::param('password'))) {
                    // get the current logged-in user's id
                    list(, $userid) = \Auth::instance()->get_user_id();

                    // so we can link it to the provider manually
                    $this->link_provider($userid);

                    // inform the user we're linked
                    \Messages::success(sprintf(__('login.provider-linked'), ucfirst($provider)));

                    // logged in, go back where we came from,
                    // or the the user dashboard if we don't know
                    \Response::redirect_back('dashboard');
                } else {
                    // login failed, show an error message
                    \Messages::error(__('login.failure'));
                }
            } // was the registration form posted?
            elseif (\Input::post('register')) {

                // validate the input
                $form->validation()->run();
                // if validated, create the user
                if (!$form->validation()->error()) {
                    try {
                        // call Auth to create this user
                        $created = \Auth::create_user(
                            $form->validated('username'),
                            $form->validated('password'),
                            $form->validated('email'),
                            \Config::get('application.user.default_group', 1),
                            array(
                                'fullname' => $form->validated('fullname'),
                            )
                        );

                        // if a user was created succesfully
                        if ($created) {
                            // inform the user
                            \Messages::success(__('login.new-account-created'));

                            // and go back to the previous page, or show the
                            // application dashboard if we don't have any
                            \Response::redirect_back('dashboard');
                        } else {
                            // oops, creating a new user failed?
                            \Messages::error(__('login.account-creation-failed'));
                        }
                    } // catch exceptions from the create_user() call
                    catch (\SimpleUserUpdateException $e) {
                        // duplicate email address
                        if ($e->getCode() == 2) {
                            \Messages::error(__('login.email-already-exists'));
                        } // duplicate username
                        elseif ($e->getCode() == 3) {
                            \Messages::error(__('login.username-already-exists'));
                        } // this can't happen, but you'll never know...
                        else {
                            \Messages::error($e->getMessage());
                        }
                    }
                }
            }

            // validation failed, repopulate the form from the posted data

            $form->repopulate();
            foreach ($form->validation()->error() as $error) {
                \Messages::error($error);
            }
        } else {
            // get the auth-strategy data from the session (created by the callback)
            $user_hash = \Session::get('auth-strategy.user', array());

            // populate the registration form with the data from the provider callback
            $form->populate(array(
                'username' => \Arr::get($user_hash, 'nickname'),
                'fullname' => \Arr::get($user_hash, 'name'),
                'email' => \Arr::get($user_hash, 'email'),
            ));
        }

        // pass the fieldset to the form, and display the new user registration view
        $data["subnav"] = array('registration' => 'active');

        $this->template->title = 'Index &raquo; Registration';
        $this->template->content = View::forge('default/registration', $data)->set('form', $form, false)->set('login', isset($login) ? $login : null, false);
        //return \View::forge('default/registration')->set('form', $form, false)->set('login', isset($login) ? $login : null, false);
    }

    /**
     * Registration from oauth callback
     */
    public function action_auto_register()
    {
        $created = -1;
        $provider = \Session::get('auth-strategy.authentication.provider', false);
        if (!$provider) {
            \Response::redirect('default/register');
        } else {
            // get the auth-strategy data from the session (created by the callback)
            $user_hash = (object)\Session::get('auth-strategy.user', array());

            // populate the registration form with the data from the provider callback
            try {
                $created = \Auth::create_user(
                    $user_hash->email,
                    Str::random('hexdec', 16),
                    $user_hash->email,
                    \Config::get('application.user.default_group', 1),
                    array(

                        'fullname' => $user_hash->name,
                        'first_name' => $user_hash->first_name,
                        'last_name' => $user_hash->last_name,
                        'image' => (isset($user_hash->image)) ? $user_hash->image : ''
                    )
                );
                \Messages::success(__('login.users.:id.created', array('id' => $created)));
                $this->link_provider($created);
            } catch (Exception $ex) {
                \Messages::error($ex->getMessage());
                \Response::redirect('default/register');
            }
            $data["subnav"] = array('registration' => 'active');

            $this->template->title = 'Index &raquo; Registration';
            $this->template->content = View::forge('welcome/index', $data);
        }
    }

    /**
     *
     */
    public function action_fieldset()
    {
        $test = \Fieldset::forge('test');
        $test->add(
            'email', 'email'
        );

        $val = Validation::forge();
        $val->add_field('email', 'email', 'required|trim|valid_email');

        try {
            $val->run(array('email' => 'testemail.com'));
        } catch (Exception $ex) {
            \Fuel\Core\Debug::dump($ex);
        }

        \Debug::dump($val->error());
        $data["subnav"] = array('registration' => 'active');

        $this->template->title = 'Test';
        $this->template->content = View::forge('welcome/index', $data);

    }

    /**
     *
     */
    public function action_action1()
    {
        $data["subnav"] = array('action1' => 'active');
        $this->template->title = 'Index &raquo; Action1';
        $this->template->content = View::forge('default/action1', $data);
    }

    /**
     *
     */
    public function action_action2()
    {
        $data["subnav"] = array('action2' => 'active');
        $this->template->title = 'Index &raquo; Action2';
        $this->template->content = View::forge('default/action2', $data);
    }

    /**
     * @param $userid
     */
    protected function link_provider($userid)
    {
        // do we have an auth strategy to match?
        if ($authentication = \Session::get('auth-strategy.authentication', array())) {
            // don't forget to pass false, we need an object instance, not a strategy call
            $opauth = \Auth_Opauth::forge(false);

            // call Opauth to link the provider login with the local user
            $insert_id = $opauth->link_provider(array(
                'parent_id' => $userid,
                'provider' => $authentication['provider'],
                'uid' => $authentication['uid'],
                'access_token' => $authentication['access_token'],
                'secret' => $authentication['secret'],
                'refresh_token' => $authentication['refresh_token'],
                'expires' => $authentication['expires'],
                'created_at' => time(),
            ));
        }
    }

}
