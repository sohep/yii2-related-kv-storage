# Related key-value storage for Yii2.

## Description

**Storage bases on MySQL table. It can be use to edit settings from admin panel.**

**This extension has two classes:**

+ [Config](#config-class) - storage for default settings
+ [RelatedConfig](#relatedconfig-class) - storage for individual settings

Feel free to let me know what else you want added via:

- [Issues](https://github.com/ancor-dev/yii2-related-kv-storage/issues)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ php composer.phar require ancor/yii2-related-kv-storage
```

or add

```
"ancor/yii2-related-kv-storage": "dev-master"
```

to the `require` section of your `composer.json` file.

## Config class

**Simple key-value storage with array-like access.**

### Features:

+ this class is the basis for RelatedConfig
+ array access to options (also countable and iterable)
+ this class can be use as yii component in $app container
+ this class is designed for inheritance and expansion
+ **autoload configuration** from database, when during instance creating

Storage bases on MySQL table. It can be use to edit settings from admin panel.

### Configuration

```php
'components' => [
    'config' => [
        'class' => 'ancor\relatedKvStorage\Config',

        // default settings
        // 'tableName'  => '{{config}}',
        // 'keyField'   => 'key',
        // 'valueField' => 'value',
    ]
]
```

### Usage

Simple usage
```php
Yii::$app->config['main-page.show-banner'] = true;

if (Yii::$app->config['main-page.show-banner']) { ... }
```

Iterable
```php
$config = Yii::$app->config;

foreach ($config as $key => $value) {
    echo $key . ' -> ' . $value . "\n";
}
```

Configurations  was automatically loaded when instance created.
But configurations will **not be save automatically.**
```php
Yii::$app->config->attributes = [
    'default.option-one' => true,
    'default.option-two' => false,
];

// Can be get, but didn't store
echo Yii::$app->config['default.option-one']; // true

// For convenience, let's make some variable
$config = Yii::$app->config;

// Save to database
$config->save();

// let's change any value
$config['default.option-one'] = false;
echo $config['default.option-one']; // false

// And now, imagine that we need to reset changed values to default.
// Reload from database. Please note, ->save() was not fired.
$config->reload();
echo $config['default.option-one']; // true

// If we need to array type
echo gettype($config->attributes); // array
```

## RelatedConfig class

**Simple key-value storage like [Config](#config-class), but related to other entity**

### Features:

+ This class inherits all features from [[\ancor\relatedKvStorage\Config]]
+ Ideal for storing user preferences, and preferences need to has default values

**The value is extracted in three steps**

1. Try to get *current* value from this(RelatedConfig) model.
2. Try to get *default* value from common config component(Yii::$app->config).
3. If the value has been not received will be returned null.

*Please, create instance with help Yii::createObject() for configure instance.*

### Configuration

**It is best to use through model.** Example for class User

```php
use ancor\relatedKvStorage\RelatedConfig

class User extends ... {

   ...

  /**
   * Get user configuration
   * @return RelatedConfig
   */
  public function getConfig() {
      $config = Yii::createObject([
          'class'      => RelatedConfig::className(),
          'relationId' => $this->id,

          // Default settings
          // 'tableName'           => '{{user_config}}',
          // 'relationIdField'     => 'user_id',
          // 'configComponentName' => 'config',
          // 'useCommonConfig'     => true,
      ]);
  }
}
```

### Usage

```php
// set default global settings (use Config class)
Yii::$app->config->attributes = [
   'user.dialogs.message-limit' => 100,
   'user.friends.limit => 20,
];

$user = new User();

$user->config = [

     // this option has not default value in global settings
    'user.dialogs.allow-modify' => true,

     // override default value from global settings
    'user.friends.limit' => 50,
];

// Must have! ( getConfig() reload don't cache `config` object and reload every time. So we didn't do it )
$user->config->save();

// use current value
echo $user->config['user.dialogs.allow-modify']; // true
// override default, use current value
echo $user->config['user.friends.limit']; // 50
// have not current, use default value
echo $user->config['user.dialogs.message-limit']; // 100
```