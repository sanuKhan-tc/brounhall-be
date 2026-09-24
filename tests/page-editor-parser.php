<?php
// Minimal parser check; WordPress is not required for this migration test.
define( 'ABSPATH', __DIR__ );
function add_action() {}
require dirname( __DIR__ ) . '/wp-content/plugins/brounhall-headless/includes/page-editor.php';

$data = brounhall_page_yaml_to_array( <<<'YAML'
version: 1
page:
  template: sample
sections:
  - id: hero
    type: hero
    enabled: true
    content:
      title: "Hello"
      description: >
        First line
        second line
    actions:
      - label: Book
        href: /book
YAML
);

assert( 'sample' === $data['page']['template'] );
assert( 'hero' === $data['sections'][0]['id'] );
assert( true === $data['sections'][0]['enabled'] );
assert( 'First line second line' === $data['sections'][0]['content']['description'] );
assert( '/book' === $data['sections'][0]['actions'][0]['href'] );
echo "page-editor parser ok\n";
