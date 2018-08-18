<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\TexyDI;

use Nepada\Texy;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class TexyExtensionTest extends TestCase
{

    /** @var Nette\DI\Container */
    private $container;

    public function testServices(): void
    {
        Assert::type(Texy\TexyMultiplier::class, $this->container->getService('texy.multiplier'));
        Assert::type(Texy\TexyFactory::class, $this->container->getService('texy.texyFactory'));
    }

    public function testTemplate(): void
    {
        /** @var Nette\Bridges\ApplicationLatte\Template $template */
        $template = $this->container->getByType(Nette\Application\UI\ITemplateFactory::class)->createTemplate();
        $template->setFile(__DIR__ . '/fixtures/test.latte');

        Assert::matchFile(
            __DIR__ . '/fixtures/test.phtml',
            $template->getLatte()->compile($template->getFile())
        );

        Assert::matchFile(
            __DIR__ . '/fixtures/test.html',
            (string) $template
        );
    }

    protected function setUp(): void
    {
        $configurator = new Nette\Configurator();
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->setDebugMode(true);
        $configurator->addConfig(__DIR__ . '/fixtures/config.neon');
        $this->container = $configurator->createContainer();
    }

}


(new TexyExtensionTest())->run();
