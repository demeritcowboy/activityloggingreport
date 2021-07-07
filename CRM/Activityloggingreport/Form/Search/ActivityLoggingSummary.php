<?php
use CRM_Activityloggingreport_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Activityloggingreport_Form_Search_ActivityLoggingSummary extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  /**
   * @var array
   * The list of custom tables that are being logged.
   */
  private $customDataTables;

  function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->customDataTables = (new CRM_Logging_Schema())->entityCustomDataLogTables('Activity');
    $moreInfo = array();
    foreach ($this->customDataTables as $table => $logTable) {
      $moreInfo[$logTable] = $this->getMoreInfoAboutTable($table);
    }
    $this->customDataTables = $moreInfo;
  }

  /**
   * Given a custom data table name, get more info about its fields.
   * @todo I was originally ambitious but at the moment this is overkill.
   *
   * @param string $table
   * @return array
   */
  private function getMoreInfoAboutTable($table): array {
    $ret = array();
    $cg = \Civi\Api4\CustomGroup::get()->addSelect('title')
      ->addWhere('table_name', '=', $table)
      ->setLimit(1)
      ->execute()->first();
    if (!empty($cg['title'])) {
      $ret['title'] = $cg['title'] ?? '';
    }
    return $ret;
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('Activity Logging Summary'));

    $form->add('datepicker', 'start_date', ts('Start Date'), [], FALSE, ['time' => FALSE]);
    $form->add('datepicker', 'end_date', ts('End Date'), [], FALSE, ['time' => FALSE]);

    // Optionally define default search values
    $form->setDefaults(array(
      'start_date' => date('Y-m-d', strtotime('-1 week')),
      'end_date' => date('Y-m-d'),
    ));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('start_date', 'end_date'));
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Altered On') => 'log_date',
      E::ts('Action') => 'log_action',
      E::ts('Type') => 'activity_type',
      E::ts('Subject') => 'subject',
      E::ts('Altered By') => 'sort_name',
      E::ts('ID') => 'id',
      E::ts('Altered By ID') => 'altered_by_contact_id',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    // Default sort doesn't work because the selector controls it and we have
    // no access to that. It will always default to first column ASC.
    return $this->sql($this->select(), $offset, $rowcount, $sort ?? 'contact_a.log_date DESC', $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id AS id,
      contact_a.log_action AS log_action,
      contact_a.subject AS subject,
      contact_a.log_date AS log_date,
      ov_atype.label AS activity_type,
      c.sort_name AS sort_name,
      c.id AS altered_by_contact_id
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    // ugh there needs to be a table called contact_a, and it needs to have an id column and a sort_name column and also id can't be null, BUT at the same time we need a combo table so we can include custom fields, so it might as well be called contact_a.
    $from = "
      FROM (
        SELECT log_a.id, log_a.log_user_id, log_a.log_date, log_a.log_action, log_a.activity_type_id, log_a.subject, 'dummy' AS sort_name FROM log_civicrm_activity log_a
    ";
    $counter = 1;
    foreach ($this->customDataTables as $customLogTable => $info) {
      // Translation on subject is difficult. Ideally it would show what changed instead but that's also difficult.
      $escapedTitle = CRM_Core_DAO::escapeString($info['title'] . ' [' . E::ts('custom') . ']');
      $from .= "UNION SELECT logt{$counter}.entity_id AS id, logt{$counter}.log_user_id, logt{$counter}.log_date, logt{$counter}.log_action, NULL AS activity_type_id, '$escapedTitle' AS subject, 'dummy' AS sort_name FROM $customLogTable logt{$counter}
      ";
      $counter++;
    }
    $from .= ") AS contact_a
      LEFT JOIN civicrm_activity a ON contact_a.id = a.id
      LEFT JOIN civicrm_contact c ON c.id = contact_a.log_user_id
      LEFT JOIN civicrm_option_group og_atype ON (og_atype.name = 'activity_type')
      LEFT JOIN civicrm_option_value ov_atype ON (og_atype.id = ov_atype.option_group_id AND ov_atype.value = contact_a.activity_type_id)
    ";
    return $from;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();
    $clause = array();
    if (!empty($this->_formValues['start_date'])) {
      $clause[] = "contact_a.log_date >= %1";
      $params[1] = array($this->_formValues['start_date'], 'String');
    }
    if (!empty($this->_formValues['end_date'])) {
      $clause[] = "contact_a.log_date <= %2";
      $params[2] = array($this->_formValues['end_date'] . ' 23:59:59', 'String');
    }

    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
      return $this->whereClause($where, $params);
    }
    return ' (1) ';
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Activityloggingreport/Form/Search/ActivityLoggingSummary.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
  }
}
