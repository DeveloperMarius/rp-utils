<?php

namespace utils\dbmanager;

use BackedEnum;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Pecee\Http\Input\Attributes\Route;
use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\InputValidator;
use Pecee\SimpleRouter\SimpleRouter;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use UnitEnum;
use utils\dbmanager\attributes\ForeignKeyProperty;
use utils\dbmanager\attributes\PlaintextProperty;
use utils\dbmanager\attributes\PrimaryKeyProperty;
use utils\dbmanager\attributes\ReadonlyProperty;
use utils\dbmanager\attributes\UUIDv4Property;
use utils\dbmanager\types\DBTypeBoolean;
use utils\dbmanager\types\DBTypeFloat;
use utils\dbmanager\types\DBTypeInt;
use utils\dbmanager\types\DBTypeString;
use utils\dbmanager\types\DBTypeTinyInt;
use utils\exception\ModelException;
use utils\exception\SQLException;
use utils\Inflector;
use utils\mail_config;
use utils\router\utils\RouterUtils;
use utils\Time;
use utils\UnixTimestamp;
use utils\Util;

/**
 * @template T
 */
abstract class DatabaseModel implements JsonSerializable{

    /**
     * @var string|null $_objectClass
     */
    protected static ?string $_objectClass = null;
    /**
     * @var string|null $_dbTable
     */
    protected static ?string $_dbTable = null;
    /**
     * @var bool $htmlSpecialChars
     */
    protected static bool $htmlSpecialChars = true;
    /**
     * @var bool $_hasId
     */
    protected static bool $_hasId = true;
    /**
     * @var bool $_isUsingUuid
     */
    protected static bool $_isUsingUuid = false;
    /**
     * @var bool $_isUsingUuid
     */
    protected static bool $_isUsingBinUuid = false;
    /**
     * @var bool $_dynamicProperties
     */
    protected static bool $_dynamicProperties = false;
    /**
     * @var array|null $_property_cache
     */
    protected static ?array $_property_cache = null;

    /* Context */

    /**
     * @var string|null $_context
     */
    protected static ?string $_context = null;

    /**
     * @return string|null
     */
    protected static function _getContext(): ?string{
        return static::$_context;
    }

    /* DatabaseModel */

    /**
     * DatabaseModel constructor.
     * @param array|null $data
     */
    public function __construct(?array $data = null){
        if($data !== null){
            $this->setProperties($data);
        }else{
            $reflection = new ReflectionClass(static::class);
            $_properties = $reflection->getProperties();
            $properties = array();
            foreach($_properties as $property){
                if(!$property->isStatic() && !$property->isPrivate())
                    $properties[$property->getName()] = $property->getValue($this);
            }
            $this->setProperties($properties);
        }
    }

    /**
     * @param array $data
     * @return void
     * @throws ModelException
     */
    private function setProperties(array $data): void{
        foreach ($data as $key => $value){
            try{
                $property = new ReflectionProperty($this, $key);
                if(static::$htmlSpecialChars && is_string($value)){
                    $attributes = $property->getAttributes(PlaintextProperty::class);
                    if(sizeof($attributes) === 0)
                        $value = htmlspecialchars($value);
                }
                if($property->getType() === null)
                    throw new ModelException('Property type not defined in model for ' . $key);
                if($property->getType()->isBuiltin()){
                    $value = match ($property->getType()->getName()){
                        'bool' => $value !== null && in_array($value, array(0, 1, 'false', 'true')) ? in_array($value, array(1, 'true')) : null,
                        default => $value
                    };
                }else if(!is_object($value)){
                    $type_name = $property->getType()->getName();
                    try{
                        if(enum_exists($type_name)){
                            if(is_subclass_of($type_name, BackedEnum::class)){
                                /**
                                 * @var BackedEnum $type_name
                                 */
                                $value = $type_name::from($value);
                            }else{
                                /**
                                 * @var UnitEnum $type_name
                                 */
                                foreach ($type_name::cases() as $case) {
                                    if(strtoupper($value) === strtoupper($case->name)){
                                        $value = $case;
                                        break;
                                    }
                                }
                            }
                        }else{
                            $value = match ($property->getType()->getName()){
                                UnixTimestamp::class => $value === null ? null : UnixTimestamp::createFromTimestamp($value),
                                Time::class => $value === null ? null : new Time($value),
                                default => is_int($value) && method_exists($type_name, 'getById') ? $type_name::getById($value) : $value
                            };
                        }
                        /*$reflectionClass = new ReflectionClass($type_name);
                        if($reflectionClass->isEnum()){
                            $value = $type_name::from($value);
                        }else{
                            $value = match ($property->getType()->getName()){
                                UnixTimestamp::class => $value === null ? null : UnixTimestamp::createFromTimestamp($value),
                                Time::class => $value === null ? null : new Time($value),
                                default => is_int($value) && method_exists($type_name, 'getById') ? $type_name::getById($value) : $value
                            };
                        }*/
                    } catch(ReflectionException $e) {
                    }
                }
            }catch(ReflectionException $e){}
            $this->$key = $value;
            $obj_key = $key . '_object';
            if(isset($this->$obj_key)){
                unset($this->$obj_key);
            }
        }
    }

    /**
     * @return string
     */
    protected static function getObjectClass(): string{
        if(!isset(static::$_objectClass)){
            $called_class = get_called_class();
            if($called_class === '')
                throw new ModelException('get_called_class() is null');
            return $called_class;
        }
        return static::$_objectClass;
    }

    /**
     * @return string
     */
    protected static function getModelName(): string{
        $path = explode('\\', self::getObjectClass());
        return end($path);
    }

    /**
     * @return null|string
     */
    private static function getDbTable(): null|string{
        if(isset(static::$_dbTable)){
            return static::$_dbTable;
        }
        $table = Util::replaceFromCamelCase((string) str_replace('Model', '', lcfirst(self::getModelName())));
        $table = explode('_', $table);
        $table[sizeof($table)-1] = Inflector::pluralize(end($table));
        return join('_', $table);
    }

    /**
     * @return bool
     */
    public static function hasId(): bool{
        return static::$_hasId;
    }

    /**
     * @return bool
     */
    private static function isUsingUuid(): bool{
        return static::$_isUsingUuid;
    }

    /**
     * @return bool
     */
    private static function isUsingBinUuid(): bool{
        return static::$_isUsingBinUuid;
    }

    /**
     * @return bool
     */
    private static function hasDynamicProperties(): bool{
        return static::$_dynamicProperties;
    }

    /**
     * @return ReflectionProperty[]
     */
    public static function getProperties(): array{
        $reflection = new ReflectionClass(static::class);
        $_properties = $reflection->getProperties();
        $properties = array();
        foreach($_properties as $property){
            if($property->isProtected() && !$property->isStatic() && !str_starts_with($property->getName(), '_')){
                $properties[] = $property;
            }
        }
        return $properties;
    }

    /**
     * @param ReflectionProperty $property
     * @return array|null
     * @throws ReflectionException
     */
    #[ArrayShape([
        'type' => 'string',
        'nullable' => 'bool'
    ])]
    private static function getRealTypeForProperty(ReflectionProperty $property): ?array{
        if($property->getType() !== null){
            $type = null;
            if(!$property->getType()->isBuiltin()){
                $property_type_name = $property->getType()->getName();
                if(class_exists($property_type_name)){
                    $type_class = new ReflectionClass($property_type_name);
                    if($type_class->isEnum()){
                        $type_class = new \ReflectionEnum($property_type_name);
                        $backing_type = $type_class->getBackingType();
                        if($backing_type instanceof \ReflectionNamedType)
                            $type = $backing_type->getName();
                        else
                            $type = 'int';
                    }else{
                        $type = 'int';
                    }
                    if(is_subclass_of($property_type_name, DatabaseModel::class)){
                        foreach($property_type_name::getProperties() as $property){
                            if(sizeof($property->getAttributes(PrimaryKeyProperty::class)) > 0){
                                $found_type = $property->getType();
                                if($found_type !== null && $found_type->isBuiltin()){
                                    $type = $found_type->getName();
                                    break;
                                }
                            }
                        }
                    }
                }else{
                    //Class not found
                    return null;
                }
            }else{
                $type = $property->getType()->getName();
            }
            return array(
                'type' => $type,
                'nullable' => $property->getType()->allowsNull()
            );
        }
        return null;
    }

    /**
     * @return array|null
     */
    public static function getPropertyCache(): ?array{
        if(isset(static::$_property_cache)){
            return static::$_property_cache;
        }
        $properties = array();
        foreach(self::getProperties() as $property){
            $readonlyAttributes = $property->getAttributes(ReadonlyProperty::class);
            $propertyAttributes = $property->getAttributes(ValidatorAttribute::class);

            /* @var ValidatorAttribute|null $routeAttribute */
            $routeAttribute = null;

            if(sizeof($propertyAttributes) > 0){
                /**
                 * @var $routeAttribute ValidatorAttribute
                 */
                $routeAttribute = $propertyAttributes[0]->newInstance();
                if($routeAttribute->getName() === null)
                    $routeAttribute->setName($property->getName());
                if($routeAttribute->getType() === null && $property->getType() !== null){
                    if(!$property->getType()->isBuiltin()){
                        $property_type_name = $property->getType()->getName();
                        if(class_exists($property_type_name)){
                            $type_class = new ReflectionClass($property_type_name);
                            if($type_class instanceof UnixTimestamp){
                                $routeAttribute->setType('int');
                            }else if($type_class instanceof Time){
                                $routeAttribute->setType('string');
                            }else if($type_class->isEnum()){
                                $type_class = new \ReflectionEnum($property_type_name);
                                if($type_class->getBackingType() instanceof \ReflectionNamedType)
                                    $routeAttribute->setType($type_class->getBackingType()->getName());
                                else
                                    $routeAttribute->setType('int');
                            }else if(is_subclass_of($property_type_name, DatabaseModel::class)){
                                $classlist = explode('\\', $property->getType()->getName());
                                if(!in_array('model:' . $property->getType()->getName(), $routeAttribute->getFullValidator())
                                    && !in_array('model:' . end($classlist), $routeAttribute->getFullValidator()))
                                    $routeAttribute->addValidator('model:' . $property->getType()->getName());
                                $routeAttribute->setType('int');
                            }else{
                                $routeAttribute->setType('int');
                            }
                        }else{
                            //Class not found
                        }
                    }else{
                        $routeAttribute->setType($property->getType()->getName());
                    }
                    if($property->getType()->allowsNull())
                        $routeAttribute->addValidator('nullable');
                }
            }

            $properties[$property->getName()] = (object)[
                'readonly' => !$property->isReadOnly() && sizeof($readonlyAttributes) === 0,
                'validator' => $routeAttribute
            ];
        }

        //static::$_property_cache = $properties;
        return $properties;
    }

    /**
     * @return DBManager
     */
    public static function getDBManager(): DBManager{
        $db_manager = DBManager::getDBManager(self::_getContext());
        if($db_manager === null){
            $db_manager = DBManager::getDBManager();
        }
        $db_manager->init();
        if(self::getDbTable() !== null)
            $db_manager->construct(self::getDbTable());
        $db_manager->setTableStructure(self::toDbTable());
        //$db_manager->setId(self::hasId())->setUseUuid(self::isUsingUuid())->setUseBinUuid(self::isUsingBinUuid());
        return $db_manager;
    }

    /**
     * @return DBManager
     */
    #[Deprecated(
        reason: 'Deprecated',
        replacement: '%class%::getDBManager()'
    )]
    public static function getDatabaseManager(): DBManager{
        return self::getDBManager();
    }

    /**
     * @param array $data
     * @return bool
     * @throws InputValidationException
     * @throws SQLException
     */
    public static function create(array $data): bool{
        self::validate($data, true);
        self::getDBManager()->insert($data);
        return true;
    }

    /**
     * @param array $data
     * @return T
     * @throws InputValidationException
     * @throws ModelException
     * @throws SQLException
     */
    public static function createWithCallback(array $data): object{
        self::validate($data, true);
        if(!self::hasId())
            throw new ModelException('createWithIdCallback only works for models with ids');
        $callback = self::getDBManager()->setReturning('id')->insert($data)->getReturningValue();
        return static::getById($callback);//new static($callback);
    }

    /**
     * @param array $data
     * @return int|string|false
     * @throws InputValidationException
     * @throws ModelException
     * @throws SQLException
     */
    public static function createWithIdCallback(array $data): int|string|bool{
        self::validate($data, true);
        if(!self::hasId())
            throw new ModelException('createWithIdCallback only works for models with ids');
        return self::getDBManager()->insert($data)->getLastInsertRow()['id'];
    }

    /**
     * @param array $data
     * @return bool
     * @throws InputValidationException
     * @throws SQLException
     */
    #[Deprecated(
        reason: 'Use "_hasId = false" and create()',
        replacement: '%class::create(%parameter0%%)'
    )]
    public static function createWithoutId(array $data): bool{
        self::validate($data, true);
        self::getDBManager()->setId(false)->insert($data);
        return true;
    }

    /**
     * @param array $set
     * @param array|null $where - id by default
     * @return bool
     * @throws InputValidationException
     * @throws ModelException
     * @throws SQLException
     */
    public function update(array $set, array $where = null): bool{
        self::validate($set);
        self::getDBManager()->where($where === null ? array('id' => $this->getId()) : $where)->update($set);
        $this->setProperties($set);
        return true;
    }

    /**
     * @param array $set
     * @param array $where
     * @return bool
     * @throws InputValidationException
     * @throws SQLException
     */
    public static function update_(array $set, array $where): bool{
        self::validate($set);
        self::getDBManager()->where($where)->update($set);
        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array|null $where
     * @return bool
     * @throws InputValidationException
     * @throws ModelException
     * @throws SQLException
     */
    public function set(string $key, mixed $value, array $where = null): bool{
        self::validate(array($key => $value));
        self::getDBManager()->where($where === null ? array('id' => $this->getId()) : $where)->update(array($key => $value));
        $this->setProperties(array(
            $key => $value
        ));
        return true;
    }

    /**
     * @return array
     */
    public function toArray(bool $cleanData = false): array{
        $vars = get_object_vars($this);
        foreach($vars as $key => $value){
            $property = new ReflectionProperty($this, $key);
            if(static::$htmlSpecialChars && is_string($value)){
                $attributes = $property->getAttributes(PlaintextProperty::class);
                if(sizeof($attributes) === 0)
                    $vars[$key] = htmlspecialchars_decode($value);
            }
        }
        if(!$cleanData){
            $vars['__type__'] = self::getModelName();
            if(isset($vars['password']))
                $vars['password'] = '<**Protected Property**>';
        }
        return $vars;
    }

    /**
     * @return string
     */
    public function __toString(): string{
        return self::class . ': ' .  json_encode($this->toArray());
    }

    #[Pure]
    public function jsonSerialize(): array{
        return static::toArray();
    }

    /**
     * @param mixed $object
     * @return bool
     */
    public function equals(mixed $object): bool{
        $object_id = null;
        if(method_exists($object, 'getId'))
            $object_id = $object->getId();
        else if($object->id !== null)
            $object_id = $object->id;
        return $this->getId() === $object_id;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed{
        if(str_starts_with($name, 'get')){
            $val_name = Util::replaceFromCamelCase(lcfirst(str_replace('get', '', $name)));
            if(str_ends_with($val_name, '_object')){
                $val_object_name = $val_name;
                $val_name = str_replace('_object', '', $val_name);
                if($this->$val_name === null)
                    return null;
                if(!isset($this->$val_object_name) && $this->getDBManager()->getModelNamespace() !== null){
                    $className = $this->getDBManager()->getModelNamespace() . '\\' . ucfirst(Util::replaceToCamelCase($val_name));
                    if(method_exists($className, 'getById'))
                        $this->$val_object_name = $className::getById($this->$val_name);
                }
                return $this->$val_object_name;
            }else{
                return $this->$val_name;
            }
        }else if(str_starts_with($name, 'is')){
            $name = Util::replaceFromCamelCase(lcfirst(str_replace('is', '', $name)));
            $name2 = 'is_' . $name;
            return isset($this->$name) ? $this->$name === 1 || $this->$name === true : (isset($this->$name2) ? $this->$name2 === 1 || $this->$name2 === true : null);
        }else if(str_starts_with($name, 'set')){
            $name = Util::replaceFromCamelCase(lcfirst(str_replace('set', '', $name)));
            $value = $arguments[0];
            return $this->set($name, $value);
        }else if(str_starts_with($name, 'has')){
            $name = Util::replaceFromCamelCase(lcfirst(str_replace('has', '', $name)));
            return $this->$name !== null;
        }
        return null;
    }

    /**
     * @param array|null $where
     * @return bool
     * @throws SQLException
     */
    public function delete(?array $where = null): bool{
        self::getDBManager()->where($where === null ? array('id' => $this->getId()) : $where)->delete();
        return true;
    }

    /**
     * @param array $properties
     * @return array
     */
    public static function getValidators(array $properties): array{
        $validators = array();
        foreach($properties as $property_name => $validator){
            if(is_int($property_name)){
                $property_name = $validator;
                $validator = null;
            }
            if($validator === null){
                try{
                    $property = new ReflectionProperty(static::class, $property_name);
                    $propertyAttributes = $property->getAttributes(ValidatorAttribute::class);
                    if(sizeof($propertyAttributes) > 0){
                        /* @var ValidatorAttribute $routeAttribute */
                        $routeAttribute = $propertyAttributes[0]->newInstance();
                        $validator = $routeAttribute->getFullValidator();
                    }
                } catch(ReflectionException $e) {}
            }
            $validators[$property_name] = $validator ?? 'required';
        }
        return $validators;
    }

    /**
     * @param string $property_name
     * @param array $data
     * @param array $validators
     * @param array $filter_data
     * @param bool $create
     * @return void
     */
    private static function validateProperty(string $property_name, array $data, array &$validators, array &$filter_data, bool $create = false){
        if(isset(self::getPropertyCache()[$property_name])){
            if($create || self::getPropertyCache()[$property_name]->readonly){
                $validators[$property_name] = self::getPropertyCache()[$property_name]->validator->getFullValidator();
                $filter_data[$property_name] = self::toValidatorValue($data[$property_name]);
            }
        }else if(self::hasDynamicProperties()){
            $filter_data[$property_name] = self::toValidatorValue($data[$property_name]);
        }
    }

    /**
     * @param mixed $value
     * @return int|mixed|string
     */
    private static function toValidatorValue(mixed $value){
        if($value instanceof \BackedEnum){
            return $value->value;
        }else if($value instanceof \UnitEnum){
            return $value->name;
        }else if($value instanceof DatabaseModel &&$value::hasId()){
            return $value->getId();
        }else if($value instanceof UnixTimestamp){
            return $value->getMilliseconds(true);
        }else if($value instanceof Time){
            return $value->format('Y-m-d H:i:s');
        }
        return $value;
    }

    /**
     * @param array $data
     * @param bool $create
     * @return bool
     * @throws InputValidationException
     */
    public static function validate(array $data, bool $create = false): bool{
        $validators = array();
        $filter_data = array();
        if(self::hasDynamicProperties()){
            foreach($data as $key => $value){
                if(array_key_exists($key, self::getPropertyCache())){
                    self::validateProperty($key, $data, $validators, $filter_data, $create);
                }else{
                    $filter_data[$key] = self::toValidatorValue($value);
                }
            }
        }else {
            foreach(array_keys(self::getPropertyCache()) as $property_name){
                if(array_key_exists($property_name, $data)){
                    self::validateProperty($property_name, $data, $validators, $filter_data, $create);
                }
            }
        }
        if(sizeof($filter_data) === 0)
            throw new InputValidationException('Nothing to update');
        //if(sizeof($data) > $validators)
        //    throw new InputValidationException('Input value present without validator rule');
        return InputValidator::make()->setRules($validators)->validateData($filter_data)->passes();
    }

    /* static */

    /**
     * @param int|string $id
     * @return T
     * @throws ModelException
     * @throws SQLException
     */
    public static function getById(int|string $id): object{
        return self::getDBManager()->where(array('id' => $id))->fetchObject(self::getObjectClass());
    }

    /**
     * @param array $where
     * @return T
     * @throws ModelException
     * @throws SQLException
     */
    public static function get(array $where): object{
        return self::getDBManager()->where($where)->fetchObject(self::getObjectClass());
    }

    /**
     * @param array $where
     * @return array
     * @throws SQLException
     */
    public static function getAllPlain(array $where = array()): array{
        return self::getDBManager()->where($where)->fetchAll();
    }

    /**
     * @param array $where
     * @param int|null $limit
     * @param bool $order_desc
     * @param string|int|null $order_by
     * @param int|null $offset
     * @return T[]
     * @throws ModelException
     * @throws SQLException
     */
    public static function getAll(array $where = array(), ?int $limit = null, bool $order_desc = false, string|int|null $order_by = null, ?int $offset = null): array{
        $request = self::getDBManager()->where($where)->setLimit($limit, $offset);
        if($order_desc)
            $request->setOrderDESC();
        if($order_by !== null)
            $request->setOrderBy($order_by);
        return $request->fetchObjects(self::getObjectClass());
    }

    /**
     * @param array $where
     * @return bool
     * @throws SQLException
     */
    public static function exists(array $where = array()): bool{
        return self::getDBManager()->where($where)->exist();
    }

    /**
     * @param array $where
     * @return bool
     * @throws SQLException
     */
    public static function delete_(array $where = array()): bool{
        self::getDBManager()->where($where)->delete();
        return true;
    }

    public static function generateTypescriptType(): string{
        $data = "export type " . self::getModelName() . " = {\n";
        foreach(self::getPropertyCache() as $key => $value){
            /**
             * @var ValidatorAttribute $validatorAttribute
             */
            $validatorAttribute = $value->validator;
            if($validatorAttribute === null)
                continue;
            $nullable = in_array('nullable', $validatorAttribute->getValidator());
            $readonly = !$value->readonly;
            $type = match ($validatorAttribute->getValidatorType()){
                'integer','float' => 'number',
                default => $validatorAttribute->getValidatorType()
            };
            $data .= "    " . ($readonly ? 'readonly ' : '') . $validatorAttribute->getName() . ': ' . $type . ($nullable ? '|null' : '') . ";\n";
        }
        $data .= "};";
        return $data;
    }

    /**
     * @return DBTable
     * @throws ReflectionException
     */
    public static function toDbTable(){
        $columns = array();
        foreach(self::getProperties() as $property){
            $real_type = self::getRealTypeForProperty($property);
            $type = match($real_type['type']){
                'string' => new DBTypeString(),
                'int' => new DBTypeInt(),
                'bool' => new DBTypeBoolean(),
                'float' => new DBTypeFloat(),
                default => null
            };
            if($type === null) continue;
            if($real_type['nullable'])
                $type->setDbTypeDefaultNull();
            $column = new DBColumn($property->getName(), $type);
            $attributes = $property->getAttributes();
            foreach($attributes as $attribute){
                switch($attribute->getName()){
                    case PrimaryKeyProperty::class:
                        $column->setPrimaryKey(true);
                        break;
                    case ForeignKeyProperty::class:
                        /**
                         * @var $foreign_key_property ForeignKeyProperty
                         */
                        $foreign_key_property = $attribute->newInstance();
                        $column->setForeignKey(true, $foreign_key_property->getReferencesColumn(), $foreign_key_property->getReferencesTable(), $foreign_key_property->getOnDelete(), $foreign_key_property->getOnUpdate());
                        break;
                    case ReadonlyProperty::class:
                        //Throw error on before insert
                        break;
                }
            }
            if($column->isPrimaryKey() && !$column->hasForeignKey())
                $column->setAutoIncrement(true);
            foreach($attributes as $attribute){
                switch($attribute->getName()){
                    case UUIDv4Property::class:
                        $uuid_property = $attribute->newInstance();
                        if($uuid_property->isBinary()){
                            $column->applyBeforeResponse(DBFunction::BIN_TO_UUID);
                            $column->applyBeforeInsert(DBFunction::UUID_TO_BIN);
                        }
                        //if(sizeof(array_filter($attributes, fn($attribute) => $attribute->getName() === PrimaryKeyProperty::class)) > 0)
                        //    $column->setDefaultGenerator(fn() => Util::generateUuid());
                        break;
                }
            }
            $columns[] = $column;
        }
        return new DBTable(self::getDbTable(), $columns);
    }

    use RouterUtils;

    /**
     * @param ...$args
     * @return array
     */
    private function buildRequestWhere(...$args): array{
        $id = !empty($args) ? end($args) : null;//string|int|null $id
        $properties = array_keys(self::getPropertyCache());

        $where = array();
        if($id !== null)
            $where['id'] = $id;
        $where += $this->request()->getInputHandler()->values(array(), 'get');
        $where = array_filter($where, fn($key) => !in_array($key, array('page', 'length', 'sort_by', 'order_by', 'mask')) && in_array($key, $properties), ARRAY_FILTER_USE_KEY);

        $buildWhere = array_map(function($value, $key){
            preg_match('/(?:(gt|lt|gte|lte|in):)?(.+)/', $value, $matches);
            if(sizeof($matches) === 3 && !empty($matches[1])){
                $matches[1] = strtolower($matches[1]);
                return new DBManagerOperator($key, $matches[1] === 'in' ? '%' . $value . '%' : $value, match ($matches[1]){
                    'gt' => DBManagerOperator::OPERATOR_BIGGER,
                    'gte' => DBManagerOperator::OPERATOR_BIGGER_EQUAL,
                    'lt' => DBManagerOperator::OPERATOR_SMALLER,
                    'lte' => DBManagerOperator::OPERATOR_SMALLER_EQUAL,
                    'in' => DBManagerOperator::OPERATOR_LIKE,
                    default => DBManagerOperator::OPERATOR_EQUAL
                });
            }
            return new DBManagerOperator($key, $value, DBManagerOperator::OPERATOR_EQUAL);
        }, $where, array_keys($where));
        return array_values($buildWhere);
    }

    #[Route(Route::POST, '/')]
    public function postRequest(){
        $callback = static::createWithCallback($this->request()->getInputHandler()->values());
        $this->success(array(
            'callback' => $callback
        ));
    }

    #[Route(Route::DELETE, '/{id?}')]
    public function deleteRequest(...$args){
        $id = end($args);//string|int|null $id
        $where = $this->buildRequestWhere($id);

        static::getDBManager()->where($where)->delete();
    }

    #[Route(Route::PATCH, '/{id?}')]
    public function patchRequest(...$args){
        $id = end($args);//string|int|null $id
        $where = $this->buildRequestWhere($id);

        static::update_($this->request()->getInputHandler()->values(array(), 'patch'), $where);
    }

    #[Route(Route::GET, '/{id?}')]
    public function getRequest(...$args){
        $id = end($args);//string|int|null $id
        $properties = array_keys(self::getPropertyCache());

        $page = $this->request()->getInputHandler()->get('page')->getValue() ?? 1;
        //$offset = $this->request()->getInputHandler()->get('offset')->getValue();
        $length = $this->request()->getInputHandler()->get('length')->getValue() ?? 10;
        $sort_by = $this->request()->getInputHandler()->get('sort_by')->getValue();
        $order_by = $this->request()->getInputHandler()->get('order_by')->getValue();//asc|desc
        $mask = $this->request()->getInputHandler()->get('mask')->getValue();
        if($mask !== null){
            $mask = explode(',', $mask);
            $mask = array_map(fn($value) => trim($value), $mask);
            $mask = array_filter($mask, fn($value) => !empty($value));

            $mask = array_filter($mask, fn($value) => in_array($value, $properties));
        }
        $where = $this->buildRequestWhere($id);


        /**
         * @var $callback DatabaseModel
         */
        //$model_class = $this->getObjectClass();
        $query = static::getDBManager()->where($where)->select($mask ?? '*')->setLimit($length, ($page-1) * $length);
        if($sort_by !== null){
            $query->setOrderBy($sort_by);
            if($order_by === 'desc')
                $query->setOrderDESC();
        }
        $data = $query->fetchAll();
        $this->success($data, meta: array(
            'current_page' => $page,
            'next_page' => $page+1,
            'previous_page' => max($page - 1, 1),
            'filtered' => static::getDBManager()->where($where)->countRows(),
            'total' => static::getDBManager()->countRows()
        ));
    }
}
