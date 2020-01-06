<?php


namespace Xandros15\Tumbler\Console;


use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Xandros15\Tumbler\Sites\SiteInterface;

class Download extends Command
{
    const SITES_MAP = [
        'ehentai' => 'eh',
        'hentai2read' => 'h2r',
        'hentaifoundry' => 'hf',
        'sankaku' => 'sc',
    ];
    protected static $defaultName = 'download';
    /** @var ContainerInterface */
    private $di;

    /**
     * Download constructor.
     *
     * @param ContainerInterface $di
     */
    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('site', InputArgument::REQUIRED, 'Site from what you want to download.');
        $this->addArgument('ident', InputArgument::REQUIRED, 'Album url/Artist id/Tags/Blog name/Album name.');
        $this->addOption('out', 'o', InputArgument::OPTIONAL, 'Output directory.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $site = $input->getArgument('site');
        if (isset(self::SITES_MAP[$site])) {
            $site = self::SITES_MAP[$site];
        }
        $app = $this->di->has($site) ? $this->di->get($site) : null;
        if (!$app instanceof SiteInterface) {
            $output->writeln('No site found.');

            return 0;
        }
        $ident = $input->getArgument('ident');

        $directory = $input->getOption('out');

        $isCwd = strpos($directory, './') === 0;
        $isUwd = strpos($directory, '../') === 0;
        if (!$directory) {
            $directory = getcwd() . DIRECTORY_SEPARATOR . $site;
        } elseif ($isCwd || $isUwd) {
            $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
        }

        $app->download($ident, $directory);

        return 0;
    }
}
