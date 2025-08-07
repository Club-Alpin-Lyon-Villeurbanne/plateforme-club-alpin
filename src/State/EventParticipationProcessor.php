<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\EventParticipation;
use App\Service\EventParticipationService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EventParticipationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $remover,
        private EventParticipationService $service,

    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?EventParticipation
    {
        if ($operation instanceof DeleteOperationInterface) {
            $this->service->onBeforeRemoveParticipation($data);
            $this->remover->process($data, $operation, $uriVariables, $context);

            return $this->service->onAfterRemoveParticipation($data);
        }
        $data = $this->service->onBeforeAddParticipation($data);
        $data = $this->persistor->process($data, $operation, $uriVariables, $context);
        $this->service->onAfterAddParticipation($data);

        return $data;
    }
}
