<?php

	namespace hiweb\core\arrays;


	use hiweb\core\hidden_methods;


	abstract class Options{

		use hidden_methods;

		/** @var Arrays */
		private $options_ArrayObject;
		/** @var Options|null */
		private $parent_OptionsObject;


		public function __construct( $parent_OptionsObject = null ){
			$this->options_ArrayObject = Arrays::make( [] );
			///Set Parent Options Object
			if( $parent_OptionsObject instanceof Options ){
				$this->parent_OptionsObject = $parent_OptionsObject;
			}
		}


		/**
		 * @return Options|null
		 */
		protected function getParent_OptionsObject(){
			if( $this->parent_OptionsObject instanceof Options ){
				return $this->parent_OptionsObject;
			}
			return null;
		}


		/**
		 * Return root Options Object
		 * @return Options
		 */
		protected function getRoot_OptionsObject(){
			if( $this->parent_OptionsObject instanceof Options ){
				return $this->parent_OptionsObject->getRoot_OptionsObject();
			} else return $this;
		}


		protected function set( $option_key, $value ){
			$this->options_ArrayObject->set_value( $option_key, $value );
			return $this;
		}


		/**
		 * @param null $option_key
		 * @param null $default
		 * @return array|mixed|null
		 */
		protected function get( $option_key = null, $default = null ){
			return $this->options_ArrayObject->_( $option_key, $default );
		}


		/**
		 * Remove option by key
		 * @aliace \hiweb\core\arrays\Options::unset
		 * @param $option_key
		 * @return arrays
		 */
		protected function remove( $option_key ){
			return $this->options_ArrayObject->unset_key( $option_key );
		}


		/**
		 * Unset option by key to NULL
		 * @param $option_key
		 */
		protected function unset( $option_key ){
			$this->set( $option_key, null );
		}


		/**
		 * @return arrays
		 */
		protected function Arrays(){
			return $this->options_ArrayObject;
		}


		/**
		 * @param      $option_key
		 * @param null $value
		 * @param null $default
		 * @return $this|array|mixed|null
		 */
		public function _( $option_key, $value = null, $default = null ){
			if( is_null( $value ) ){
				return $this->options_ArrayObject->_( $option_key, $default );
			} else {
				return $this->set( $option_key, $value );
			}
		}


		/**
		 * @param $option_key
		 * @return bool
		 */
		public function _is_exists( $option_key ){
			return $this->Arrays()->is_key_exists( $option_key );
		}


		/**
		 * Collect options and sub-options to array
		 * @return array
		 */
		public function _get_optionsCollect(){
			$R = [];
			foreach( $this->Arrays()->get() as $key => $value ){
				if($value instanceof Options_Once){
					$R[$key] = $value->get();
				}
				elseif( $value instanceof Options ){
					$R = array_merge( $R, [ $key => $value->_get_optionsCollect() ] );
				} else {
					$R[ $key ] = $value;
				}
			}
			return $R;
		}

	}