<?php
/**
 * @license MIT
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\DeepCopy\TypeFilter;

/**
 * @final
 */
class ReplaceFilter implements TypeFilter
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callable Will be called to get the new value for each element to replace
     */
    public function __construct(callable $callable)
    {
        $this->callback = $callable;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($element)
    {
        return call_user_func($this->callback, $element);
    }
}
