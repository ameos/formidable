<?php

interface IFormidable_ds_collection {

		public function &each();

		public function reset();

		public function count();

		public function keys();

		public function isEmpty();

		public function push(&$mMixed);

		public function &first();
}

?>