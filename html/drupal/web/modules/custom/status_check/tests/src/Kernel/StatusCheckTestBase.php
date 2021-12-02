<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use MPNDEV\D8TDD\KernelTestBase;

abstract class StatusCheckTestBase extends KernelTestBase {

  public static $modules = [
    'node',
    'field',
    'paragraphs',
    'status_check',
    'entity_reference_revisions',
    'file',
    'menu_ui',
  ];

  const ENDPOINT_API_USER_REGISTER = 'https://statuscheckapp.local/api/user/register?XDEBUG_SESSION_START=PHPSTORM';
  const ENDPOINT_API_PROJECTS_WHERE = 'https://statuscheckapp.local/api/projects/where';
  const ENDPOINT_API_PROJECTS = 'https://statuscheckapp.local/api/projects?XDEBUG_SESSION_START=PHPSTORM';
  const ENDPOINT_API_USER_SETTINGS = 'https://statuscheckapp.local/api/user/settings?XDEBUG_SESSION_START=PHPSTORM';
  const ENDPOINT_API_USER_LOGIN = 'https://statuscheckapp.local/api/user/login?XDEBUG_SESSION_START=PHPSTORM';

  const VALID_USERNAME = 'John Doe';
  const INVALID_USERNAME = 'Dummy Name';
  const SHORT_USERNAME = 'Ab';
  const EMPTY_USERNAME = NULL;

  const VALID_PASSWORD = 'qwerty';
  const INVALID_PASSWORD = 'invalid_password';
  const EMPTY_PASSWORD = NULL;

  const SETTINGS = '{"settings":"some json settings"}';
  const EMPTY_SETTINGS = '{"settings":null}';

  const VALID_JWT_RESPONSE = '{"jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOiIxIn0._z4G14O3rnYaFidShnGhPqWJ_5YOFdZj--2BOz8vxC4"}';
  const VALID_JWT_TOKEN = '{"jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOiIxIn0._z4G14O3rnYaFidShnGhPqWJ_5YOFdZj--2BOz8vxC4"}';
  const VALID_COOKIE = ['jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOiIxIn0._z4G14O3rnYaFidShnGhPqWJ_5YOFdZj--2BOz8vxC4'];
  const INVALID_COOKIE = ['jwt' => 'SOME.INVALID.COOKIE'];

  const JSON_RESPONSE_PROJECT_IDS_AND_TITLES = '[{"id":"1","title":"Some Project"},{"id":"2","title":"Some Project"}]';
  const JSON_RESPONSE_PROJECT_WITH_ONE_ENVIRONMENT_WITH_ONE_CHECK_STATUS_CHECK = '{"data":[{"id":1,"name":"Some Project","environments":[{"name":"Some Environment","checks":[{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"}],"assertions":[{"name":"response_status_code","field_code":"200"}]}]}]}]}';
  const JSON_RESPONSE_TWO_PROJECTS_WITH_TWO_ENVIRONMENTS_WITH_TWO_CHECKS_WITH_TWO_ASSERTIONS_AND_ACTIONS = '{"data":[{"id":1,"name":"Some Project","environments":[{"name":"Some Environment","checks":[{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]},{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]}]},{"name":"Some Environment","checks":[{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]},{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]}]}]},{"id":2,"name":"Some Project","environments":[{"name":"Some Environment","checks":[{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]},{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]}]},{"name":"Some Environment","checks":[{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]},{"name":"Some Check","actions":[{"name":"go_to","field_text":"http:\/\/localhost"},{"name":"click"}],"assertions":[{"name":"response_status_code","field_code":"200"},{"name":"response_status_code","field_code":"200"}]}]}]}]}';

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('file');
    $this->installConfig(['field', 'node', 'paragraphs', 'status_check']);

    $this->factory(Node::class)->define('project', [
      'type' => 'project',
      'title' => "Some Project",
    ]);
    $this->factory(Paragraph::class)->define('environment', [
      'type' => 'environment',
      'field_name' => "Some Environment",
    ]);
    $this->factory(Paragraph::class)->define('check', [
      'type' => 'check',
      'field_name' => "Some Check",
    ]);
    $this->factory(Paragraph::class)->define('click', [
      'type' => 'click',
    ]);
    $this->factory(Paragraph::class)->define('find', [
      'type' => 'find',
      'field_element' => "some-element",
    ]);
    $this->factory(Paragraph::class)->define('find_and_type', [
      'type' => 'find_and_type',
      'field_element' => "some-element",
      'field_text' => "Lorem ipsum...",
    ]);
    $this->factory(Paragraph::class)->define('focus', [
      'type' => 'focus',
    ]);
    $this->factory(Paragraph::class)->define('go_to', [
      'type' => 'go_to',
      'field_text' => 'http://localhost',
    ]);
    $this->factory(Paragraph::class)->define('keyboard_down', [
      'type' => 'keyboard_down',
      'field_button' => 'shift',
    ]);
    $this->factory(Paragraph::class)->define('keyboard_press', [
      'type' => 'keyboard_press',
      'field_button' => 'shift',
    ]);
    $this->factory(Paragraph::class)->define('keyboard_type', [
      'type' => 'keyboard_type',
      'field_text' => 'Lorem ipsum...',
    ]);
    $this->factory(Paragraph::class)->define('mouse_left_click', [
      'type' => 'mouse_left_click',
    ]);
    $this->factory(Paragraph::class)->define('mouse_left_up', [
      'type' => 'mouse_left_up',
    ]);
    $this->factory(Paragraph::class)->define('mouse_left_down', [
      'type' => 'mouse_left_down',
    ]);
    $this->factory(Paragraph::class)->define('mouse_right_click', [
      'type' => 'mouse_right_click',
    ]);
    $this->factory(Paragraph::class)->define('mouse_right_up', [
      'type' => 'mouse_right_up',
    ]);
    $this->factory(Paragraph::class)->define('mouse_right_down', [
      'type' => 'mouse_right_down',
    ]);
    $this->factory(Paragraph::class)->define('scroll_click', [
      'type' => 'scroll_click',
    ]);
    $this->factory(Paragraph::class)->define('mouse_move', [
      'type' => 'mouse_move',
      'field_x' => '100',
      'field_y' => '100',
    ]);
    $this->factory(Paragraph::class)->define('wait', [
      'type' => 'wait',
      'field_time_for_humans' => '1 hour 15 min 10 sec',
    ]);
    $this->factory(Paragraph::class)->define('response_status_code', [
      'type' => 'response_status_code',
      'field_code' => 200,
    ]);
    $this->factory(Paragraph::class)->define('response_status_text', [
      'type' => 'response_status_text',
      'field_text' => 'ok',
    ]);
    $this->factory(Paragraph::class)->define('text_exist', [
      'type' => 'response_status_text',
      'field_text' => 'Lorem ipsum...',
    ]);
    $this->factory(Paragraph::class)->define('text_exist_regexp', [
      'type' => 'text_exist_regexp',
      'field_text' => 'Lorem ipsum...',
    ]);
    $this->factory(Paragraph::class)->define('element_exist', [
      'type' => 'element_exist',
      'field_element' => "some-element",
    ]);
    $this->factory(User::class)->define('john_doe', [
      'name' => 'John Doe',
      'pass' => 'qwerty',
      'status' => 1,
      'roles' => [],
    ]);

  }

}
