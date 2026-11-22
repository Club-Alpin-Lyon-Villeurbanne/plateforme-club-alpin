<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Dto\ExpenseReportCreateDto;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Repository\ExpenseReportRepository;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExpenseReportCreateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private ValidatorInterface $validator,
        private Security $security,
        private ExpenseReportRepository $expenseReportRepository,
        private EvtRepository $evtRepository,
        private CommissionRepository $commissionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExpenseReport
    {
        if (!$data instanceof ExpenseReportCreateDto) {
            throw new \InvalidArgumentException('Invalid input');
        }

        $user = $this->security->getUser();
        if (!$user) {
            throw new \LogicException('User must be logged in to create an expense report');
        }

        if (null !== $data->type) {
            $expenseReport = $this->nouvelleNoteHorsSortie($data, $user);
            $this->validator->validate($expenseReport);

            return $this->persistProcessor->process($expenseReport, $operation, $uriVariables, $context);
        }

        //
        // TODO: Check if the user is part of the encadrants group
        //
        $event = $this->evtRepository->find($data->eventId);
        if (!$event) {
            throw new NotFoundHttpException('Event not found');
        }

        $existingReport = $this->expenseReportRepository->findOneBy([
            'user' => $user,
            'event' => $event,
        ]);

        if ($existingReport) {
            throw new ConflictHttpException('An expense report already exists for this user and event');
        }

        $expenseReport = new ExpenseReport();
        $expenseReport->setUser($user);
        $expenseReport->setEvent($event);
        $expenseReport->setStatus(ExpenseReportStatusEnum::DRAFT);
        $expenseReport->setRefundRequired(false);

        $this->validator->validate($expenseReport);

        return $this->persistProcessor->process($expenseReport, $operation, $uriVariables, $context);
    }

    private function nouvelleNoteHorsSortie(ExpenseReportCreateDto $data, User $demandeur): ExpenseReport
    {
        $note = ExpenseReport::nouvelleNoteHorsSortie($demandeur, ExpenseReportTypeEnum::from($data->type));
        $note->setCommission(null !== $data->commissionId ? $this->commissionRepository->find($data->commissionId) : null);
        $note->setObjet($data->objet);
        $note->setDateDepense(null !== $data->dateDepense ? new \DateTimeImmutable($data->dateDepense) : null);
        $note->setLienFormation($data->lienFormation);

        return $note;
    }
}
