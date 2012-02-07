<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Validator;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Generates the PHP5 table map class for user object model (OM).
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class TableMapBuilder extends AbstractOMBuilder
{
    /**
     * Gets the package for the map builder classes.
     * @return     string
     */
    public function getPackage()
    {
        return parent::getPackage() . '.Map';
    }

    public function getNamespace()
    {
        if ($namespace = parent::getNamespace()) {
            if ($this->getGeneratorConfig() && $omns = $this->getGeneratorConfig()->getBuildProperty('namespaceMap')) {
                return $namespace . '\\' . $omns;
            } else {
                return $namespace;
            }
        }
    }

    /**
     * Returns the name of the current class being built.
     * @return     string
     */
    public function getUnprefixedClassname()
    {
        return $this->getTable()->getPhpName() . 'TableMap';
    }

    /**
     * Adds class phpdoc comment and openning of class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $script .= "

/**
 * This class defines the structure of the '".$table->getName()."' table.
 *
 *";
        if ($this->getBuildProperty('addTimeStamp')) {
            $now = strftime('%c');
            $script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
 *
 * $now
 *";
        }
        $script .= "
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class ".$this->getClassname()." extends \Propel\Runtime\Map\TableMap
{
";
    }

    /**
     * Specifies the methods that are added as part of the map builder class.
     * This can be overridden by subclasses that wish to add more methods.
     * @see        ObjectBuilder::addClassBody()
     */
    protected function addClassBody(&$script)
    {
        $this->declareClasses(
            '\Propel\Runtime\Map\TableMap',
            '\Propel\Runtime\Map\RelationMap'
        );
        $this->addConstants($script);
        $this->addAttributes($script);
        $this->addInitialize($script);
        $this->addBuildRelations($script);
        $this->addGetBehaviors($script);
    }

    /**
     * Adds any constants needed for this TableMap class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addConstants(&$script)
    {
        $script .= "
    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = '".$this->getClasspath()."';
";
    }

    /**
     * Adds any attributes needed for this TableMap class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addAttributes(&$script)
    {
    }

    /**
     * Closes class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addClassClose(&$script)
    {
        $script .= "
} // " . $this->getClassname() . "
";
        $this->applyBehaviorModifier('tableMapFilter', $script, "");
    }

    /**
     * Adds the addInitialize() method to the  table map class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addInitialize(&$script)
    {

        $table = $this->getTable();
        $platform = $this->getPlatform();

        $script .= "
    /**
     * Initialize the table attributes, columns and validators
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return     void
     * @throws     PropelException
     */
    public function initialize()
    {
        // attributes
        \$this->setName('".$table->getName()."');
        \$this->setPhpName('".$table->getPhpName()."');
        \$this->setClassname('" . addslashes($this->getStubObjectBuilder()->getFullyQualifiedClassname()) . "');
        \$this->setPackage('" . parent::getPackage() . "');";
        if ($table->getIdMethod() == "native") {
            $script .= "
        \$this->setUseIdGenerator(true);";
        } else {
            $script .= "
        \$this->setUseIdGenerator(false);";
        }

        if ($table->getIdMethodParameters()) {
            $params = $table->getIdMethodParameters();
            $imp = $params[0];
            $script .= "
        \$this->setPrimaryKeyMethodInfo('".$imp->getValue()."');";
        } elseif ($table->getIdMethod() == IdMethod::NATIVE && ($platform->getNativeIdMethod() == PlatformInterface::SEQUENCE || $platform->getNativeIdMethod() == PlatformInterface::SERIAL)) {
            $script .= "
        \$this->setPrimaryKeyMethodInfo('".$platform->getSequenceName($table)."');";
        }

        if ($this->getTable()->getChildrenColumn()) {
            $script .= "
        \$this->setSingleTableInheritance(true);";
        }

        if ($this->getTable()->getIsCrossRef()) {
            $script .= "
        \$this->setIsCrossRef(true);";
        }

        // Add columns to map
            $script .= "
        // columns";
        foreach ($table->getColumns() as $col) {
            $cup=strtoupper($col->getName());
            $cfc=$col->getPhpName();
            if (!$col->getSize()) {
                $size = "null";
            } else {
                $size = $col->getSize();
            }
            $default = $col->getDefaultValueString();
            if ($col->isPrimaryKey()) {
                if ($col->isForeignKey()) {
                    foreach ($col->getForeignKeys() as $fk) {
                        $script .= "
        \$this->addForeignPrimaryKey('$cup', '$cfc', '".$col->getType()."' , '".$fk->getForeignTableName()."', '".strtoupper($fk->getMappedForeignColumn($col->getName()))."', ".($col->isNotNull() ? 'true' : 'false').", ".$size.", $default);";
                    }
                } else {
                    $script .= "
        \$this->addPrimaryKey('$cup', '$cfc', '".$col->getType()."', ".var_export($col->isNotNull(), true).", ".$size.", $default);";
                }
            } else {
                if ($col->isForeignKey()) {
                    foreach ($col->getForeignKeys() as $fk) {
                        $script .= "
        \$this->addForeignKey('$cup', '$cfc', '".$col->getType()."', '".$fk->getForeignTableName()."', '".strtoupper($fk->getMappedForeignColumn($col->getName()))."', ".($col->isNotNull() ? 'true' : 'false').", ".$size.", $default);";
                    }
                } else {
                    $script .= "
        \$this->addColumn('$cup', '$cfc', '".$col->getType()."', ".var_export($col->isNotNull(), true).", ".$size.", $default);";
                }
            } // if col-is prim key
            if ($col->isEnumType()) {
                $script .= "
        \$this->getColumn('$cup', false)->setValueSet(" . var_export($col->getValueSet(), true). ");";
            }
            if ($col->isPrimaryString()) {
                $script .= "
        \$this->getColumn('$cup', false)->setPrimaryString(true);";
            }
        } // foreach

        // validators
        $script .= "
        // validators";
        foreach ($table->getValidators() as $val) {
            $col = $val->getColumn();
            $cup = strtoupper($col->getName());
            foreach ($val->getRules() as $rule) {
                if ($val->getTranslate() !== Validator::TRANSLATE_NONE) {
                    $script .= "
        \$this->addValidator('$cup', '".$rule->getName()."', '".$rule->getClass()."', '".str_replace("'", "\'", $rule->getValue())."', ".$val->getTranslate()."('".str_replace("'", "\'", $rule->getMessage())."'));";
                } else {
                    $script .= "
        \$this->addValidator('$cup', '".$rule->getName()."', '".$rule->getClass()."', '".str_replace("'", "\'", $rule->getValue())."', '".str_replace("'", "\'", $rule->getMessage())."');";
                } // if ($rule->getTranslation() ...
                } // foreach rule
        }  // foreach validator

        $script .= "
    } // initialize()
";

    }

    /**
     * Adds the method that build the RelationMap objects
     * @param      string &$script The script will be modified in this method.
     */
    protected function addBuildRelations(&$script)
    {
        $script .= "
    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {";
        foreach ($this->getTable()->getForeignKeys() as $fkey) {
            $columnMapping = 'array(';
            foreach ($fkey->getLocalForeignMapping() as $key => $value) {
                $columnMapping .= "'$key' => '$value', ";
            }
            $columnMapping .= ')';
            $onDelete = $fkey->hasOnDelete() ? "'" . $fkey->getOnDelete() . "'" : 'null';
            $onUpdate = $fkey->hasOnUpdate() ? "'" . $fkey->getOnUpdate() . "'" : 'null';
            $script .= "
        \$this->addRelation('" . $this->getFKPhpNameAffix($fkey) . "', '" . addslashes($this->getNewStubObjectBuilder($fkey->getForeignTable())->getFullyQualifiedClassname()) . "', RelationMap::MANY_TO_ONE, $columnMapping, $onDelete, $onUpdate);";
        }
        foreach ($this->getTable()->getReferrers() as $fkey) {
            $relationName = $this->getRefFKPhpNameAffix($fkey);
            $columnMapping = 'array(';
            foreach ($fkey->getForeignLocalMapping() as $key => $value) {
                $columnMapping .= "'$key' => '$value', ";
            }
            $columnMapping .= ')';
            $onDelete = $fkey->hasOnDelete() ? "'" . $fkey->getOnDelete() . "'" : 'null';
            $onUpdate = $fkey->hasOnUpdate() ? "'" . $fkey->getOnUpdate() . "'" : 'null';
            $script .= "
        \$this->addRelation('$relationName', '" . addslashes($this->getNewStubObjectBuilder($fkey->getTable())->getFullyQualifiedClassname()) . "', RelationMap::ONE_TO_" . ($fkey->isLocalPrimaryKey() ? "ONE" : "MANY") .", $columnMapping, $onDelete, $onUpdate";
            if ($fkey->isLocalPrimaryKey()) {
                 $script .= ");";
            } else {
                $script .= ", '" . $this->getRefFKPhpNameAffix($fkey, true) . "');";
            }
        }
        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list(, $crossFK) = $fkList;
            $relationName = $this->getFKPhpNameAffix($crossFK);
            $pluralName = "'" . $this->getFKPhpNameAffix($crossFK, true) . "'";
            $onDelete = $fkey->hasOnDelete() ? "'" . $fkey->getOnDelete() . "'" : 'null';
            $onUpdate = $fkey->hasOnUpdate() ? "'" . $fkey->getOnUpdate() . "'" : 'null';
            $script .= "
        \$this->addRelation('$relationName', '" . addslashes($this->getNewStubObjectBuilder($crossFK->getForeignTable())->getFullyQualifiedClassname()) . "', RelationMap::MANY_TO_MANY, array(), $onDelete, $onUpdate, $pluralName);";
        }
        $script .= "
    } // buildRelations()
";
    }

    /**
     * Adds the behaviors getter
     * @param      string &$script The script will be modified in this method.
     */
    protected function addGetBehaviors(&$script)
    {
        if ($behaviors = $this->getTable()->getBehaviors()) {
            $script .= "
    /**
     *
     * Gets the list of behaviors registered for this table
     *
     * @return array Associative array (name => parameters) of behaviors
     */
    public function getBehaviors()
    {
        return array(";
            foreach ($behaviors as $behavior) {
                $script .= "
            '{$behavior->getName()}' => array(";
                foreach ($behavior->getParameters() as $key => $value) {
                    $script .= "'$key' => '$value', ";
                }
                $script .= "),";
            }
            $script .= "
        );
    } // getBehaviors()
";
        }
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @return boolean
     */
    public function hasBehaviorModifier($hookName, $modifier = null)
    {
        return parent::hasBehaviorModifier($hookName, 'TableMapBuilderModifier');
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string &$script The script will be modified in this method.
     */
    public function applyBehaviorModifier($hookName, &$script, $tab = "        ")
    {
        return $this->applyBehaviorModifierBase($hookName, 'TableMapBuilderModifier', $script, $tab);
    }
}