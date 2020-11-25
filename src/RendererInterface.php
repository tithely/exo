<?php

namespace Exo;

use Exo\Operation\AbstractOperation;

interface RendererInterface
{
    /**
     * @param string $templates
     * @param array $replacements
     * @return AbstractOperation
     */
    public function render(string $templates, array $replacements): AbstractOperation;
}
