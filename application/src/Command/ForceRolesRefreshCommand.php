<?php /** @noinspection PhpUnused */

namespace App\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:force-roles-refresh',
    description: 'Reset rolesLastRefreshed field to null for all users'
)]
class ForceRolesRefreshCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Forcing a roles refresh for all users...');

        $connection = $this->entityManager->getConnection();
        try {
            /** @noinspection SqlWithoutWhere */
            $rowsAffected = $connection->executeStatement(
                'UPDATE user SET roles_last_refreshed = NULL'
            );
            $io->success(sprintf('Successfully reset rolesLastRefreshed for %d user(s)', $rowsAffected));
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error("Error while updating the user role refresh timestamps: " . $e->getMessage());
            return Command::FAILURE;
        }

    }
}
