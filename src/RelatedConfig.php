<?php
/**
 * Created by Anton Korniychuk <ancor.dev@gmail.com>.
 */
namespace ancor\relatedKvStorage;

use Yii;
use yii\db\Query;

/**
 * # Simple key-value storage like Config, but related to other entity.
 *
 * ### Features:
 *
 * + This class inherits all features from [[\ancor\relatedKvStorage\Config]]
 * + Ideal for storing user preferences, and preferences need to has default values
 *
 * **The value is extracted in three steps**
 *
 * 1. Try to get *current* value from this(RelatedConfig) model.
 * 2. Try to get *default* value from common config component(Yii::$app->config).
 * 3. If the value has been not received will be returned null.
 *
 * *Please, create instance with help Yii::createObject() for configure instance.*
 *
 * ### Configuration
 *
 * **It is best to use through model.** Example for class User
 * ```php
 * use ancor\relatedKvStorage\RelatedConfig
 *
 * class User extends ... {
 *
 *    ...
 *
 *   /**
 *    * Get user configuration
 *    * @return RelatedConfig
 *    * /
 *   public function getConfig() {
 *       $config = Yii::createObject([
 *           'class'      => RelatedConfig::className(),
 *           'relationId' => $this->id,
 *
 *           // Default settings
 *           // 'tableName'           => '{{user_config}}',
 *           // 'relationIdField'     => 'user_id',
 *           // 'configComponentName' => 'config',
 *           // 'useCommonConfig'     => true,
 *       ]);
 *   }
 * }
 * ```
 *
 * ### Usage
 *
 * // set default global settings
 * Yii::$app->config->attributes = [
 *    'user.dialogs.message-limit' => 100,
 *    'user.friends.limit => 20,
 * ];
 *
 * ```php
 * $user = new User();
 *
 * $user->config = [
 *
 *      // this option has not default value in global settings
 *     'user.dialogs.allow-modify' => true,
 *
 *      // override default value from global settings
 *     'user.friends.limit' => 50,
 * ];
 *
 * // Must have! ( getConfig() reload don't cache `config` object and reload every time. So we didn't do it )
 * $user->config->save();
 *
 * // use current value
 * echo $user->config['user.dialogs.allow-modify']; // true
 * // override default, use current value
 * echo $user->config['user.friends.limit']; // 50
 * // have not current, use default value
 * echo $user->config['user.dialogs.message-limit']; // 100
 * ```
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

    /**
     * Get all settings. Merged global with current.
     */
    public function getAttributes()
    {
        if ($this->useCommonConfig) {
            return array_merge($this->config->attributes, $this->values);
        }

        return $this->values;
    } // end getAttributes()
}
