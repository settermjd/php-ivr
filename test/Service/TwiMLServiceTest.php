<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Services\TwiMLService;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Twilio\TwiML\VoiceResponse;

use function file_get_contents;
use function sprintf;

class TwiMLServiceTest extends TestCase
{
    private TwiMLService $twimlService;

    public function setUp(): void
    {
        $this->twimlService = new TwiMLService(new VoiceResponse());
    }

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
        $this->assertXmlStringEqualsXmlString(
            file_get_contents(
                sprintf(
                    "%s/../data/menu/%s-menu.xml",
                    __DIR__,
                    $menu,
                ),
            ),
            $this->twimlService->getMenu($menu)->asXML(),
        );
    }
}
