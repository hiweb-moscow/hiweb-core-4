<?php

	namespace hiweb\core\Cache;


	use hiweb\core\hidden_methods;


	class Cache{


		use hidden_methods;

		/** @var Cache_File */
		private $Cache_File;
		/** @var Cache_CallbackValue */
		private $Cache_CallbackValue;

		private $value = null;
		private $variable_name = null;
		private $group_name = null;
		private $is_value_set = false;


		///

		public function __construct( $variable_name = null, $group_name = null ){
			$variable_name = (string)$variable_name;
			$group_name = (string)$group_name;
			if( $variable_name != '' ) $this->variable_name = $variable_name;
			if( $group_name != '' ) $this->group_name = $group_name;
		}


		/**
		 * @return Cache_File
		 */
		public function Cache_File(){
			if( !$this->Cache_File instanceof Cache_File ) $this->Cache_File = new Cache_File( $this );
			return $this->Cache_File;
		}


		/**
		 * @return Cache_CallbackValue
		 */
		public function Cache_CallbackValue(){
			if( !$this->Cache_CallbackValue instanceof Cache_CallbackValue ) $this->Cache_CallbackValue = new Cache_CallbackValue( $this );
			return $this->Cache_CallbackValue;
		}


		/**
		 * @return null
		 */
		public function __invoke(){
			return $this->get();
		}


		/**
		 * @return string
		 */
		public function __toString(){
			return (string)$this->value;
		}


		/**
		 * @return string|null
		 */
		public function get_variable_name(){
			return $this->variable_name;
		}


		/**
		 * @return string|null
		 */
		public function get_group_name(){
			return $this->group_name;
		}


		/**
		 * Get cache value from instant, callback or file if same exists
		 * @return null
		 */
		public function get(){
			if( $this->Cache_File()->is_enable() ){
				if($this->Cache_File()->is_alive() ){
					$this->value = $this->Cache_File()->get();
				} else {
					if( $this->Cache_CallbackValue()->is_callable() && $this->Cache_CallbackValue()->get_count() == 0 ){
						$this->value = $this->Cache_CallbackValue()->get();
					}
					$this->Cache_File()->set( $this->value );
				}
			} else {
				if( $this->Cache_CallbackValue()->is_callable() && $this->Cache_CallbackValue()->get_count() == 0 ){
					$this->value = $this->Cache_CallbackValue()->get();
				}
			}
			return $this->value;
		}


		/**
		 * @param mixed $value
		 * @return Cache
		 */
		public function set( $value ){
			$this->value = $value;
			$this->is_value_set = microtime( true );
			if( $this->Cache_File()->is_enable() ){
				$this->Cache_File()->set( $value );
			}
			return $this;
		}


		/**
		 * @return int
		 */
		public function is_set(){
			return $this->is_value_set !== false;
		}

	}