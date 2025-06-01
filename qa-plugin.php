<?php
if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('page', 'list-users.php', 'list_users', 'Show all the users with the selected level.');
