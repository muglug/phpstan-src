<?php

namespace Nette\DI {
	class Container {
		/**
		 * @template T
		 * @template-typeof T $type
		 * Resolves service by type.
		 * @param  string  class or interface
		 * @param  bool    throw exception if service doesn't exist?
		 * @return T|null
		 * @throws MissingServiceException
		 * @psalm-ignore-nullable-return
		 */
		public function getByType($type, $throw = true)
		{
		}
	}
}