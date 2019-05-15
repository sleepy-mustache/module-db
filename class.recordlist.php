<?php
namespace Module\DB;

require_once(dirname(__FILE__) . '/class.record.php');
require_once(dirname(__FILE__) . '/../../core/class.hooks.php');

/**
 * Create an iterable list of records
 *
  * ### Usage
 *
 * <code>
 *   $facilities = new \Module\DB\RecordList(new Facility(), 'active=1');
 *   foreach ($facilities as $f) {
 *     $f->columns['active'] = 0;
 *     $f->save();
 *   }
 * </code>
 *
 * ### Changelog
 *
 * ## Version 1.0
 * *
 *
 * @section dependencies Dependencies
 * * class.hooks.php
 * * class.db.php
 * * class.record.php
 *
 * @date May 10, 2019
 * @author Jaime A. Rodriguez <jaime@rodriguez-jr.com>
 * @version  1.0
 * @license  http://opensource.org/licenses/MIT
 **/

class RecordList extends \ArrayIterator {
  /**
   * PDO The PDO object
   */
  protected $db;

  public function __construct(Record $record, string $where = "") {
    $items = [];
    $key = \Sleepy\Hook::addFilter('record_list_key', $record->primaryKey);
    $table = \Sleepy\Hook::addFilter('record_list_table', $record->table);
    $where = \Sleepy\Hook::addFilter('record_list_where', $where);

    $this->db = DB::getInstance();

    if (!empty($where)) {
      $query = $this->db->prepare("SELECT `{$key}` FROM `{$table}` WHERE {$where}");
    } else {
      $query = $this->db->prepare("SELECT `{$key}` FROM `{$table}`");
    }

    $query->execute();
    $query->setFetchMode(\PDO::FETCH_ASSOC);

    foreach ($rows = $query->fetchAll() as $row) {
      $instance = clone $record;
      $instance->load($row[$key]);
      \Sleepy\Hook::addFilter("record_list_item", $instance);
      array_push($items, $instance);
    }

    parent::__construct($items);
  }
}