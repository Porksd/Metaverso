<?php

namespace VSHM;

defined('ABSPATH') || exit;

/**
 * Class DB
 *
 * Helper methods to manage WP custom tables.
 *
 * @package VSHM
 * @author  VonStroheim
 */
class DB
{
    /**
     * Creates a new table in the database.
     *
     * @param string $table_name The name of the table to create.
     * @param array  $columns    An array of column names and attributes.
     *
     * @return string The name of the table created.
     */
    public static function create_table(string $table_name, array $columns): string
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        // Check if the table already exists.
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '%s'", $table_name)) === $table_name) {
            return $table_name;
        }

        // Prepare the SQL statement.
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE $table_name (id int NOT NULL AUTO_INCREMENT,\r\n";
        foreach ($columns as $name => $attrs) {
            if ($name === 'id') {
                continue;
            }
            $sql .= self::_prepare_column($name, $attrs) . ",\r\n";
        }
        $sql .= self::_prepare_column('created', 'datetime') . ",\r\n";
        $sql .= self::_prepare_column('updated', 'timestamp') . ",\r\n";
        $sql .= "UNIQUE KEY id (id)) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        return $table_name;

    }

    /**
     * @param string $table_name
     *
     * @return bool
     */
    public static function drop_table(string $table_name): bool
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

        return TRUE;
    }

    /**
     * @param string $table_name
     *
     * @return false|int
     */
    public static function truncate_table(string $table_name)
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        return $wpdb->query("TRUNCATE TABLE $table_name");
    }

    /**
     * @param string       $table_name
     * @param string       $other_table_name
     * @param              $where
     * @param              $where_alias
     * @param string|array $what
     * @param bool         $multisite
     * @param string       $output_mode
     *
     * @return array|object|null
     */
    public static function select_where_is_not(string $table_name, string $other_table_name, $where, $where_alias = NULL, $what = '*', bool $multisite = FALSE, string $output_mode = 'ARRAY_A')
    {
        global $wpdb;

        // Sanitize the table names.
        $table_name       = $wpdb->prefix . sanitize_text_field($table_name);
        $other_table_name = $wpdb->prefix . sanitize_text_field($other_table_name);

        $table_name_1 = self::esc_sql_name($table_name);
        $table_name_2 = self::esc_sql_name($other_table_name);
        if (NULL === $where_alias) {
            $where_alias = $where;
        }
        $columns = is_array($what)
            ? rtrim(implode(', ', $what), ', ')
            : $what;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT " . self::esc_sql_name($columns) . " FROM $table_name_1 WHERE %s NOT IN (SELECT %s FROM $table_name_2)",
                [$where, $where_alias]
            ),
            $output_mode
        );
    }

    /**
     * @param string $order_by
     * @param string $order
     * @param int    $items
     * @param int    $page
     *
     * @return string
     */
    public static function pagination(string $order_by = 'created', string $order = 'ASC', int $items = 10, int $page = 1): string
    {
        global $wpdb;

        $offset     = ($page - 1) * $items;
        $orderBySql = sanitize_sql_orderby("{$order_by} {$order}");

        return "ORDER BY {$orderBySql} " . $wpdb->prepare('LIMIT %d OFFSET %d', [$items, $offset]);
    }

    /**
     * @param        $results
     * @param        $get_query
     * @param        $output_mode
     * @param string $operation
     *
     * @return void
     */
    private static function multisiteFetch(&$results, $get_query, $output_mode, string $operation = 'get_results'): void
    {
        if (function_exists('is_multisite')
            && is_multisite()
        ) {
            global $wpdb;

            $to_be_merged[0] = $results;
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
            $original_blog = get_current_blog_id();
            foreach ($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs") as $blog_id) {
                $switched = switch_to_blog($blog_id);
                if ($switched
                    && (int)$blog_id !== $original_blog
                    && is_plugin_active(plugin_basename(vshm()->plugin['FILE']))) {
                    $to_be_merged[] = $wpdb->{$operation}($get_query($wpdb->prefix), $output_mode);
                }
                restore_current_blog();
            }
            $results = array_merge(...$to_be_merged);
        }
    }

    /**
     * @param string       $table1
     * @param string       $table2
     * @param string       $joinColumn1 join condition table 1 column
     * @param string       $joinColumn2 join condition table 2 column
     * @param string       $t2col1      condition table 2 column 1
     * @param string       $t2col2      condition table 2 column 2
     * @param array        $where2      conditions table 2 ['column' => 'value']
     * @param string|array $what
     * @param array        $where1      optional condition table 1 [['column' => 'column', 'value' => 'value',
     *                                  'operator' => 'operator']]
     * @param bool         $multisite   If TRUE, the query is expected to be performed in the whole network
     *                                  if the multisite support setting of the plugin is active.
     * @param string       $output_mode
     *
     * @return array|null|object
     */
    public static function selectMultiTableConditions(
        string $table1,
        string $table2,
        string $joinColumn1,
        string $joinColumn2,
        string $t2col1,
        string $t2col2,
        array  $where2,
               $what = '*',
        array  $where1 = [],
        bool   $multisite = FALSE,
        string $output_mode = 'ARRAY_A'
    )
    {
        if (empty($where2)) {
            return new \WP_Error(400, 'Conditions array must be not empty');
        }
        global $wpdb;

        // Sanitize the table names.
        $table1 = sanitize_text_field($table1);
        $table2 = sanitize_text_field($table2);

        $table_name_1 = self::esc_sql_name($wpdb->prefix . $table1);
        $table_name_2 = self::esc_sql_name($wpdb->prefix . $table2);
        $joinColumn1  = self::esc_sql_name($joinColumn1);
        $joinColumn2  = self::esc_sql_name($joinColumn2);
        $t2col1       = self::esc_sql_name($t2col1);
        $t2col2       = self::esc_sql_name($t2col2);
        $columns      = is_array($what)
            ? self::esc_sql_name(rtrim(implode(', ', $what), ', '))
            : self::esc_sql_name($what);

        $innerQuery          = "SELECT t2.{$joinColumn2} FROM {$table_name_2} t2 GROUP BY t2.{$joinColumn2} HAVING ";
        $innerQueryImmutable = '';
        foreach ($where2 as $key => $value) {
            if (!is_array($value)) {
                $value = [
                    'value'          => $value,
                    'operator_key'   => '=',
                    'operator_value' => '='
                ];
            }
            $key                 = self::esc_sql_name($key);
            $value_s             = esc_sql($value['value']);
            $operator_key        = self::esc_sql_name($value['operator_key']);
            $operator_value      = self::esc_sql_name($value['operator_value']);
            $innerQueryImmutable .= "MAX(CASE WHEN t2.{$t2col1} {$operator_key} '{$key}' THEN t2.{$t2col2} END) {$operator_value} '{$value_s}' AND ";
        }
        $innerQuery = substr($innerQuery . $innerQueryImmutable, 0, -5);
        $query      = "SELECT {$columns} FROM {$table_name_1} t1 INNER JOIN ({$innerQuery}) tx ON tx.{$joinColumn2} = t1.{$joinColumn1}";


        $query_where_part = '';
        if (!empty($where1)) {
            foreach ($where1 as $column => $value) {
                if (!is_array($value)) {
                    $value = [
                        'value'    => $value,
                        'operator' => '='
                    ];
                }
                $column           = self::esc_sql_name($column);
                $value_s          = esc_sql($value['value']);
                $operator         = self::esc_sql_name($value['operator']);
                $query_where_part .= " AND t1.{$column} {$operator} '{$value_s}'";
            }
        }
        $results = $wpdb->get_results($query . $query_where_part, $output_mode);

        if ($multisite) {
            self::multisiteFetch($results, static function ($db_prefix) use ($table1, $table2, $joinColumn1, $joinColumn2, $innerQueryImmutable, $columns, $query_where_part) {
                $table_name_1 = self::esc_sql_name($db_prefix . $table1);
                $table_name_2 = self::esc_sql_name($db_prefix . $table2);
                $innerQuery   = "SELECT t2.{$joinColumn2} FROM {$table_name_2} t2 GROUP BY t2.{$joinColumn2} HAVING ";
                $innerQuery   = substr($innerQuery . $innerQueryImmutable, 0, -4);
                $query        = "SELECT {$columns} FROM {$table_name_1} t1 INNER JOIN ({$innerQuery}) tx ON tx.{$joinColumn2} = t1.{$joinColumn1}";

                return $query . $query_where_part;
            }, $output_mode);
        }

        if ($wpdb->last_error) {
            $code = 500;
            if (preg_match("/^\b(Table)\b[\s\S]*\b(doesn't exist)\b/", $wpdb->last_error)) {
                $code = 404;
            }

            return new \WP_Error($code, $wpdb->last_error);
        }

        return $results;
    }

    /**
     * @param string       $table1
     * @param string       $table2
     * @param string       $joinColumn1 join condition table 1 column
     * @param string       $joinColumn2 join condition table 2 column
     * @param string|array $what
     * @param array        $where1      optional condition table 1 ['column' => 'value']
     * @param array        $where2      optional condition table 2 ['column' => 'value']
     * @param bool         $multisite   If TRUE, the query is expected to be performed in the whole network
     *                                  if the multisite support setting of the plugin is active.
     * @param string       $output_mode
     *
     * @return array|null|object
     */
    public static function selectJoin(
        string $table1,
        string $table2,
        string $joinColumn1,
        string $joinColumn2,
               $what = '*',
        array  $where1 = [],
        array  $where2 = [],
        bool   $multisite = FALSE,
        string $output_mode = 'ARRAY_A'
    )
    {
        global $wpdb;

        // Sanitize the table names.
        $table1 = sanitize_text_field($table1);
        $table2 = sanitize_text_field($table2);

        $table_name_1 = self::esc_sql_name($wpdb->prefix . $table1);
        $table_name_2 = self::esc_sql_name($wpdb->prefix . $table2);
        $joinColumn1  = self::esc_sql_name($joinColumn1);
        $joinColumn2  = self::esc_sql_name($joinColumn2);
        $columns      = is_array($what)
            ? self::esc_sql_name(rtrim(implode(', ', $what), ', '))
            : self::esc_sql_name($what);

        $query            = "SELECT {$columns} FROM {$table_name_1} t1 INNER JOIN {$table_name_2} t2 ON t1.{$joinColumn1} = t2.{$joinColumn2}";
        $query_where_part = '';

        $whereAlready = FALSE;

        if (!empty($where1)) {
            $query_where_part .= self::where($where1, 'AND', 't1');
            $whereAlready     = TRUE;
        }

        if (!empty($where2)) {
            $query_where_part .= ($whereAlready ? ' ' : '') . self::where($where2, 'AND', 't2', $whereAlready ? 'AND' : 'WHERE');
        }

        $results = $wpdb->get_results("{$query} {$query_where_part}", $output_mode);

        if ($multisite) {
            self::multisiteFetch($results, static function ($db_prefix) use ($table1, $table2, $joinColumn1, $joinColumn2, $columns, $query_where_part) {
                $table_name_1 = self::esc_sql_name($db_prefix . $table1);
                $table_name_2 = self::esc_sql_name($db_prefix . $table2);
                $query        = "SELECT {$columns} FROM {$table_name_1} t1 INNER JOIN {$table_name_2} t2 ON t1.{$joinColumn1} = t2.{$joinColumn2}";

                return "{$query} {$query_where_part}";
            }, $output_mode);
        }

        if ($wpdb->last_error) {
            $code = 500;
            if (preg_match("/^\b(Table)\b[\s\S]*\b(doesn't exist)\b/", $wpdb->last_error)) {
                $code = 404;
            }

            return new \WP_Error($code, $wpdb->last_error);
        }

        return $results;
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function esc_sql_name($name): string
    {
        return (string)str_replace("`", "``", $name);
    }

    /**
     * @param array       $conditions
     * @param string      $join_operator
     * @param string|null $tableAlias
     * @param string      $initialPart
     *
     * @return string
     */
    public static function where(
        array  $conditions = [],
        string $join_operator = 'AND',
        string $tableAlias = NULL,
        string $initialPart = 'WHERE'
    ): string
    {
        if (empty($conditions)) {
            return '';
        }
        global $wpdb;

        $query        = '';
        $whereAlready = FALSE;
        $alias        = $tableAlias ? "{$tableAlias}." : '';
        foreach ($conditions as $column => $value) {

            if (!is_array($value)) {
                $value = [
                    'value'    => $value,
                    'operator' => '='
                ];
            }

            if (isset($value['subCondition']) && is_array($value['subCondition'])) {
                $query .= self::where($value['subCondition'], $value['join'], $tableAlias, '( ') . ') ';
            }

            $column = self::esc_sql_name($column);

            $q_value        = !is_array($value['value']) ? esc_sql($value['value']) : $value['value'];
            $q_operator     = self::esc_sql_name($value['operator']);
            $q_initial_part = !$whereAlready ? $initialPart : (' ' . $join_operator);

            $whereAlready = TRUE;

            if (($q_operator === 'IN' || $q_operator === 'NOT IN') && is_array($q_value)) {

                $placeholder = implode(', ', array_fill(0, count($q_value), '%s'));
                $query       .= $wpdb->prepare("{$q_initial_part} {$alias}{$column} {$q_operator} ({$placeholder})", $q_value);
            } else {

                $query .= "{$q_initial_part} {$alias}{$column} {$q_operator} '{$q_value}'";
            }
        }

        return $query;
    }

    /**
     * @param array  $conditions
     * @param string $join_operator
     *
     * @return string
     */
    public static function whereIn(array $conditions = [], string $join_operator = 'AND'): string
    {
        if (empty($conditions)) {
            return '';
        }
        global $wpdb;

        $query        = '';
        $whereAlready = FALSE;
        foreach ($conditions as $column => $values) {

            $how_many     = count($values);
            $placeholders = array_fill(0, $how_many, '%s');
            $format       = implode(', ', $placeholders);

            $head  = $whereAlready ? $join_operator : 'WHERE';
            $query .= $wpdb->prepare(
                " {$head} " . self::esc_sql_name($column) . " IN ($format)",
                ...$values
            );

            if (!$whereAlready) {
                $whereAlready = TRUE;
            }
        }

        return trim($query);
    }

    /**
     * @param string       $table_name
     * @param string|array $what
     * @param string       $where       optional conditions
     * @param string       $pagination
     * @param bool         $multisite   If TRUE, the query is expected to be performed in the whole network
     *                                  if the multisite support setting of the plugin is active.
     * @param string|int   $output_mode (int when using $operation = get_var)
     * @param string       $operation
     *
     * @return array|null|object
     */
    public static function select(
        string $table_name,
               $what = '*',
        string $where = '',
        string $pagination = '',
        bool   $multisite = FALSE,
               $output_mode = 'ARRAY_A',
        string $operation = 'get_results'
    )
    {
        global $wpdb;

        $table_name_c = self::esc_sql_name($wpdb->prefix . $table_name);
        $columns      = is_array($what)
            ? self::esc_sql_name(rtrim(implode(', ', $what), ', '))
            : self::esc_sql_name($what);
        $query        = "SELECT {$columns} FROM {$table_name_c}";

        $results = $wpdb->{$operation}("$query $where $pagination", $output_mode);

        if ($multisite) {
            self::multisiteFetch($results, static function ($db_prefix) use ($table_name, $columns, $where, $pagination) {
                $table_name_c = self::esc_sql_name($db_prefix . $table_name);
                $query        = "SELECT {$columns} FROM {$table_name_c}";

                return "$query $where $pagination";
            }, $output_mode, $operation);
        }

        if ($wpdb->last_error) {
            $code = 500;
            if (preg_match("/^\b(Table)\b[\s\S]*\b(doesn't exist)\b/", $wpdb->last_error)) {
                $code = 404;
            }

            return new \WP_Error($code, $wpdb->last_error);
        }

        return $results;
    }

    /**
     * @param string $table_name
     * @param array  $where
     *
     * @return int
     */
    public static function count(string $table_name, array $where = []): int
    {
        return (int)self::select($table_name, 'COUNT(*)', self::where($where), '', FALSE, 0, 'get_var');
    }

    /**
     * @param string $table_name
     * @param array  $record
     *
     * @return int
     */
    public static function insert(string $table_name, array $record): int
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        $wpdb->insert($table_name, $record);

        return $wpdb->insert_id;
    }

    /**
     * @param string $table_name
     * @param array  $records
     * @param array  $columns
     *
     * @return int
     */
    public static function insertMany(string $table_name, array $records, array $columns): int
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        $values    = [];
        $columns_s = self::esc_sql_name(rtrim(implode(', ', $columns), ', '));
        $query     = "INSERT INTO {$table_name} ({$columns_s}) VALUES ";

        foreach ($records as $record) {

            $how_many     = count($record);
            $placeholders = array_fill(0, $how_many, '%s');
            $format       = implode(', ', $placeholders);

            $values[] = $wpdb->prepare("({$format})", $record);
        }

        $query .= implode(",\n", $values);

        $wpdb->query($query);

        return $wpdb->insert_id;
    }

    /**
     * @param string $table_name
     * @param array  $record
     * @param array  $keys
     *
     * @return false|int
     */
    public static function update(string $table_name, array $record, array $keys)
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        return $wpdb->update($table_name, $record, $keys);
    }

    /**
     * @param string $table_name
     * @param array  $conditions
     *
     * @return false|int
     */
    public static function delete(string $table_name, array $conditions)
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        if (empty($conditions)) {
            return $wpdb->get_results(
                "DELETE FROM " . esc_sql($table_name));
        }

        return $wpdb->delete($table_name, $conditions);
    }

    /**
     * @param string $table_name
     * @param string $column
     * @param string $like_string
     *
     * @return mixed
     */
    public static function deleteWhereLike(string $table_name, string $column, string $like_string)
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        $column = self::esc_sql_name($column);
        $like   = esc_sql($like_string);

        return $wpdb->query(
            "DELETE FROM " . esc_sql($table_name) . " WHERE {$column} LIKE '%{$like}%'");
    }

    /**
     * @param string $table_name
     * @param array  $conditions
     *
     * @return mixed
     */
    public static function deleteWhere(string $table_name, array $conditions)
    {
        global $wpdb;

        // Sanitize the table name.
        $table_name = $wpdb->prefix . sanitize_text_field($table_name);

        $where = self::where($conditions);

        return $wpdb->query(
            "DELETE FROM {$table_name} {$where}");
    }

    /**
     * @param string       $name
     * @param array|string $attrs
     *
     * @return string
     */
    protected static function _prepare_column(string $name, $attrs): string
    {
        if (!is_array($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_merge(
            [
                'type'    => NULL,
                'null'    => FALSE,
                'chars'   => 255,
                'primary' => FALSE,
                'unique'  => FALSE
            ],
            $attrs
        );
        $type  = strtolower($attrs['type']);
        switch ($type) {
            case 'text':
            case 'tinytext':
            case 'int':
            case 'longtext':
            case 'tinyint':
                return $name . ' ' . $type
                    . ($attrs['null'] ? '' : ' NOT NULL')
                    . ($attrs['primary'] ? ' PRIMARY KEY' : '')
                    . ($attrs['unique'] ? ' UNIQUE' : '');
            case 'decimal':
                return $name . ' decimal(20,2)' . ($attrs['null'] ? '' : ' NOT NULL');
            case 'datetime':
                return $name . " datetime DEFAULT CURRENT_TIMESTAMP"
                    . ($attrs['null'] ? '' : ' NOT NULL');
            case 'timestamp':
                return $name . ' timestamp' . ($attrs['null'] ? '' : ' NOT NULL')
                    . ' DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            case 'char':
            case 'varchar':
                return $name . ' '
                    . $type . '(' . $attrs['chars'] . ')'
                    . ($attrs['null'] ? '' : ' NOT NULL')
                    . ($attrs['primary'] ? ' PRIMARY KEY' : '')
                    . ($attrs['unique'] ? ' UNIQUE' : '');
            default:
                return '';
        }
    }
}