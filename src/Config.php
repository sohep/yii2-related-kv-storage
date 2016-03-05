<?php
/**
 * Created by Anton Korniychuk <ancor.dev@gmail.com>.
 */
namespace ancor\relatedKvStorage;

use Yii;
use yii\base\Object;
use yii\db\Query;
use yii\helpers\ArrayHelper;


/**
 * Simple key-value storage with array-like access;
 *
 */
class Config extends Object implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var mixed[] This is data of the model. key-value storage.
     */
    protected $values = [];
    /**
     * @var string[] Keys that will be deleted from database during ->save() will execute
     */
    protected $deleteKeys = [];

    /**
     * @var string Table name, which is a key-value storage. Curly brackets can be use.
     */
    public $tableName = '{{config}}';
    /**
     * @var string key field name. Without curly brackets!
     */
    public $keyField = 'key';
    /**
     * @var string value field name. Without curly brackets!
     */
    public $valueField = 'value';


    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->load();

        parent::init();
    } // end init()

    /**
     * Reload config. Syntax sugar
     */
    public function reload()
    {
        $this->load(true);
    } // end reload()

    /**
     * Load or reload(with revert) values from database
     *
     * @param bool $reload
     */
    public function load($reload = false)
    {
        if (!$reload && $this->values) return;

        $query = (new Query())->select(["{{{$this->keyField}}}", "{{{$this->valueField}}}"])->from($this->tableName);
        $this->customizeQuery($query);
        $values = $query->all();

        $values = ArrayHelper::map($values, $this->keyField, $this->valueField);
        $this->values = array_map('unserialize', $values);
    } // end load()

    /**
     * Save changes
     */
    public function save()
    {
        $data = array_map([$this, 'insertDataCallback'], array_keys($this->values), $this->values);

        $insertSql = Yii::$app->db->queryBuilder->batchInsert($this->tableName, $this->getInsertFields(), $data);
        $insertSql = "$insertSql ON DUPLICATE KEY UPDATE {$this->valueField} = VALUES({$this->valueField})";
        $deleteCondition = $this->getDeleteCondition();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $count = Yii::$app->db->createCommand($insertSql)->execute();
            $count += Yii::$app->db->createCommand()->delete($this->tableName, $deleteCondition)->execute();
            $this->deleteKeys = [];
            $transaction->commit();
        } catch (\yii\db\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $count ?? 0;
    } // end save()

    /**
     * Customize select Query
     * @param \yii\db\Query $query
     */
    protected function customizeQuery(Query $query)
    {
    } // end customizeQuery()

    /**
     * Customize insert Query
     * @param string $sql
     * @return string
     */
    protected function customizeInsert(string $sql)
    {
        return $sql;
    } // end customizeInsert()

    /**
     * Get fields list for select query
     * @return string[]
     */
    protected function getInsertFields()
    {
        return ['{{'.$this->keyField.'}}', '{{'.$this->valueField.'}}'];
    } // end getInsertFields()

    /**
     * Make and return one row with data for INSERT ... VALUES ... statement
     * @param string $key
     * @param mixed  $value
     * @return string[]
     */
    protected function insertDataCallback($key, $value)
    {
        return [
            $this->keyField   => $key,
            $this->valueField => serialize($value),
        ];
    } // end insertDataCallback()

    /**
     * Make and return DELETE statement condition.
     *
     * @return array condition
     */
    protected function getDeleteCondition()
    {
        return ['in', $this->keyField, $this->deleteKeys];
    } // end getDeleteCondition()



    function rewind() {
        reset($this->values);
    }
    function current() {
        return current($this->values);
    }
    function key() {
        return key($this->values);
    }
    function next() {
        next($this->values);
    }
    function valid() {
        return key($this->values) !== null;
    }



    public function count()
    {
        return count($this->values);
    } // end count()



    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }
    public function offsetUnset($offset)
    {
        if (isset($this->values[$offset])) {
            $this->deleteKeys[] = $offset;
        }

        unset($this->values[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->values[$offset] ?? null;
    }
}