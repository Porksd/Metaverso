<?php
/**
 * Provides Entry Model Class
 */
namespace BitCode\BitFormPro\Core\Database;

use BitCode\BitForm\Core\Database\Model;

class LogModel extends Model
{
    // protected static $log_table = 'bitforms_form_entry_log';
    // protected static $log_details = 'bitforms_form_log_details';

    // public static function autoLogDelete(int $formId, int $intervalDate)
    // {
    //     global $wpdb;
    //     if (
    //         !\is_null($intervalDate) && !\is_null($formId)
    //     ) {
    //         $logTable = $wpdb->prefix . static::$log_table;
    //         $logDetails = $wpdb->prefix . static::$log_details;
    //         $condition = "lg.form_id=$formId && DATE_ADD(date(lg.created_at), INTERVAL $intervalDate DAY) < CURRENT_DATE";

    //         $sql = "DELETE lg, lg_details FROM $logTable as lg INNER JOIN $logDetails
    //         as lg_details ON  lg.id = lg_details.log_id WHERE $condition";
    //         $result = $wpdb->get_results($sql, OBJECT_K);

    //         return $result;
    //     }
    // }

    public function logDelete($condition=null)
    {
        if (!\is_null($condition)
            && \is_array($condition)
            && array_keys($condition) !== range(0, count($condition) - 1)
        ) {
            $delete_condition = $condition;
        } else {
            return new WP_Error(
                'deletion_error',
                __('At least 1 condition needed', 'bitform')
            );
        }
        $formatted_conditions = $this->getFormatedCondition($delete_condition);
        if ($formatted_conditions) {
            $condition_to_check = $formatted_conditions['conditions'];
            $all_values = $formatted_conditions['values'];
        } else {
            $condition_to_check = null;
            return new WP_Error(
                'deletion_error',
                __('At least 1 condition needed', 'bitform')
            );
        }
        $prefix = $this->app_db->prefix;

        $sql = "DELETE `{$prefix}bitforms_form_entry_log`,`{$prefix}bitforms_form_log_details` FROM "
        ."`{$prefix}bitforms_form_entry_log` "
        ." INNER JOIN `{$prefix}bitforms_form_log_details` ON "
        ." `{$prefix}bitforms_form_entry_log`.`id` "
        ."= `{$prefix}bitforms_form_log_details`.`log_id` "
        ." $condition_to_check";
        return $this->execute($sql, $all_values)->getResult();
    }
}
