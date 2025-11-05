<?php
session_start();
echo 'Session ID: ' . session_id() . '<br>';
echo 'Session Cookie Params: ' . print_r(session_get_cookie_params(), true);
?> 