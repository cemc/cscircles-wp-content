<?php

add_action('admin_head', 'pybox_admin_css');

function pybox_admin_css() {
  echo '<style type="text/css">
.wp-admin .button-primary{
        margin-top: 10px;
        font-size: 14px !important;
        font-weight: bold;
        padding: 7px;
        height: auto;
}

.wp-admin #wpadminbar #wp-toolbar #wp-admin-bar-user-actions .ab-item
{padding: 0px;}
</style>';
}

add_action('login_head', 'pybox_login_css');

function pybox_login_css() {
  echo '<style type="text/css">
#login h1, #login h2 {text-align: center; margin-bottom: 10px;}
</style>';
}


