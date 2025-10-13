<?php

namespace App\Controller;

use App\Form\SignalementType;
use App\Mailer\Mailer;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SignalementController extends AbstractController
{
    public function __construct(
        protected string $recipientsEmail,
    ) {
    }

    #[Route(path: '/signalement/formulaire', name: 'formulaire_signalement')]
    #[Template('signalement/formulaire.html.twig')]
    public function form(
        Request $request,
        Mailer $mailer,
    ): array {
        $confirmMessage = null;
        $form = $this->createForm(SignalementType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();

            // envoi du mail
            $receivers = $this->getMailRecipients();

            $mailer->send(
                $receivers,
                'transactional/signalement', [
                    'message' => $data['signalement']['content'],
                    'contact_state' => $data['signalement']['contact_status'],
                    'object' => $data['signalement']['object'],
                    'contact_email' => !empty($data['signalement']['contact_email']) ? $data['signalement']['contact_email'] : null,
                    'contact_phone' => !empty($data['signalement']['contact_phone']) ? $data['signalement']['contact_phone'] : null,
                ]
            );

            $confirmMessage = 'Votre signalement a bien été envoyé.';
        }

        return [
            'form' => $form,
            'confirm_message' => $confirmMessage,
        ];
    }

    private function getMailRecipients(): array
    {
        $recipients = [];

        if (!empty($this->recipientsEmail)) {
            // plusieurs emails séparés par des virgules
            $emails = array_map('trim', explode(',', $this->recipientsEmail));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                }
            }
        }

        return $recipients;
    }
}
