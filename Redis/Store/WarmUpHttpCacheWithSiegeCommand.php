<?php

declare(strict_types=1);

namespace SwagEssentials\Redis\Store;

use Doctrine\DBAL\Connection;
use PDO;
use Shopware\Components\HttpCache\UrlProvider\UrlProviderInterface;
use Shopware\Components\HttpCache\UrlProviderFactoryInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Routing\Context;
use Shopware\Models\Shop\DetachedShop;
use Shopware\Models\Shop\Repository as ShopRepository;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class WarmUpHttpCacheWithSiegeCommand extends Command
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var UrlProviderFactoryInterface
     */
    private $urlProviderFactory;

    /**
     * @var ShopRepository
     */
    private $shopRepository;

    /**
     * @var Shopware_Components_Config
     */
    private $config;

    public function __construct(
        Connection $connection,
        UrlProviderFactoryInterface $urlProviderFactory,
        ModelManager $modelManager,
        Shopware_Components_Config $config
    ) {
        parent::__construct();
        $this->shopRepository = $modelManager->getRepository(Shop::class);
        $this->connection = $connection;
        $this->urlProviderFactory = $urlProviderFactory;
        $this->config = $config;
    }

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->input = $input;

        $shopIds = $this->getShopsFromInput();

        foreach ($shopIds as $shopId) {
            $this->warmShopUrls((int) $shopId);
        }
        $output->writeln("\n The HttpCache is now warmed up");
    }

    protected function warmShopUrls(int $shopId): void
    {
        $shop = $this->shopRepository->getById($shopId);

        if (!$shop instanceof DetachedShop) {
            throw new \Exception(sprintf('Shop with ID "%s" was not found', $shopId));
        }

        $context = Context::createFromShop(
            $shop,
            $this->config
        );

        $output = $this->output;
        $concurrency = $this->input->getOption('concurrency') ?: 7;

        if ($this->input->getOption('urls')) {
            $file = $this->input->getOption('urls');
            $totalUrlCount = count(file($file));
            $fileName = $file;
        } else {
            $totalUrlCount = $this->getTotalUrlCount($context);
            $fileName = $this->getFileNameWithUrls($context);
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
     */
    protected function getWidth(int $totalUrlCount): int
    {
        $terminal = new Terminal();
        $width = $terminal->getWidth();

        $maxWidth = $width - (strlen((string) $totalUrlCount) * 2 + 10);

        return min(200, $maxWidth);
    }

    protected function getShopsFromInput(): array
    {
        $shopId = $this->input->getArgument('shopId');
        if (!empty($shopId)) {
            return [$shopId];
        }

        return $this->connection->executeQuery('SELECT id FROM s_core_shops WHERE active = 1')->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function runSiege(string $concurrency, string $fileName, ProgressBar $progressBar): void
    {
        $cmd = "siege -b -v --no-follow -c {$concurrency} -f {$fileName} -r 1 ";
        $this->output->writeln("Running: $cmd");

        $progressBar->start();
        $fp = popen($cmd . ' 2>&1 ', 'r');

        while (!feof($fp)) {
            $read = fread($fp, 11024);
            $lines = explode("\n", $read);
            $progressBar->advance(count($lines));
        }
        $progressBar->finish();
    }

    protected function checkForSiege(): void
    {
        $output = $this->output;

        $output->writeln('Checking for siege');
        if (!shell_exec('siege')) {
            throw new \RuntimeException("Could not run command. Make sure, that 'siege' is installed");
        }
        $output->writeln("Done\n");
    }

    private function getFileNameWithUrls(Context $context): string
    {
        $this->output->writeln("\n Exporting URLs for shop with id " . $context->getShopId());
        $fileName = tempnam(sys_get_temp_dir(), 'urls');
        $fh = fopen($fileName, 'wb');

        /** @var UrlProviderInterface[] $providers */
        $providers = $this->urlProviderFactory->getAllProviders();
        foreach ($providers as $provider) {
            $urls = $provider->getUrls($context);
            fwrite($fh, implode("\n", $urls));
        }
        fclose($fh);

        return $fileName;
    }

    private function getTotalUrlCount(Context $context): int
    {
        $count = 0;

        /** @var UrlProviderInterface[] $providers */
        $providers = $this->urlProviderFactory->getAllProviders();
        foreach ($providers as $provider) {
            $count += $provider->getCount($context);
        }

        return $count;
    }
}
