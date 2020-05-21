<?php
	
	namespace hiweb\components\Fields\Types\Checkbox;
	
	
	use hiweb\components\Fields\Field;
	use hiweb\components\Includes\IncludesFactory;
	
	
	class Field_Checkbox extends Field{
		
		protected $options_class = '\hiweb\components\Fields\Types\Checkbox\Field_Checkbox_Options';
		
		
		public function get_css(){
			return __DIR__ . '/Field_Checkbox.css';
		}
		
		
		public function get_admin_html( $value = null, $name = null ){
			ob_start();
			include __DIR__ . '/template.php';
			return ob_get_clean();
		}
		
		
		/**
		 * @param null $value
		 * @param bool $update_meta_process
		 * @return bool
		 */
		public function get_sanitize_admin_value( $value, $update_meta_process = false ){
			return (bool)$value;
		}
		
		
		/**
		 * @return Field_Checkbox_Options
		 */
		public function options(){
			return parent::options(); // TODO: Change the autogenerated stub
		}
		
	}