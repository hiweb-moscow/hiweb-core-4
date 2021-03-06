<?php

namespace hiweb\components\Fields\Types\Repeat;


use hiweb\core\Strings;


/**
 * Class Field_Repeat_Value
 * @package hiweb\components\Fields\Types\Repeat
 * @version 1.1
 */
class Field_Repeat_Value {

    private $Field_Repeat;
    protected $value_raw = [];
    protected $value_filtered = [];
    /** @var Field_Repeat_Row[] */
    protected $rows;
    protected $Field_Repeat_name;


    public function __construct(Field_Repeat $Field_Repeat, $value_array = [], $field_name = '') {
        $this->Field_Repeat = $Field_Repeat;
        $this->Field_Repeat_name = $field_name;
        if (is_array($value_array)) {
            $this->value_raw = $value_array;
        }
        $value_raw = $this->value_raw;
        $value_raw = get_array(is_array($value_raw) ? $value_raw : [])->rows();
        $value_filtered = [];
        while($value_raw->have()) {
            $value_raw->the();
            $flexRowId = Strings::sanitize_id($value_raw->get_sub_field('_flex_row_id', ''));
            $rowCollapsed = $value_raw->get_sub_field('_flex_row_collapsed', '0');
            if (array_key_exists($flexRowId, $this->Field_Repeat->options()->get_cols())) {
                $value_filtered[$value_raw->get_index()] = [
                    '_flex_row_id' => $flexRowId,
                    '_flex_row_collapsed' => absint($rowCollapsed)
                ];
                foreach ($this->Field_Repeat->options()->get_cols()[$flexRowId] as $colId => $col) {
                    $value_filtered[$value_raw->get_index()][$col->get_id()] = $value_raw->get_sub_field($col->get_id());
                }
            }
        }
        $this->value_filtered = $value_filtered;
    }


    /**
     * @return array
     */
    public function get(): array {
        return $this->value_filtered;
    }


    //		/**
    //		 * @return bool
    //		 */
    //		public function have_cols(){
    //			return count( $this->Field_Repeat->Options()->get_cols() ) > 0;
    //		}

    /**
     * @return bool
     */
    public function have_rows() {
        return (count($this->value_filtered) > 0);
    }


    //		/**
    //		 * @return bool
    //		 */
    //		public function have_flex_rows(){
    //			return !( in_array( '', $this->Field_Repeat->Options()->get_flex_ids() ) && count( $this->Field_Repeat->Options()->get_flex_ids() ) );
    //		}

    /**
     * @return array|Field_Repeat_Row[]
     */
    public function get_rows() {
        if ( !is_array($this->rows)) {
            $this->rows = [];
            $cols_by_flex = $this->Field_Repeat->options()->get_cols();
            foreach ($this->value_filtered as $row_index => $row_raw) {
                $flex_row_id = array_key_exists('_flex_row_id', $row_raw) ? Strings::sanitize_id($row_raw['_flex_row_id']) : '';
                if (array_key_exists($flex_row_id, $cols_by_flex)) {
                    $this->rows[$row_index] = new Field_Repeat_Row($this->Field_Repeat, $row_index, $cols_by_flex[$flex_row_id], $row_raw, $this->Field_Repeat_name);
                }
            }
        }
        return $this->rows;
    }

}