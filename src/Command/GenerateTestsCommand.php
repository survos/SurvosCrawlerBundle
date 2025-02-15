<?php

namespace Survos\CrawlerBundle\Command;

use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;

#[AsCommand('survos:crawl:make-tests', 'Generate crawler tests for a visitor and authenticated users')]
final class GenerateTestsCommand extends Command
{

    public function __construct(
        private readonly RouterInterface                          $router,
        #[Autowire('%kernel.project_dir%/tests/')] private string $testRoot,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('testDir', InputArgument::OPTIONAL, 'Test class dir', 'Crawl')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ns = $input->getArgument('testDir');
        $testDir = $this->testRoot . $ns . '/';
        if (!file_exists($testDir)) {
            mkdir($testDir, 0777, true);
        }
        if (!class_exists(PhpNamespace::class)) {
            $output->writeln("Missing dependency:\n\ncomposer req nette/php-generator");
            return self::FAILURE;
        }
        $fn = $testDir . '/../crawldata.json';
        assert(file_exists($fn), $fn);
        $routes = json_decode(file_get_contents($fn), true);
        foreach ($routes as $key => $links) {
            [$user, $userClass, $startUrl] = explode('|', $key);
            $namespace = new PhpNamespace('App\\Tests\\' . $ns);
            foreach ([
                         WebTestCase::class,
                         TestDox::class,
                         TestWith::class,
                         KernelBrowser::class,
                         BaseVisitLinksTest::class
                     ] as $useClass) {
                $namespace->addUse($useClass);
            }

// create new classes in the namespace
            $className = sprintf('CrawlAs%sTest',  ucfirst($user ? u($user)->before('@')->toString() : 'Visitor'));
            $className = str_replace('.', '', $className);
            $class = $namespace->addClass($className);
            $class->setExtends(BaseVisitLinksTest::class);
            $namespace->add($class);

            $method = $class->addMethod('testRoute');
            $method->setReturnType('void');
            $method->addAttribute(TestDox::class, [
                '/$method $url ($route)'
            ]);
            // get the routes
            foreach ($links as $link) {
                //        #[TestWith(['GET', '/app', 'app_app'])]
                $method->addAttribute(TestWith::class,
                    [
                        [
                            $user,
                            $userClass,
                            $link['path'],
//                            $link['route'],
                            $link['statusCode']??200
                        ]
                    ]);
            }

            array_map(fn($param) => $method->addParameter($param)->setType('string'), [
                'username', 'userClassName', 'url'
            ]);
            $method->addParameter('expected')->setType('string|int|null');
//        public function testRoute(string $method, string $url, string $route): void
            $method->setBody(<<<'END'
        parent::testWithLogin($username, $userClassName, $url, (int)$expected);

END
            );

            $filename = $testDir . $className . '.php';
            file_put_contents($filename, "<?php\n\n" . $namespace);
            $output->writeln(sprintf('<info>%s</info> written.', $filename));
//            dd($filename, file_get_contents($filename));
        }
        return self::SUCCESS;

    }


}
