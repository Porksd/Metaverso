<?php
/**
 * @license MIT
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\DeepCopy\Filter;

/**
 * Defines a decorator filter that will not stop the chain of filters.
 */
class ChainableFilter implements Filter
{
    /**
     * @var Filter
     */
    protected $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function apply($object, $property, $objectCopier)
    {
        $this->filter->apply($object, $property, $objectCopier);
    }
}
