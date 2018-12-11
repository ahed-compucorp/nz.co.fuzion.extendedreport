<?php

require_once __DIR__ . '/../../../BaseTestClass.php';

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * ReportTemplate.Getmetadata API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_ReportTemplate_GetmetadataTest extends BaseTestClass implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testApiMetadata() {
    $ids = $this->createCustomGroupWithField(['CustomField' => ['data_type' => 'Int', 'default_value' => 2]], 'Pledge');
    $dateField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'data_type' => 'Date',
      'default_value' => '',
      'html_type' => 'Select Date',
      'name' => 'date_field',
      'label' => 'date_field',
    ]);
    $selectField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'data_type' => 'String',
      'default_value' => '',
      'html_type' => 'Select',
      'name' => 'select_field',
      'label' => 'select_field',
    ]);

    $multiSelectField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'data_type' => 'String',
      'default_value' => '',
      'html_type' => 'Multi-Select',
      'name' => 'multi_select_field',
      'label' => 'multi_select_field',
    ]);

    $booleanField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'data_type' => 'Boolean',
      'default_value' => '',
      'html_type' => 'Radio',
      'name' => 'bool_select_field',
      'label' => 'bool_select_field',
    ]);

    $result = civicrm_api3('ReportTemplate', 'Getmetadata', array('report_id' => 'pledge/details'))['values'];
    $filters = $result['filters'];
    foreach ($filters as $fieldName => $filter) {
      $this->assertEquals(TRUE, $filter['is_filters']);
      $this->assertEquals($result['metadata'][$fieldName], $filter);
      $knownNoFieldFilters = ['effective_date', 'tagid', 'gid', 'pledge_payment_status_id'];
      if (!in_array($fieldName, $knownNoFieldFilters)) {
        $this->assertEquals($result['fields'][$fieldName], $filter);
      }
    }
    $this->assertEquals(CRM_Report_Form::OP_INT, $filters['custom_' . $ids['custom_field_id']]['operatorType']);
    $this->assertEquals(CRM_Report_Form::OP_DATE, $filters['custom_' . $dateField['id']]['operatorType']);
    $this->assertEquals(CRM_Report_Form::OP_MULTISELECT, $filters['custom_' . $selectField['id']]['operatorType']);
    $this->assertEquals(CRM_Report_Form::OP_MULTISELECT_SEPARATOR, $filters['custom_' . $multiSelectField['id']]['operatorType']);
    $this->assertEquals('Pledge', $filters['custom_' . $multiSelectField['id']]['table_label']);
    $this->assertEquals(CRM_Report_Form::OP_SELECT, $filters['custom_' . $booleanField['id']]['operatorType']);

    foreach ([$dateField['id'], $ids['custom_field_id'], $selectField['id'], $multiSelectField['id'], $booleanField['id']] as $id) {
      $this->callAPISuccess('CustomField', 'delete', array('id' => $id));
    }
  }

  /**
   * Test getmetdata works on all reports.
   *
   * @dataProvider getAllNonLoggingReports
   */
  public function testApiMetadataAllReports($reportID) {
    $reportID = 'campaign/progress';
    $result = civicrm_api3('ReportTemplate', 'Getmetadata', array('report_id' => $reportID))['values'];
    $filters = $result['filters'];
    foreach ($filters as $fieldName => $filter) {
      $this->assertEquals(TRUE, $filter['is_filters']);
      $this->assertEquals($result['metadata'][$fieldName], $filter);
      $knownNoFieldFilters = ['effective_date', 'tagid', 'gid', 'pledge_payment_status_id'];
      if (!in_array($fieldName, $knownNoFieldFilters) && $filter['is_fields']) {
        $this->assertEquals($result['fields'][$fieldName], $filter);
        $this->assertTrue(!empty($filter['operatorType']), $fieldName . ' has no operator Type');
      }
    }
  }

}
