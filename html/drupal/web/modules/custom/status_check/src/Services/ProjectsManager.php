<?php

namespace Drupal\status_check\Services;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

class ProjectsManager {

  protected $projects = [];

  protected $data = [
    'data' => [],
  ];

  /**
   * @param $ids
   *
   * @return $this
   */
  public function where($ids) {
    $project_ids_array = $ids;
    foreach (Node::loadMultiple($project_ids_array) as $project_result) {
      $this->projects[] = $project_result;
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getProjects() {
    return $this->projects;
  }

  /**
   * @param \Drupal\node\Entity\Node $project
   *
   * @return array
   */
  public function getProjectEnvironments(Node $project) {
    $environments = [];
    foreach ($project->get('field_environments') as $environment_field_index => $environment_field) {
      $environments[] = Paragraph::load($environment_field->get('target_id')->getValue());
    }
    return $environments;
  }

  /**
   * @param \Drupal\paragraphs\Entity\Paragraph $environment
   *
   * @return array
   */
  public function getEnvironmentChecks(Paragraph $environment) {
    $checks = [];
    foreach ($environment->get('field_checks') as $check_field_index => $check_field) {
      $checks[] = Paragraph::load($check_field->get('target_id')->getValue());
    }
    return $checks;
  }

  /**
   * @param \Drupal\paragraphs\Entity\Paragraph $check
   *
   * @return array
   */
  public function getCheckActions(Paragraph $check) {
    $actions = [];
    foreach ($check->get('field_actions') as $action_field_index => $action_field) {
      $actions[] = Paragraph::load($action_field->get('target_id')->getValue());
    }
    return $actions;
  }

  /**
   * @param \Drupal\paragraphs\Entity\Paragraph $check
   *
   * @return array
   */
  public function getCheckAssertions(Paragraph $check) {
    $assertions = [];
    foreach ($check->get('field_assertions') as $assertion_field_index => $assertion_field) {
      $assertions[] = Paragraph::load($assertion_field->get('target_id')->getValue());
    }
    return $assertions;
  }

  /**
   * @param \Drupal\paragraphs\Entity\Paragraph $action
   *
   * @return array|mixed[]
   */
  public function getActionFields(Paragraph $action) {
    return $action->toArray();
  }

  /**
   * @param \Drupal\paragraphs\Entity\Paragraph $assertion
   *
   * @return array|mixed[]
   */
  public function getAssertionFields(Paragraph $assertion) {
    return $assertion->toArray();
  }

  /**
   * @return array
   */
  public function toArray() {
    return $this->fillDataWithProjects()->data;
  }

  public function getIdsAndTitlesToArray() {
    foreach ($this->getProjects() as $project) {
      $this->data['data'][] = [
        'id' => $project->id(),
        'title' => $project->label(),
      ];
    }
    return $this->data['data'];
  }

  /**
   * @return $this
   */
  protected function fillDataWithProjects() {
    foreach ($this->projects as $project_index => $project) {
      $this->data['data'][$project_index] = [
        'id' => (int) $project->id(),
        'name' => $project->getTitle(),
        'environments' => [],
      ];
      $this->fillProjectWithEnvironments($project, $this->data['data'][$project_index]['environments']);
    }
    return $this;
  }

  /**
   * @param $project
   * @param $data
   *
   * @return $this
   */
  protected function fillProjectWithEnvironments($project, &$data) {
    foreach ($this->getProjectEnvironments($project) as $environment_index => $environment) {
      $data[$environment_index] = [
        'name' => $environment->get('field_name')->getString(),
        'checks' => []
      ];
      $this->fillEnvironmentWithChecks($environment, $data[$environment_index]['checks']);
    }
    return $this;
  }

  /**
   * @param $environment
   * @param $data
   *
   * @return $this
   */
  protected function fillEnvironmentWithChecks($environment, &$data) {
    foreach ($this->getEnvironmentChecks($environment) as $check_index => $check) {
      $data[$check_index] = [
        'name' => $check->get('field_name')->getString(),
        'actions' => [],
        'assertions' => [],
      ];
      $this->fillCheckWithActions($check, $data[$check_index]['actions']);
      $this->fillCheckWithAssertions($check, $data[$check_index]['assertions']);
    }
    return $this;
  }

  /**
   * @param $check
   * @param $data
   *
   * @return $this
   */
  protected function fillCheckWithActions($check, &$data) {
    foreach ($this->getCheckActions($check) as $action_index => $action) {
      $data[$action_index] = [
        'name' => $action->bundle(),
      ];
      $this->fillActionWithFields($action, $data[$action_index]);
    }
    return $this;
  }

  /**
   * @param $check
   * @param $data
   *
   * @return $this
   */
  protected function fillCheckWithAssertions($check, &$data) {
    foreach ($this->getCheckAssertions($check) as $assertion_index => $assertion) {
      $data[$assertion_index] = [
        'name' => $assertion->bundle(),
      ];
      $this->fillAssertionWithFields($assertion, $data[$assertion_index]);
    }
    return $this;
  }

  /**
   * @param $action
   * @param $data
   *
   * @return $this
   */
  protected function fillActionWithFields($action, &$data) {
    foreach ($this->getActionFields($action) as $field_index => $field) {
      if (substr($field_index, 0, 6) == "field_") {
        !empty($field[0]['value']) ? $data[$field_index] = $field[0]['value'] : NULL;

        if ($field_index === 'field_time_for_humans') {
          $data[$field_index] = strtotime($data[$field_index],0);
        }
      }
    }
    return $this;
  }

  /**
   * @param $assertion
   * @param $data
   *
   * @return $this
   */
  protected function fillAssertionWithFields($assertion, &$data) {
    foreach ($this->getAssertionFields($assertion) as $field_index => $field) {
      if (substr($field_index, 0, 6) == "field_") {
        !empty($field[0]['value']) ? $data[$field_index] = $field[0]['value'] : NULL;
      }
    }
    return $this;
  }

}
