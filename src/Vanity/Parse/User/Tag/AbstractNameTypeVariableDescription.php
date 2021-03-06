<?php
/**
 * Copyright (c) 2009-2012 [Ryan Parman](http://ryanparman.com)
 * Copyright (c) 2011-2012 [Amazon Web Services, Inc.](http://aws.amazon.com)
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
 * <http://www.opensource.org/licenses/mit-license.php>
 */


namespace Vanity\Parse\User\Tag;

use phpDocumentor\Reflection\DocBlock;
use Vanity\Parse\User\Reflect\AncestryHandler;
use Vanity\Parse\User\Reflect\TagHandler;
use Vanity\Parse\User\Tag\AbstractHandler;
use Vanity\Parse\User\Tag\HandlerInterface;
use Vanity\Parse\Utilities as ParseUtil;

/**
 * The default handler for name:type:variable:description tags.
 */
abstract class AbstractNameTypeVariableDescription extends AbstractHandler
{
	/**
	 * {@inheritdoc}
	 */
	public function process($elongate = false)
	{
		$content = $this->clean($this->tag->getContent());

		$return = array(
			'raw'         => $content,
			'name'        => $this->tag->getName(),
			'type'        => 'void',
			'variable'    => null,
			'description' => null,
		);

		$pattern = '/
			^[\s]*                # Preceding whitespace
			(?:
				([\w\|_\\\\]+)    # Type, if exists
				[\s]+
			)?
			\$([\w\|_\\\\]+)      # Variable name
			[\s]*
			(.*)                  # Description
		/ux';

		if (preg_match($pattern, $content, $m))
		{
			list(, $type, $variable, $description) = $m;

			$return['variable'] = $variable;

			$return = array_merge(
				$return,
				$this->handleType($type, $elongate),
				$this->handleDescription($description, $elongate)
			);
		}

		return $return;
	}

	/**
	 * Handle the type/types for the tag.
	 *
	 * @param  string  $type     The raw type that has been parsed from the tag.
	 * @param  boolean $elongate Whether or not to elongate/resolve classes and aliases.
	 * @return array             An array containing a `type` and `types` key that should be merged back into the
	 *                           parent node.
	 */
	public function handleType($type, $elongate)
	{
		$return = array();

		if ($type && strpos($type, '|'))
		{
			$self = $this;
			$return['type'] = 'mixed';
			$return['types'] = explode('|', $type);
			$return['types'] = array_map(function($type) use ($self, $elongate)
			{
				return $elongate ? AncestryHandler::elongateType($type, $self->ancestry) : $type;
			},
			$return['types']);
		}
		elseif ($type)
		{
			$return['type'] = $elongate ? AncestryHandler::elongateType($type, $this->ancestry) : $type;
		}

		return $return;
	}

	/**
	 * Handle the arguments for the tag.
	 *
	 * @param  string  $arguments The raw arguments that have been parsed from the tag.
	 * @param  boolean $elongate  Whether or not to elongate/resolve classes and aliases.
	 * @return array              An array containing a `type` and `types` key that should be merged back into the
	 *                            parent node.
	 */
	public function handleArguments($arguments, $elongate)
	{
		$return = array();
		$return['arguments'] = array();
		$arguments = trim($arguments);

		if ($arguments)
		{
			foreach (explode(',', $arguments) as $argument)
			{
				preg_match('/^((\w*)[\s]*)?(&)?\$(\w*)([\s]*=[\s]*(.*))?$/', trim($argument), $matches);

				@list(,,$type,$pbr,$name,,$default) = $matches;
				$pieces = array(
					'name' => $name,
					'type' => $type,
					'required' => (!$default),
					'passed_by_reference' => (boolean) $pbr,
				);

				if ($type && strpos($type, '|'))
				{
					$self = $this;
					$pieces['type'] = 'mixed';
					$pieces['types'] = explode('|', $type);
					$pieces['types'] = array_map(function($type) use ($self, $elongate)
					{
						return $elongate ? AncestryHandler::elongateType($type, $self->ancestry) : $type;
					},
					$pieces['types']);
				}
				elseif ($type)
				{
					$pieces['type'] = $elongate ? AncestryHandler::elongateType($type, $this->ancestry) : $type;
				}

				$return['arguments'][] = $pieces;
			}
		}

		return $return;
	}

	/**
	 * Handle the arguments for the tag.
	 *
	 * @todo: Add support for resolving sub-blocks.
	 *
	 * @param  string  $description The raw description that have been parsed from the tag.
	 * @param  boolean $elongate    Whether or not to elongate/resolve classes and aliases.
	 * @return array                An array containing a `type` and `types` key that should be merged back into the
	 *                              parent node.
	 */
	public function handleDescription($description, $elongate)
	{
		$return = array();

		$return['description'] = $description;

		return $return;
	}
}
