<?php

add_action('admin_head', 'pybox_admin_css');

function pybox_admin_css() {
  echo '<style type="text/css">
#login h1, #login h2 {text-align: center; margin-bottom: 10px;}

#login {padding-top: 50px !important;}

.returnfromprofile {
        font-size: 18px;
        margin: 10px;
        font-weight: 700;
}

.returnfromprofile a {
        padding: 3px 7px;
        text-decoration: none;
        border: 1px solid green;
        border-radius: 12px;
        background:green;
        color: white;
}

#your-profile p.submit input#submit.button-primary{
        margin: 10px;
        font-size: 18px !important;
        padding: 7px;
}

.returnfromprofile a:hover {
        color: #dfd;
        border-color: palegreen;
        border-style: inset;
}';
}
