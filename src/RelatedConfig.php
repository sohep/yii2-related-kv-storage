<?php
/**
 * Created by Anton Korniychuk <ancor.dev@gmail.com>.
 */
namespace ancor\relatedKvStorage;

use Yii;
use yii\db\Query;

/**
 * This class can be use as model. Please, create instance with help Yii::createObject() for configure instance.
 */
class RelatedConfig extends Config
{
    /**
     * @var Config common config component
     */
    protected $config;

    public $tableName = '{{user_config}}';
    /**
     * @var integer id from related table
     */
    public $relationId;
    /**
     * @var string relation field in this table
     */
    public $relationIdField = 'user_id';
    /**
     * @var string common config component name in yii2 DI container
     */
    public $configComponentName = 'config';
    /**
     * @var bool user common config component, if this config has not requested key
     */
    public $useCommonConfig = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
    	parent::init();
        $this->config = Yii::$app->{$this->configComponentName};
    } // end init()


    /**
     * Add relation_field to INSERT statement.
     * Now columns: (key, value, relation_field)
     * @inheritdoc
     */
    protected function getInsertFields()
    {
    	$fields = parent::getInsertFields();
        $fields[] = $this->relationIdField;
        return $fields;
    } // end getInsertFields()

    /**
     * We has 3 columns at INSERT, and we must add thirst column to inserted data
     * @inheritdoc
     */
    protected function insertDataCallback($key, $value)
    {
    	$fields = parent:: insertDataCallback($key, $value);
        $fields[$this->relationIdField] = $this->relationId;

        return $fields;
    } // end  insertDataCallback()

    /**
     * Add WHERE condition to select statement. This need for select select keys only for ONE related item(model)
     * @inheritdoc
     */
    protected function customizeQuery(Query $query)
    {
        $query->where([$this->relationIdField => $this->relationId]);
    } // end customizeQuery()

    /**
     * Add relation field to delete condition. Otherwise this key will be deleted many rows which contains this rows
     * @inheritdoc
     */
    protected function getDeleteCondition()
    {
    	$cond = parent::getDeleteCondition();

        return ['and', $cond, [$this->relationIdField => $this->relationId]];
    } // end getDeleteCondition()

    /**
     * Redeclare for use common config component, it can use
     * @param string|number $offset key.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!$this->useCommonConfig || !$this->config) return parent::offsetGet($offset);

        return $this->values[$offset] ?? $this->config[$offset] ?? null;
    }
}
