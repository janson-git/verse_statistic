<?php

namespace Verse\Statistic\View;

use Verse\Statistic\Core\Model\StatRecord;

/**
 * Class ColumnPack
 * 
 * Класс коллекции столбцов данных статистики
 * 
 * @package Statist
 */
class ColumnPack implements \Iterator {
    
    /**
     * @var ColumnData[]
     */
    public $columns = [];
    
    /**
     * @var ColumnData[][]
     */
    public $columnsByGroup = [];
    
    /**
     * @var ColumnData[][]
     */
    public $columnsByField = [];
    
    
    /**
     * @var ColumnData
     */
    public $sumColumn;
    /**
     * @var Dater
     */
    protected $dater;
    
    function __construct($dater)
    {
        $this->dater = $dater;
    }
    
    public function addColumn(ColumnData $column, $id = null)
    {
        
        if ($id) {
            $this->columns[$id] = $column;    
        } else {
            $this->columns[] = $column;
        }
        $column->columnPack = $this;
        
        $this->columnsByGroup[$column->group_id][$column->fieldId] = $column;
        $this->columnsByField[$column->fieldId][$column->group_id] = $column;
    }
    
    public function removeColumn(ColumnData $column)
    {
        
    }
    
    public function bindColumn($data)
    {
        $dater = $this->dater;
        $fieldsOrder = $dater->getFieldsOrder();
        $fieldsIdx = array_flip($dater->getFields());
        $grouping = $dater->getGrouping();
        $hideSubtitle = $dater->getGrouping()->isPrimaryGrouping();
        $names = $grouping->getDataRange();
            
        $keys = array_flip(array_keys($names));
            
        foreach ($data as $row) {
            $fId = $row[StatRecord::EVENT_ID] . '.' . $row[StatRecord::GROUP_ID];
            
            if (!isset($this->columns[$fId])) {
                $column = new ColumnData();
                
                $column->fieldId  = $row[StatRecord::EVENT_ID];
                $column->group_id = $row[StatRecord::GROUP_ID];
                $column->field    = $fieldsIdx[$column->fieldId];
                $column->title    = $fieldsIdx[$column->fieldId];
                $column->order    = ($fieldsOrder[$column->fieldId]+1)*10000 + @$keys[$column->group_id];
    
                if ($hideSubtitle) {
                    $column->subTitle = '';
                } else {
                    $column->subTitle = $names[$column->group_id] ?? '#'.$column->group_id;
                }
                
                $this->addColumn($column, $fId);
            } else {
                $column = $this->columns[$fId];
            }
        
            $column->data[$row[StatRecord::TIME]]    = $row[StatRecord::COUNT];
            $column->dataUnq[$row[StatRecord::TIME]] = $row[StatRecord::COUNT_UNQ];
        }
        
    }
    
    
    public function boot () 
    {
        usort($this->columns, function (ColumnData $a,ColumnData  $b) {
            if ($a->order !== null && $b->order !== null) {
                return $a->order > $b->order ? 1 : -1;
            }
            
            return strnatcmp($a->title, $b->title);
        });
    
        foreach ($this->columnsByGroup as $grId => &$columnList) {
            usort($columnList, function (ColumnData $a,ColumnData  $b) {
                if ($a->order !== null && $b->order !== null) {
                    return $a->order > $b->order ? 1 : -1;
                }
                
                return strnatcmp($a->title, $b->title);
            });
            
            $prev = null;
            foreach ($columnList as $column) {
                if ($prev == null) {
                    $prev = $column;
                    continue;
                }
    
                $prev->rightColumn = $column;
                $prev = $column;
            }
        }
    
        foreach ($this->columns as $column) {
            $column->boot();
        }
        
    }
    
    public function minusRight () 
    {
    }
    
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->columns);
    }
    
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->columns);
    }
    
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->columns);
    }
    
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return is_object($this->current());
    }
    
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->columns);
    }
}
