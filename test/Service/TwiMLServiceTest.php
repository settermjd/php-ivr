<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Services\TwiMLService;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Twilio\TwiML\VoiceResponse;

use function file_get_contents;
use function sprintf;

class TwiMLServiceTest extends TestCase
{
    private TwiMLService $twimlService;

    #[TestWith(['choose-department'])]
    #[TestWith(['choose-insurance-category'])]
    #[TestWith(['choose-insurance-type'])]
    #[TestWith(['choose-language'])]
    #[TestWith(['choose-new-or-existing-policy'])]
    #[TestWith(['get-text-copy-of-conversation'])]
    #[TestWith(['pre-transfer-confirmation'])]
    #[TestWith(['provide-personal-details'])]
    #[TestWith(['provide-policy-number'])]
    public function testCanGenerateMenuCorrectly(string $menu): void
    {
        $session = $this->createStub(SessionInterface::class);

        $this->twimlService = new TwiMLService(new VoiceResponse(), $session);

        $generatedMenu = $this->twimlService->getMenu($menu);
        $this->assertXmlStringEqualsXmlString(
            file_get_contents(
                sprintf(
                    "%s/../data/menu/%s-menu.xml",
                    __DIR__,
                    $menu,
                ),
            ),
            $generatedMenu->asXML(),
        );
    }

    /**
     */
    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OPTIONS])]
    public function testThatHandleChooseDepartmentMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $session = $this->createMock(SessionInterface::class);
                $session
                    ->expects($this->once())
                    ->method('set')
                    ->with('department', 'insurance');

                $response = new TwiMLService(new VoiceResponse(), $session)
                                ->handleChooseDepartmentMenu($digit);

                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-category"),
                    $response->asXML(),
                );
                break;

            case '2':
                $session = $this->createMock(SessionInterface::class);
                $session
                    ->expects($this->once())
                    ->method('set')
                    ->with('department', 'banking');

                $response = new TwiMLService(new VoiceResponse(), $session)
                                ->handleChooseDepartmentMenu($digit);

                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(
                    new VoiceResponse(),
                    $this->createStub(SessionInterface::class),
                )->handleChooseDepartmentMenu($digit);

                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("get-text-copy-of-conversation"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OPTIONS:
                $response = new TwiMLService(
                    new VoiceResponse(),
                    $this->createStub(SessionInterface::class),
                )->handleChooseDepartmentMenu($digit);

                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-department"),
                    $response->asXML(),
                );
                break;
        }
    }

    private function getExpectedMenu(string $menu): string
    {
        return file_get_contents(
            sprintf("%s/../data/menu/%s-menu.xml", __DIR__, $menu),
        );
    }
}
