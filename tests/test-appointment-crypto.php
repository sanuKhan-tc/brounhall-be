<?php
declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function absint( $value ) { return abs( (int) $value ); }
require dirname( __DIR__ ) . '/wp-content/plugins/brounhall-headless/includes/security/appointment-key-provider.php';
require dirname( __DIR__ ) . '/wp-content/plugins/brounhall-headless/includes/security/appointment-crypto.php';

$key = base64_encode( str_repeat( 'k', 32 ) );
$index_key = base64_encode( str_repeat( 'i', 32 ) );
putenv( 'BOURNHALL_APPOINTMENT_KEK=' . $key );
putenv( 'BOURNHALL_APPOINTMENT_EMAIL_INDEX_KEY=' . $index_key );
putenv( 'BOURNHALL_APPOINTMENT_PHONE_INDEX_KEY=' . base64_encode( str_repeat( 'p', 32 ) ) );
putenv( 'BOURNHALL_APPOINTMENT_DUPLICATE_KEY=' . base64_encode( str_repeat( 'd', 32 ) ) );

$payload = array( 'name' => 'ENCRYPTION_TEST_NAME_12345', 'email' => 'encryption-test-12345@example.invalid', 'phone' => '+971500001234', 'location' => 'Dubai', 'service' => 'IUI', 'message' => 'UNIQUE_TEST_SECRET_MESSAGE_98765', 'consent' => true );
$one = brounhall_appointment_envelope( 'APT-TEST-1', $payload );
$two = brounhall_appointment_envelope( 'APT-TEST-1', $payload );
assert( $one['ciphertext'] !== $two['ciphertext'] );
assert( brounhall_appointment_decrypt( 'APT-TEST-1', $one ) === $payload );

$tampered = $one;
$tampered['ciphertext'][0] = $tampered['ciphertext'][0] === 'A' ? 'B' : 'A';
try { brounhall_appointment_decrypt( 'APT-TEST-1', $tampered ); assert( false ); } catch ( Throwable $error ) { assert( true ); }
try { brounhall_appointment_decrypt( 'APT-WRONG', $one ); assert( false ); } catch ( Throwable $error ) { assert( true ); }
try { $missing = $one; unset( $missing['nonce'] ); brounhall_appointment_decrypt( 'APT-TEST-1', $missing ); assert( false ); } catch ( Throwable $error ) { assert( true ); }
$original_key = getenv( 'BOURNHALL_APPOINTMENT_KEK' );
putenv( 'BOURNHALL_APPOINTMENT_KEK=' . base64_encode( str_repeat( 'x', 32 ) ) );
try { brounhall_appointment_decrypt( 'APT-TEST-1', $one ); assert( false ); } catch ( Throwable $error ) { assert( true ); }
putenv( 'BOURNHALL_APPOINTMENT_KEK=' . $original_key );
assert( $one['email_index'] === brounhall_appointment_key_provider()->blind_index( 'email', strtoupper( $payload['email'] ) ) );
assert( $one['phone_index'] !== $one['email_index'] );
echo "appointment crypto tests passed\n";
