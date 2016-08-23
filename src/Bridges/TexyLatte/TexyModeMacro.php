<?php
/**
 * This file is part of the nepada/texy-nette.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace Nepada\Bridges\TexyLatte;

use Latte;
use Latte\MacroNode;
use Nepada;
use Nepada\Texy\TexyMultiplier;
use Nette;
use Tracy\Debugger;


/**
 * Macro {texyMode ...}
 */
class TexyModeMacro extends Nette\Object implements Latte\IMacro
{

    /** @var bool */
    private $isUsed;


    /**
     * @param Latte\Compiler $compiler
     */
    public static function install(Latte\Compiler $compiler)
    {
        $me = new static;
        $compiler->addMacro('texyMode', $me);
    }

    /**
     * Initializes before template parsing.
     */
    public function initialize()
    {
        $this->isUsed = false;
    }

    /**
     * Finishes template parsing.
     *
     * @return string[] [prolog, epilog]
     */
    public function finalize()
    {
        if ($this->isUsed) {
            return array(static::class . '::validateTemplate($template);', '');
        }
    }

    /**
     * New node is found. Returns FALSE to reject.
     *
     * @param MacroNode $node
     * @return bool
     * @throws Latte\CompileException
     */
    public function nodeOpened(MacroNode $node)
    {
        if ($node->modifiers) {
            throw new Latte\CompileException("Modifiers are not allowed in {{$node->name}}.");
        }

        if ($node->tokenizer->fetchWord() === false) {
            throw new Latte\CompileException("Missing mode name in {{$node->name}}.");
        } elseif ($node->tokenizer->fetchWord()) {
            throw new Latte\CompileException("Multiple arguments are not supported in {{$node->name}}.");
        }

        $this->isUsed = true;
        $node->isEmpty = false;
        $node->tokenizer->reset();
        $node->openingCode = Latte\PhpWriter::using($node)
            ->write('<?php $_texyModeStack[] = $_texy->getMode(); $_texy->setMode(%node.word); ?>');
    }

    /**
     * Node is closed.
     *
     * @param MacroNode $node
     */
    public function nodeClosed(MacroNode $node)
    {
        $node->closingCode = '<?php $_texy->setMode(array_pop($_texyModeStack)); ?>';
    }

    /**
     * @param Latte\Template $template
     * @throws Nepada\Texy\InvalidStateException
     */
    public static function validateTemplate(Latte\Template $template)
    {
        if (!isset($template->_texy) || !$template->_texy instanceof TexyMultiplier) {
            $where = isset($template->control) && $template->control instanceof Nette\ComponentModel\IComponent
                ? ' in component ' . get_class($template->control) . '(' . $template->control->getName() . ')'
                : null;

            throw new Nepada\Texy\InvalidStateException("TexyMultiplier instance not found{$where}.");
        }
    }

}
