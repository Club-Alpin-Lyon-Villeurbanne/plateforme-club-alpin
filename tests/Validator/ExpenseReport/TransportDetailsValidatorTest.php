<?php

namespace App\Tests\Validator\ExpenseReport;

use App\Validator\ExpenseReport\AttachmentValidator;
use App\Validator\ExpenseReport\TransportDetailsValidator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransportDetailsValidatorTest extends TestCase
{
    /**
     * @dataProvider transportAttachmentRequirementsProvider
     */
    public function testValidateRequiredAttachmentsDependsOnPositiveAmounts(array $transport, array $expectedRequiredAttachments): void
    {
        $attachments = new ArrayCollection();
        $context = $this->createMock(ExecutionContextInterface::class);

        $attachmentValidator = $this->createMock(AttachmentValidator::class);
        $attachmentValidator->expects($this->once())
            ->method('validate')
            ->with($attachments, $expectedRequiredAttachments, $context);

        $validator = new TransportDetailsValidator($attachmentValidator);
        $validator->validate($transport, $attachments, $context);
    }

    public static function transportAttachmentRequirementsProvider(): array
    {
        return [
            'rental minibus: fuel 0 and rental > 0 => rental attachment only' => [
                [
                    'type' => 'RENTAL_MINIBUS',
                    'fuelExpense' => 0,
                    'rentalPrice' => 150,
                    'passengerCount' => 8,
                ],
                ['rentalPrice'],
            ],
            'rental minibus: fuel > 0 and rental 0 => fuel attachment only' => [
                [
                    'type' => 'RENTAL_MINIBUS',
                    'fuelExpense' => 55,
                    'rentalPrice' => 0,
                    'passengerCount' => 8,
                ],
                ['fuelExpense'],
            ],
            'club minibus: fuel 0 => no mandatory attachment' => [
                [
                    'type' => 'CLUB_MINIBUS',
                    'fuelExpense' => 0,
                    'distance' => 120,
                    'passengerCount' => 7,
                ],
                [],
            ],
            'public transport: ticket 0 => no mandatory attachment' => [
                [
                    'type' => 'PUBLIC_TRANSPORT',
                    'ticketPrice' => 0,
                ],
                [],
            ],
            'rental minibus: both amounts > 0 => both attachments' => [
                [
                    'type' => 'RENTAL_MINIBUS',
                    'fuelExpense' => 20,
                    'rentalPrice' => 120,
                    'passengerCount' => 8,
                ],
                ['fuelExpense', 'rentalPrice'],
            ],
        ];
    }
}
