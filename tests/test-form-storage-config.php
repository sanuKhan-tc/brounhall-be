<?php
declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
function add_action() {}
require __DIR__ . '/../wp-content/plugins/brounhall-headless/includes/form-mail.php';

if ( brounhall_store_form_submissions() ) {
	throw new RuntimeException( 'Form storage must be disabled when the config constant is absent.' );
}

echo "Form storage config check passed\n";
