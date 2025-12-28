<?php

namespace App\Command;

use App\Service\MergeUsersHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:merge-users',
    description: 'Merge duplicate user accounts'
)]
class MergeUsersCommand extends Command
{
    private MergeUsersHelper $helper;

    public function __construct(
        MergeUsersHelper $helper
    ) {
        parent::__construct();
        $this->helper = $helper;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('primary_account_id', InputArgument::REQUIRED, 'Internal ID of the account to keep')
            ->addOption('delete_old_records', 'd', InputOption::VALUE_OPTIONAL,'Whether to delete old records after merge')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $primaryAccountId = $input->getArgument('primary_account_id');
        $deleteOldOption = $input->getOption('delete_old_records');
        $deleteOld = (bool)($deleteOldOption ?? false);

        $this->helper->merge($primaryAccountId, $deleteOld, $io);

        $io->success('Successfully merged accounts');

        return Command::SUCCESS;
    }
}
