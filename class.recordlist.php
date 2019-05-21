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

  public function __construct(Record $record, string $where = "", array $data = []) {   
    $this->data = $data; 
    
    $items = [];
    
    $this->key   = \Sleepy\Hook::addFilter('record_list_key',   $record->primaryKey);
    $this->table = \Sleepy\Hook::addFilter('record_list_table', $record->table);
    $this->where = \Sleepy\Hook::addFilter('record_list_where', $where);

    $this->db = DB::getInstance();

    if (!empty($this->where)) {
      $query = $this->db->prepare("SELECT `{$this->key}` FROM `{$this->table}` WHERE {$this->where}");
    } else {
      $query = $this->db->prepare("SELECT `{$this->key}` FROM `{$this->table}`");
    }

    if (count($this->data)) {
      $query->execute($this->data);
    } else {
      $query->execute();
    }

    $query->setFetchMode(\PDO::FETCH_ASSOC);

    foreach ($rows = $query->fetchAll() as $row) {
      $instance = clone $record;
      $instance->load($row[$this->key]);
      \Sleepy\Hook::addFilter("record_list_item", $instance);
      array_push($items, $instance);
    }

    parent::__construct($items);
  }

  public function getTotal() {
    $data = $this->data;
    
    if (!empty($this->where)) {
      // We have to remove the offset and limit if they exist
      $where = preg_replace('/\s?(LIMIT ?[0-9]*(:limit)?)?\s(OFFSET ?[0-9]*(:offset)?)/i', '', $this->where);

      // Drop the data if they exist as well
      if (isset($data[':limit'])) unset($data[':limit']);
      if (isset($data[':offset'])) unset($data[':offset']);

      $query = $this->db->prepare("SELECT count(*) FROM `{$this->table}` WHERE {$where}");
    } else {
      $query = $this->db->prepare("SELECT count(*) FROM `{$this->table}`");
    }
    
    if (count($data)) {
      $query->execute($data);
    } else {
      $query->execute();
    }
    
    $results = $query->fetch();
    return $results[0];
  }
}