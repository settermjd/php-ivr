<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Services\TwiMLService;
use PHPUnit\Framework\TestCase;
use Twilio\TwiML\VoiceResponse;

use function file_get_contents;

class TwiMLServiceTest extends TestCase
{
    private TwiMLService $twimlService;

    public function setUp(): void
    {
        $this->twimlService = new TwiMLService(new VoiceResponse());
    }

    public function testCanGenerateLanguageMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-initial-menu.xml");

        $this->assertXmlStringEqualsXmlString($output, $this->twimlService->getMenu("choose-language")->asXML());
    }

    public function testCanGenerateTextCopyOfConversationMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-conversation-text-copy-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("get-text-copy-of-conversation")->asXML(),
        );
    }

    public function testCanGenerateChooseDepartmentMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-choose-department-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("choose-department")->asXML(),
        );
    }

    public function testCanGenerateInsuranceCategoryMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-insurance-category-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("choose-insurance-category")->asXML(),
        );
    }

    public function testCanGenerateChooseInsuranceTypeMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-insurance-type-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("choose-insurance-type")->asXML(),
        );
    }

    public function testCanGenerateChooseNewOrExistingPolicyMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-new-or-existing-policy-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("choose-new-or-existing-policy")->asXML(),
        );
    }

    public function testCanGenerateProvidePersonalDetailsMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-provide-personal-details-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("provide-personal-details")->asXML(),
        );
    }

    public function testCanGenerateProvidePolicyNumberMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-provide-policy-number-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("provide-policy-number")->asXML(),
        );
    }

    public function testCanGeneratePreTransferMenu(): void
    {
        $output = file_get_contents(__DIR__ . "/../data/twiml-service-pre-transfer-menu.xml");

        $this->assertXmlStringEqualsXmlString(
            $output,
            $this->twimlService->getMenu("pre-transfer-confirmation")->asXML(),
        );
    }
}
