<?php declare(strict_types=1);

namespace SwagEssentials\Redis\Store;

use Shopware\Components\HttpCache\CacheWarmer;
use Symfony\Component\Console\Command\Command;
use Shopware\Kernel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WarmUpHttpCacheWithSiegeCommand extends Command
{
    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var CacheWarmer
     */
    private $httpCacheWarmer;

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param CacheWarmer $httpCacheWarmer
     */
    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db, CacheWarmer $httpCacheWarmer)
    {
        parent::__construct();
        $this->db = $db;
        $this->httpCacheWarmer = $httpCacheWarmer;
    }

    protected $shops;

    /** @var  Kernel */
    protected $kernel;

    protected $front;

    protected $requestReflection;

    protected $responseReflection;

    /** @var  OutputInterface */
    protected $output;

    /** @var  InputInterface */
    protected $input;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sw:cache:siege')
            ->setDescription('warm up http cache using siege')
            ->addOption('concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Number of parallel workers')
            ->addOption('urls', 'u', InputOption::VALUE_OPTIONAL, 'URL file')
            ->addArgument('shopId', InputArgument::OPTIONAL, 'The Id of the shop')
            ->setHelp('The <info>%command.name%</info> warms up the http cache faster by re-using the kernel');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $shopIds = $this->getShopsFromInput();

        foreach ($shopIds as $shopId) {
            $this->warmShopUrls($shopId);
        }
        $output->writeln("\n The HttpCache is now warmed up");
    }

    /**
     * @param $shopId
     */
    protected function warmShopUrls($shopId)
    {
        $output = $this->output;
        $concurrency = $this->input->getOption('concurrency') ?: 7;

        if ($this->input->getOption('urls')) {
            $file = $this->input->getOption('urls');
            $totalUrlCount = count(file($file));
            $fileName = $file;
        } else {
            $totalUrlCount = $this->httpCacheWarmer->getAllSEOUrlCount($shopId);
            $fileName = $this->exportUrls($shopId, $totalUrlCount);
        }

        $output->writeln("\n Starting warmup from URL file {$fileName} and {$concurrency} workers\n");

        $progressBar = new ProgressBar($output, $totalUrlCount);
        $progressBar->setBarWidth($this->getWidth($totalUrlCount));
        $progressBar->setRedrawFrequency(100);

        $this->checkForSiege();

        $this->runSiege($concurrency, $fileName, $progressBar);
    }

    /**
     * Calculate the size of the progressbar depending on the terminal's size
     *
     * @param $totalUrlCount
     * @return int
     */
    private function getWidth($totalUrlCount): int
    {
        $dimensions = $this->getApplication()->getTerminalDimensions();
        if (!$dimensions) {
            return 100;
        }
        $width = $dimensions[0];
        if ($width === 0) {
            $width = 100;
        }
        $maxWidth = $width - (strlen((string) $totalUrlCount) * 2 + 10);

        return min(200, $maxWidth);
    }

    /**
     * @return array
     */
    protected function getShopsFromInput(): array
    {
        $shopId = $this->input->getArgument('shopId');
        if (!empty($shopId)) {
            return [$shopId];
        }

        return $this->db->fetchCol('SELECT id FROM s_core_shops WHERE active = 1');
    }

    /**
     * @param $concurrency
     * @param $fileName
     * @param ProgressBar $progressBar
     */
    protected function runSiege($concurrency, $fileName, $progressBar)
    {
        $cmd = "siege -b -v -c {$concurrency} -f {$fileName} -r once ";
        $this->output->writeln("Running: $cmd");

        $progressBar->start();
        $fp = popen($cmd . ' 2>&1 ', 'r');


        while (!feof($fp)) {
            $lines = array_filter(
                explode("\n", fread($fp, 11024)),
                function ($line) {
                    return strpos($line, '==>') !== false;
                }
            );
            $progressBar->advance(count($lines));

        }
        $progressBar->finish();
    }

    protected function checkForSiege()
    {
        $output = $this->output;

        $output->writeln('Checking for siege');
        if (!shell_exec('siege')) {
            throw new \RuntimeException("Could not run command. Make sure, that 'siege' is installed");
        }
        $output->writeln("Done\n");
    }

    /**
     * @param $shopId
     * @param $totalUrlCount
     * @return string
     */
    protected function exportUrls($shopId, $totalUrlCount): string
    {
        $output = $this->output;

        $offset = 0;
        $output->writeln("\n Exporting URLs for shop with id " . $shopId);
        $fileName = tempnam(sys_get_temp_dir(), 'urls');
        $fh = fopen($fileName, 'wb');
        while ($offset < $totalUrlCount) {
            $urls = $this->httpCacheWarmer->getAllSEOUrls($shopId, 1000, $offset);
            fwrite($fh, implode("\n", $urls));
            $offset += count($urls);
        }
        fclose($fh);

        return $fileName;
    }
}
