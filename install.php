<?php

/* Prevent Direct Access */
defined('ADAPT_STARTED') or die;

$adapt = $GLOBALS['adapt'];
$sql = $adapt->data_source->sql;

$sql->on('adapt.error', function($error){
    print new \frameworks\adapt\html_pre(print_r($error, true));
});

/* Create the tables */
$sql->create_table('user')
    ->add('user_id', 'bigint')
    ->add('status', "enum('Active', 'Suspended')", false, 'Active')
    ->add('contact_id', 'bigint')
    ->add('username', 'varchar(128)', false)
    ->add('password', 'varchar(256)')
    ->add('password_change_required', "enum('Yes', 'No')", false, 'Yes')
    ->add('date_created', 'datetime')
    ->add('date_modified', 'timestamp')
    ->add('date_deleted', 'datetime')
    ->primary_key('user_id')
    ->foreign_key('contact_id', 'contact', 'contact_id')
    ->execute();

$sql->create_table('user_setting')
    ->add('user_setting_id', 'bigint')
    ->add('user_id', 'bigint')
    ->add('name', 'varchar(64)', false)
    ->add('value', 'text')
    ->add('date_created', 'datetime')
    ->add('date_modified', 'timestamp')
    ->add('date_deleted', 'datetime')
    ->primary_key('user_setting_id')
    ->foreign_key('user_id', 'user', 'user_id')
    ->execute();

$sql->create_table('user_login_token')
    ->add('user_login_token_id', 'bigint')
    ->add('user_id', 'bigint')
    ->add('token', 'varchar(64)', false)
    ->add('token_type', "enum('Keep me signed in', 'Auto login', 'Password reset')", false, 'Auto login')
    ->add('access_count', 'int', false, 0)
    ->add('date_created', 'datetime')
    ->add('date_modified', 'timestamp')
    ->add('date_deleted', 'datetime')
    ->primary_key('user_login_token_id')
    ->foreign_key('user_id', 'user', 'user_id')
    ->index('token', '32')
    ->execute();
    
    
/*
 * Did want to create a user here but can't because the model
 * isn't available until this bundle has the installed flag set, which won't
 * occur until this script ends :/
 * Could always install manually but I think it's best for this
 * to be user prompted by adapt_setup
 */

 /* Field types */
$hidden = new model_form_field_type();
$hidden->load_by_name('Hidden');
$hidden = $hidden->form_field_type_id;

$text = new model_form_field_type();
$text->load_by_name('Text');
$text = $text->form_field_type_id;

$password = new model_form_field_type();
$password->load_by_name('Password');
$password = $password->form_field_type_id;

$checkbox = new model_form_field_type();
$checkbox->load_by_name('Checkbox');
$checkbox = $checkbox->form_field_type_id;
    
    
    
/*
 * Add login form - email
 */
$form = new model_form();
$form->bundle_name = 'users';
$form->submission_url = '/';
$form->actions = 'sign-in';
$form->method = 'post';
$form->name = 'sign_in_email';
$form->title = 'Sign in';
$form->show_steps = 'No';
$form->show_processing_page = 'Yes';
$form->save();

$page = new model_form_page();
$page->form_id = $form->form_id;
$page->bundle_name = 'users';
$page->priority = 1;
$page->save();

$primary_button = new model_form_button_style();
$primary_button->load_by_name('Primary');
$primary_button = $primary_button->form_button_style_id;

$link_button = new model_form_button_style();
$link_button->load_by_name('Link');
$link_button = $link_button->form_button_style_id;

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $primary_button;
$button->label = 'Sign in';
$button->action = 'Next page';
$button->priority = 2;
$button->save();

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $link_button;
$button->label = 'Forgot your password?';
$button->action = 'Custom...';
$button->custom_action = "window.location='/forgot-password'; return void(0);";
$button->priority = 1;
$button->save();

$layout = new model_form_page_section_layout();
$layout->load_by_name('standard');

$section = new model_form_page_section();
$section->form_page_id = $page->form_page_id;
$section->form_page_section_layout_id = $layout->form_page_section_layout_id;
$section->bundle_name = 'users';
$section->priority = 1;
$section->repeatable = 'No';
$section->save();


/* Fields */
$layout = new model_form_page_section_group_layout();
$layout->load_by_name('simple');

$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 1;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id  = $hidden;
$field->name = 'redirect_url';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->save();

$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 2;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $text;
$field->name = 'email';
$field->label = 'Email';
$field->data_type_id = $adapt->data_source->get_data_type_id('email_address');
$field->placeholder_label = 'someone@example.com';
$field->max_length = 256;
$field->mandatory = 'Yes';
$field->save();



$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 3;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $password;
$field->name = 'password';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->label = 'Password';
$field->max_length = 64;
$field->mandatory = 'Yes';
$field->save();


$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 4;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $checkbox;
$field->name = 'stay_signed_in';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->label = 'Keep me signed in';
$field->mandatory = 'No';
$field->allowed_values = json_encode(array('Yes'));
$field->save();




/*
 * Add login form - username
 */

$form = new model_form();
$form->bundle_name = 'users';
$form->submission_url = '/';
$form->actions = 'sign-in';
$form->method = 'post';
$form->name = 'sign_in_username';
$form->title = 'Sign in';
$form->show_steps = 'No';
$form->show_processing_page = 'Yes';
$form->save();

$page = new model_form_page();
$page->form_id = $form->form_id;
$page->bundle_name = 'users';
$page->priority = 1;
$page->save();

$primary_button = new model_form_button_style();
$primary_button->load_by_name('Primary');
$primary_button = $primary_button->form_button_style_id;

$link_button = new model_form_button_style();
$link_button->load_by_name('Link');
$link_button = $link_button->form_button_style_id;

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $primary_button;
$button->label = 'Sign in';
$button->action = 'Next page';
$button->priority = 2;
$button->save();

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $link_button;
$button->label = 'Forgot your password?';
$button->action = 'Custom...';
$button->custom_action = "window.location='/forgot-password'; return void(0);";
$button->priority = 1;
$button->save();

$layout = new model_form_page_section_layout();
$layout->load_by_name('standard');

$section = new model_form_page_section();
$section->form_page_id = $page->form_page_id;
$section->form_page_section_layout_id = $layout->form_page_section_layout_id;
$section->bundle_name = 'users';
$section->priority = 1;
$section->repeatable = 'No';
$section->save();


/* Fields */
$layout = new model_form_page_section_group_layout();
$layout->load_by_name('simple');

$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 1;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $hidden;
$field->name = 'redirect_url';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->save();

$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 2;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id= $text;
$field->name = 'username';
$field->label = 'Username';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->placeholder_label = 'Username...';
$field->max_length = 256;
$field->mandatory = 'Yes';
$field->save();



$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 3;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $password;
$field->name = 'password';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->label = 'Password';
$field->max_length = 64;
$field->mandatory = 'Yes';
$field->save();


$group = new model_form_page_section_group();
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $layout->form_page_section_group_layout_id;
$group->bundle_name = 'users';
$group->priority = 4;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$field->form_field_type_id = $checkbox;
$field->name = 'stay_signed_in';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->label = 'Keep me signed in';
$field->mandatory = 'No';
$field->allowed_values = json_encode(array('Yes'));
$field->save();


/*
 * Add pre-registration form
 */
//$form = new model_form();
//$form->bundle_name = 'users';
//$form->submission_url = '/';
//$form->actions = 'join';
//$form->method = 'post';
//$form->name = 'join_username';
//$form->title = 'Join';
//$form->style = 'Standard';
//$form->show_steps = 'No';
//$form->show_processing_page = 'No';
//$form->save();
//
//$page = new model_form_page();
//$page->form_id = $form->form_id;
//$page->bundle_name = 'users';
//$page->priority = 1;
//$page->save();
//
//$button = new model_form_page_button();
//$button->form_page_id = $page->form_page_id;
//$button->bundle_name = "sessions";
//$button->form_button_style_id = $primary_button;
//$button->label = 'Join now';
//$button->action = 'Next page';
//$button->priority = 1;
//$button->save();
//
//$section = new model_form_page_section();
//$section->form_page_id = $page->form_page_id;
//$section->bundle_name = 'users';
//$section->priority = 1;
//$section->repeatable = 'No';
//$section->save();
//
//
//
///* Fields */
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 1;
//$field->form_field_type_id = $hidden;
//$field->name = 'redirect_url';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->save();
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 2;
//$field->form_field_type_id = $text;
//$field->name = 'username';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Username';
//$field->placeholder_label = 'username';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 3;
//$field->form_field_type_id = $password;
//$field->name = 'password';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Password';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 3;
//$field->form_field_type_id = $password;
//$field->name = 'password_repeat';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Repeat password';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();$field->save();
//


/*
 * Add pre-registration form (email)
 */
$form = new model_form();
$form->bundle_name = 'users';
$form->submission_url = '/';
$form->actions = 'join';
$form->method = 'post';
$form->name = 'join_email';
$form->title = 'Join';
$form->show_steps = 'No';
$form->show_processing_page = 'Yes';
$form->save();

$page = new model_form_page();
$page->form_id = $form->form_id;
$page->bundle_name = 'users';
$page->priority = 1;
$page->save();

$primary_button = new model_form_button_style();
$primary_button->load_by_name('Primary');
$primary_button = $primary_button->form_button_style_id;

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $primary_button;
$button->label = 'Join now';
$button->action = 'Next page';
$button->priority = 1;
$button->save();

$standard_layout = new model_form_page_section_layout();
$standard_layout->load_by_name('standard');

$section = new model_form_page_section();
$section->form_page_id = $page->form_page_id;
$section->bundle_name = 'users';
$section->form_page_section_layout_id = $standard_layout->form_page_section_layout_id;
$section->priority = 1;
$section->repeatable = 'No';
$section->save();

$simple_group = new model_form_page_section_group_layout();
$simple_group->load_by_name('simple');

$group = new model_form_page_section_group();
$group->bundle_name = 'users';
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $simple_group->form_page_section_group_layout_id;
$group->priority = 1;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$type = new model_form_field_type();
$type->load_by_name('Text');
$field->form_field_type_id = $type->form_field_type_id;
$field->name = 'email';
$field->label = 'Email';
$field->data_type_id = $adapt->data_source->get_data_type_id('email_address');
$field->mandatory = 'Yes';
$field->save();

$group = new model_form_page_section_group();
$group->bundle_name = 'users';
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $simple_group->form_page_section_group_layout_id;
$group->priority = 2;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$type = new model_form_field_type();
$type->load_by_name('Password confirmation with indicator');
$field->form_field_type_id = $type->form_field_type_id;
$field->name = 'password';
$field->label = 'Password';
$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
$field->max_length = 64;
$field->mandatory = 'Yes';
$field->save();


/*
 * Registration form (username)
 */


/*
 * Registration form (email)
 */
$form = new model_form();
$form->bundle_name = 'users';
$form->submission_url = '/';
$form->actions = 'join';
$form->method = 'post';
$form->name = 'register_email';
$form->title = 'Create your account';
$form->show_steps = 'No';
$form->show_processing_page = 'Yes';
$form->save();

$page = new model_form_page();
$page->form_id = $form->form_id;
$page->bundle_name = 'users';
$page->priority = 1;
$page->save();

$primary_button = new model_form_button_style();
$primary_button->load_by_name('Primary');
$primary_button = $primary_button->form_button_style_id;

$button = new model_form_page_button();
$button->form_page_id = $page->form_page_id;
$button->bundle_name = "users";
$button->form_button_style_id = $primary_button;
$button->label = 'Create my account';
$button->action = 'Next page';
$button->priority = 1;
$button->save();

$standard_layout = new model_form_page_section_layout();
$standard_layout->load_by_name('standard');

$section = new model_form_page_section();
$section->form_page_id = $page->form_page_id;
$section->bundle_name = 'users';
$section->form_page_section_layout_id = $standard_layout->form_page_section_layout_id;
$section->priority = 1;
$section->repeatable = 'No';
$section->save();

$simple_group = new model_form_page_section_group_layout();
$simple_group->load_by_name('simple');

$group = new model_form_page_section_group();
$group->bundle_name = 'users';
$group->form_page_section_id = $section->form_page_section_id;
$group->form_page_section_group_layout_id = $simple_group->form_page_section_group_layout_id;
$group->priority = 1;
$group->save();

$field = new model_form_page_section_group_field();
$field->form_page_section_group_id = $group->form_page_section_group_id;
$field->bundle_name = 'users';
$field->priority = 1;
$type = new model_form_field_type();
$type->load_by_name('Text');
$field->form_field_type_id = $type->form_field_type_id;
$field->name = 'email';
$field->label = 'Email';
$field->data_type_id = $adapt->data_source->get_data_type_id('email_address');
$field->mandatory = 'Yes';
$field->save();

/* Fields */
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 1;
//$field->form_field_type_id = $hidden;
//$field->name = 'redirect_url';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->save();
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 2;
//$field->form_field_type_id = $text;
//$field->name = 'email';
//$field->data_type_id = $adapt->data_source->get_data_type_id('email_address');
//$field->label = 'Email';
//$field->placeholder_label = 'someone@example.com';
//$field->max_length = 256;
//$field->mandatory = 'Yes';
//$field->save();
//
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 3;
//$field->form_field_type_id = $password;
//$field->name = 'password';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Password';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();
//
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 3;
//$field->form_field_type_id = $password;
//$field->name = 'password_repeat';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Repeat password';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();$field->save();
//
//
///*
// * Add forgot password form (username)
// */
//$form = new model_form();
//$form->bundle_name = 'users';
//$form->submission_url = '/forgot-password';
//$form->actions = 'reset-password';
//$form->method = 'post';
//$form->name = 'forgot_password_username';
//$form->title = 'Forgotten your password?';
//$form->style = 'Standard';
//$form->show_steps = 'No';
//$form->show_processing_page = 'No';
//$form->save();
//
//$page = new model_form_page();
//$page->form_id = $form->form_id;
//$page->bundle_name = 'users';
//$page->priority = 1;
//$page->save();
//
//
//$button = new model_form_page_button();
//$button->form_page_id = $page->form_page_id;
//$button->bundle_name = "users";
//$button->form_button_style_id = $primary_button;
//$button->label = 'Reset password';
//$button->action = 'Next page';
//$button->priority = 1;
//$button->save();
//
//$section = new model_form_page_section();
//$section->form_page_id = $page->form_page_id;
//$section->bundle_name = 'users';
//$section->priority = 1;
//$section->repeatable = 'No';
//$section->save();
//
//
//
///* Fields */
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 1;
//$field->form_field_type_id = $text;
//$field->name = 'username';
//$field->data_type_id = $adapt->data_source->get_data_type_id('varchar');
//$field->label = 'Username';
//$field->placeholder_label = 'username';
//$field->max_length = 64;
//$field->mandatory = 'Yes';
//$field->save();
//
//
//
// /*
// * Add forgot password form (email)
// */
//$form = new model_form();
//$form->bundle_name = 'users';
//$form->submission_url = '/forgot-password';
//$form->actions = 'reset-password';
//$form->method = 'post';
//$form->name = 'forgot_password_email';
//$form->title = 'Forgotten your password?';
//$form->show_steps = 'No';
//$form->show_processing_page = 'Yes';
//$form->save();
//
//$page = new model_form_page();
//$page->form_id = $form->form_id;
//$page->bundle_name = 'users';
//$page->priority = 1;
//$page->save();
//
//$primary_button = new model_form_button_style();
//$primary_button->load_by_name('Primary');
//$primary_button = $primary_button->form_button_style_id;
//
//$link_button = new model_form_button_style();
//$link_button->load_by_name('Link');
//$link_button = $link_button->form_button_style_id;
//
//$button = new model_form_page_button();
//$button->form_page_id = $page->form_page_id;
//$button->bundle_name = "users";
//$button->form_button_style_id = $primary_button;
//$button->label = 'Reset password';
//$button->action = 'Next page';
//$button->priority = 1;
//$button->save();
//
//$section = new model_form_page_section();
//$section->form_page_id = $page->form_page_id;
//$section->bundle_name = 'users';
//$section->priority = 1;
//$section->repeatable = 'No';
//$section->save();
//
//
//
///* Fields */
//$field = new model_form_page_section_field();
//$field->form_page_section_id = $section->form_page_section_id;
//$field->bundle_name = 'users';
//$field->priority = 1;
//$field->form_field_type_id = $text;
//$field->name = 'email';
//$field->data_type_id = $adapt->data_source->get_data_type_id('email_address');
//$field->label = 'Email';
//$field->placeholder_label = 'someone@example.com';
//$field->max_length = 256;
//$field->mandatory = 'Yes';
//$field->save();




?>