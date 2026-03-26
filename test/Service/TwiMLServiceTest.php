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
        $this->twimlService = new TwiMLService(new VoiceResponse());

        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu($menu),
            $this->twimlService
                ->getMenu($menu)
                ->asXML(),
        );
    }

    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith(['8'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testThatHandleChooseDepartmentMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleChooseDepartmentMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-category"),
                    $response->asXML(),
                );
                break;

            case '2':
                $response = new TwiMLService(new VoiceResponse())->handleChooseDepartmentMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case '8':
                $response = new TwiMLService(new VoiceResponse())->handleChooseDepartmentMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("talk-to-customer-service-rep-redirection"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(new VoiceResponse())->handleChooseDepartmentMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("get-text-copy-of-conversation"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS:
                $response = new TwiMLService(new VoiceResponse())->handleChooseDepartmentMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-department"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testThatHandleChooseLanguageMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleChooseLanguageMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("get-text-copy-of-conversation"),
                    $response->asXML(),
                );
                break;

            case '2':
                $response = new TwiMLService(new VoiceResponse())->handleChooseLanguageMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS:
                $response = new TwiMLService(new VoiceResponse())->handleChooseLanguageMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-language"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith(['1'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    public function testThatHandleTextCopyOfConversationMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleGetTextCopyOfConversationMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-department"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(new VoiceResponse())->handleGetTextCopyOfConversationMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-language"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testThatHandleChooseInsuranceCategoryMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceCategoryMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-type"),
                    $response->asXML(),
                );
                break;

            case '2':
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceCategoryMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceCategoryMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-department"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS:
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceCategoryMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-category"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testThatHandleChooseInsuranceTypeMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceTypeMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case '2':
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceTypeMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-new-or-existing-policy"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceTypeMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-category"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS:
                $response = new TwiMLService(new VoiceResponse())->handleChooseInsuranceTypeMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-type"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith([TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU])]
    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testThatHandleChooseNewOrExistingPolicyMenuOperatesCorrectly(string $digit): void
    {
        switch ($digit) {
            case '1':
                $response = new TwiMLService(new VoiceResponse())->handleChooseNewOrExistingPolicyMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("thank-you-goodbye"),
                    $response->asXML(),
                );
                break;

            case '2':
                $response = new TwiMLService(new VoiceResponse())->handleChooseNewOrExistingPolicyMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("provide-personal-details"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU:
                $response = new TwiMLService(new VoiceResponse())->handleChooseNewOrExistingPolicyMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-insurance-type"),
                    $response->asXML(),
                );
                break;

            case TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS:
                $response = new TwiMLService(new VoiceResponse())->handleChooseNewOrExistingPolicyMenu($digit);
                $this->assertXmlStringEqualsXmlString(
                    $this->getExpectedMenu("choose-new-or-existing-policy"),
                    $response->asXML(),
                );
                break;
        }
    }

    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testCanHandleChooseProvidePersonalDetailsMenu(string $digit): void
    {
        $response = new TwiMLService(new VoiceResponse())->handleProvidePersonalDetailsMenu($digit);
        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu("choose-new-or-existing-policy"),
            $response->asXML(),
        );
    }

    #[TestWith(['Dave Grohl'])]
    public function testThatHandleChooseProvidePersonalDetailsMenuCorrectly(string $transcriptionText): void
    {
        $response = new TwiMLService(new VoiceResponse())
            ->handleProvidePersonalDetailsRecordFullNameMenu($transcriptionText);
        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu("provide-policy-number"),
            $response->asXML(),
        );
    }

    #[TestWith([TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS])]
    public function testCanHandleChooseProvidePolicyNumberMenuCorrectly(string $digit): void
    {
        $response = new TwiMLService(new VoiceResponse())
            ->handleProvidePolicyNumberMenu($digit);
        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu("provide-personal-details"),
            $response->asXML(),
        );
    }

    #[TestWith(['MX1234567890'])]
    public function testThatHandleChooseProvidePolicyNumberMenuRecordFullNameCorrectly(string $transcriptionText): void
    {
        $response = new TwiMLService(new VoiceResponse())
            ->handleProvidePolicyNumberRecordPolicyNumberMenu($transcriptionText);
        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu("pre-transfer-confirmation"),
            $response->asXML(),
        );
    }

    public function testThatHandlePreTransferConfirmationMenuOperatesCorrectly(): void
    {
        $response = new TwiMLService(new VoiceResponse())->handlePreTransferConfirmationMenu();
        $this->assertXmlStringEqualsXmlString(
            $this->getExpectedMenu("pre-transfer-confirmation"),
            $response->asXML(),
        );
    }

    private function getExpectedMenu(string $menu): string
    {
        return file_get_contents(
            sprintf("%s/../data/menu/%s-menu.xml", __DIR__, $menu),
        );
    }
}
