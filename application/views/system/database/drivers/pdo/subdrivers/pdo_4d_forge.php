<?php
/**
 * CodeIgniter.
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2018, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2018, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 *
 * @see	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PDO 4D Forge Class.
 *
 * @category	Database
 *
 * @author		EllisLab Dev Team
 *
 * @see		https://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_4d_forge extends CI_DB_pdo_forge
{
    /**
     * CREATE DATABASE statement.
     *
     * @var string
     */
    protected $_create_database = 'CREATE SCHEMA %s';

    /**
     * DROP DATABASE statement.
     *
     * @var string
     */
    protected $_drop_database = 'DROP SCHEMA %s';

    /**
     * CREATE TABLE IF statement.
     *
     * @var string
     */
    protected $_create_table_if = 'CREATE TABLE IF NOT EXISTS';

    /**
     * RENAME TABLE statement.
     *
     * @var string
     */
    protected $_rename_table = false;

    /**
     * DROP TABLE IF statement.
     *
     * @var string
     */
    protected $_drop_table_if = 'DROP TABLE IF EXISTS';

    /**
     * UNSIGNED support.
     *
     * @var array
     */
    protected $_unsigned = [
        'INT16' => 'INT',
        'SMALLINT' => 'INT',
        'INT' => 'INT64',
        'INT32' => 'INT64',
    ];

    /**
     * DEFAULT value representation in CREATE/ALTER TABLE statements.
     *
     * @var string
     */
    protected $_default = false;

    // --------------------------------------------------------------------

    /**
     * ALTER TABLE.
     *
     * @param string $alter_type ALTER type
     * @param string $table      Table name
     * @param mixed  $field      Column definition
     *
     * @return string|string[]
     */
    protected function _alter_table($alter_type, $table, $field)
    {
        if (in_array($alter_type, ['ADD', 'DROP'], true)) {
            return parent::_alter_table($alter_type, $table, $field);
        }

        // No method of modifying columns is supported
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Process column.
     *
     * @param array $field
     *
     * @return string
     */
    protected function _process_column($field)
    {
        return $this->db->escape_identifiers($field['name'])
            . ' ' . $field['type'] . $field['length']
            . $field['null']
            . $field['unique']
            . $field['auto_increment'];
    }

    // --------------------------------------------------------------------

    /**
     * Field attribute TYPE.
     *
     * Performs a data type mapping between different databases.
     *
     * @param array &$attributes
     *
     * @return void
     */
    protected function _attr_type(&$attributes)
    {
        switch (strtoupper($attributes['TYPE'])) {
            case 'TINYINT':
                $attributes['TYPE'] = 'SMALLINT';
                $attributes['UNSIGNED'] = false;

                return;
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = false;

                return;
            case 'INTEGER':
                $attributes['TYPE'] = 'INT';

                return;
            case 'BIGINT':
                $attributes['TYPE'] = 'INT64';

                return;
            default: return;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Field attribute UNIQUE.
     *
     * @param array &$attributes
     * @param array &$field
     *
     * @return void
     */
    protected function _attr_unique(&$attributes, &$field)
    {
        if (!empty($attributes['UNIQUE']) && true === $attributes['UNIQUE']) {
            $field['unique'] = ' UNIQUE';

            // UNIQUE must be used with NOT NULL
            $field['null'] = ' NOT NULL';
        }
    }

    // --------------------------------------------------------------------

    /**
     * Field attribute AUTO_INCREMENT.
     *
     * @param array &$attributes
     * @param array &$field
     *
     * @return void
     */
    protected function _attr_auto_increment(&$attributes, &$field)
    {
        if (!empty($attributes['AUTO_INCREMENT']) && true === $attributes['AUTO_INCREMENT']) {
            if (false !== stripos($field['type'], 'int')) {
                $field['auto_increment'] = ' AUTO_INCREMENT';
            } elseif (0 === strcasecmp($field['type'], 'UUID')) {
                $field['auto_increment'] = ' AUTO_GENERATE';
            }
        }
    }
}
