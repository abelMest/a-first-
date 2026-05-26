<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/ollama_chat:myaddinstance' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'user' => CAP_ALLOW
        ),
    ),
    'block/ollama_chat:addinstance' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes'   => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
    ),
);
