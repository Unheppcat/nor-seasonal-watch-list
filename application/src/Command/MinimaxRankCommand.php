<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Command;

use App\Repository\ShowRepository;
use App\Service\MinimaxRankHelper;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'vote:minimaxrank',
    description: 'Calculate minimax ranking.'
)]
class MinimaxRankCommand extends Command
{

    private MinimaxRankHelper $helper;
    private ShowRepository $showRepository;

    /**
     * MinmaxRankCommand constructor.
     *
     */
    public function __construct(
        MinimaxRankHelper $helper,
        ShowRepository $showRepository
    ) {
        parent::__construct();
        $this->helper = $helper;
        $this->showRepository = $showRepository;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'ballot_file',
                InputArgument::REQUIRED,
                'Path to CSV file of ballots'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try
        {
            $fp = fopen($input->getArgument('ballot_file'), 'rb');
            $headerLine = fgetcsv($fp, 1000);
            $this->helper->setTitles($headerLine);
            $this->helper->setAnilistIds($this->getAnilistIdsFromTitles($headerLine));

            while ($ballot = fgetcsv($fp, 1000)) {
                $this->helper->addBallot($ballot);
            }

            fclose($fp);

            $rankedResults = $this->helper->getRanks();

            foreach($rankedResults as $result) {
                $io->writeln($result);
            }
            return 0;
        } catch (Exception $e) {
            $io->error('An error occurred while ranking the shows.');
            $io->error($e->getMessage());
            return 1;
        }
    }

    private function getAnilistIdsFromTitles(false|array $headerLine): array
    {
        if ($headerLine === false) {
            return [];
        }
        $ids = [];
        foreach ($headerLine as $title) {
            try {
                $show = $this->showRepository->findOneBy(['englishTitle' => $title]);
                if ($show === null) {
                    $ids[] = '(unknown)';
                } else {
                    $ids[] = $show->getAnilistId();
                }
            } catch (Exception) {
                $ids[] = '(unknown)';
            }
        }
        return $ids;
    }
}
