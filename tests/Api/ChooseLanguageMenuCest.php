<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Services\TwiMLService;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Codeception\TestInterface;
use Tests\Support\ApiTester;
use Tests\Support\Step\Api\Menu;

final class ChooseLanguageMenuCest
{
    public const string MENU_GET_TEXT_COPY_OF_CONVERSATION = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Gather action="/menu/step/get-text-copy-of-conversation/respond" method="POST"><Say>To get a text copy of this conversation, press 1.
        To go back to the previous menu, press 9.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_CHOOSE_DEPARTMENT = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Gather action="/menu/step/choose-department/respond" method="POST"><Say>For insurance, press 1.
        For banking, press 2.
        To go back to the previous menu, press 9.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;


    public const string MENU_CHOOSE_INSURANCE_CATEGORY = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Gather action="/menu/step/choose-insurance-category/respond" method="POST"><Say>For personal insurance, press 1.
        For commercial insurance, press 2.
        To hear those options again, press *.
        To go back to the previous menu, press 9.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_CHOOSE_INSURANCE_TYPE = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Gather action="/menu/step/choose-insurance-type/respond" method="POST"><Say>For home and contents insurance, press 1.
        For car insurance, press 2.
        To hear those options again, press *.
        To go back to the previous menu, press 9.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_CHOOSE_NEW_OR_EXISTING_POLICY = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Gather action="/menu/step/choose-new-or-existing-policy/respond" method="POST"><Say>For a new policy, press 1.
        For an existing policy, press 2.
        To hear those options again, press *.
        To go back to the previous menu, press 9.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_PROVIDE_PERSONAL_DETAILS = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Say>Please provide your first and last names at the tone.
        Press the star key when finished.</Say><Record action="/menu/step/provide-personal-details/respond" finishOnKey="*" method="post" timeout="10" transcribe="true" transcribeCallback="/menu/step/provide-personal-details/record"/><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_PROVIDE_POLICY_NUMBER = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Say>Please provide the policy number at the tone, starting with "MPW".
        Press the star key when finished.</Say><Record action="/menu/step/provide-policy-number/respond" finishOnKey="*" method="post" timeout="10" transcribe="true" transcribeCallback="/menu/step/provide-policy-number/record"/><Say>We didn't receive any input. Goodbye.</Say></Response>

        EOF;

    public const string MENU_PRE_TRANSFER = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Say>Thank you. Transferring you now.</Say></Response>

        EOF;

    public const string MENU_THANK_YOU_GOODBYE = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Say>Thank you. Goodbye.</Say></Response>

        EOF;

    public const string MENU_TRANSFER_TO_CUSTOMER_SERVICE_REP = <<<EOF
        <?xml version="1.0" encoding="UTF-8"?>
        <Response><Say>Transferring you now. Goodbye.</Say></Response>

        EOF;

    public function testThatRequestsForTheChooseLanguageMenuReturnTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/choose-language', []);

        $expectedResult = <<<EOF
            <?xml version="1.0" encoding="UTF-8"?>
            <Response><Gather action="/menu/step/choose-language/respond" method="POST"><Say>Thank you for calling Happy Community Bank and Insurance Company.
            To hear the options in English, press 1.
            To hear the options in Spanish, press 2.
            To hear those options again, press *.</Say></Gather><Say>We didn't receive any input. Goodbye.</Say></Response>

            EOF;

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains($expectedResult);
    }

    public function testThatRequestsForTheGetTextCopyOfConversationMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/get-text-copy-of-conversation', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_GET_TEXT_COPY_OF_CONVERSATION);
    }

    public function testThatRequestsForTheGetDepartmentMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/choose-department', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_CHOOSE_DEPARTMENT);
    }

    public function testThatRequestsForTheChooseInsuranceCategoryMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/choose-insurance-category', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_CHOOSE_INSURANCE_CATEGORY);
    }

    public function testThatRequestsForTheChooseInsuranceTypeMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/choose-insurance-type', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_CHOOSE_INSURANCE_TYPE);
    }

    public function testThatRequestsForTheChooseNewOrExistingPolicyMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/choose-new-or-existing-policy', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_CHOOSE_NEW_OR_EXISTING_POLICY);
    }

    public function testThatRequestsForTheProvidePersonalDetailsMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/provide-personal-details', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_PROVIDE_PERSONAL_DETAILS);
    }

    public function testThatRequestsForTheProvidePolicyNumberMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/provide-policy-number', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_PROVIDE_POLICY_NUMBER);
    }

    public function testThatRequestsForThePreTransferMenuMenuReturnsTheCorrectTwiml(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/menu/step/pre-transfer-confirmation', []);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains(self::MENU_PRE_TRANSFER);
    }

    #[DataProvider('menuProvider')]
    public function testThatRequestsToTheChosenMenuReturnTheCorrectTwiml(Menu $I, Example $example): void
    {
        $I->recordCallerKeypadResponse($example['menu'], $example['digit'], $example['response']);
    }

    protected function menuProvider(): array
    {
        return [
            [
                'menu'     => 'choose-language',
                'digit'    => "1",
                'response' => self::MENU_GET_TEXT_COPY_OF_CONVERSATION,
            ],
            [
                'menu'     => 'choose-language',
                'digit'    => "2",
                'response' => self::MENU_THANK_YOU_GOODBYE,
            ],
            [
                'menu'     => 'choose-insurance-category',
                'digit'    => "1",
                'response' => self::MENU_CHOOSE_INSURANCE_TYPE,
            ],
            [
                'menu'     => 'choose-insurance-category',
                'digit'    => "2",
                'response' => self::MENU_THANK_YOU_GOODBYE,
            ],
            [
                'menu'     => 'choose-insurance-category',
                'digit'    => TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS,
                'response' => self::MENU_CHOOSE_INSURANCE_CATEGORY,
            ],
            [
                'menu'     => 'choose-insurance-category',
                'digit'    => TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU,
                'response' => self::MENU_CHOOSE_DEPARTMENT,
            ],
            [
                'menu'     => 'choose-insurance-type',
                'digit'    => "1",
                'response' => self::MENU_THANK_YOU_GOODBYE,
            ],
            [
                'menu'     => 'choose-insurance-type',
                'digit'    => "2",
                'response' => self::MENU_CHOOSE_NEW_OR_EXISTING_POLICY,
            ],
            [
                'menu'     => 'choose-insurance-type',
                'digit'    => TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS,
                'response' => self::MENU_CHOOSE_INSURANCE_TYPE,
            ],
            [
                'menu'     => 'choose-insurance-type',
                'digit'    => TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU,
                'response' => self::MENU_CHOOSE_INSURANCE_CATEGORY,
            ],
            [
                'menu'     => 'choose-new-or-existing-policy',
                'digit'    => "1",
                'response' => self::MENU_THANK_YOU_GOODBYE,
            ],
            [
                'menu'     => 'choose-new-or-existing-policy',
                'digit'    => "2",
                'response' => self::MENU_PROVIDE_PERSONAL_DETAILS,
            ],
            [
                'menu'     => 'choose-new-or-existing-policy',
                'digit'    => TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS,
                'response' => self::MENU_CHOOSE_NEW_OR_EXISTING_POLICY,
            ],
            [
                'menu'     => 'choose-new-or-existing-policy',
                'digit'    => TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU,
                'response' => self::MENU_CHOOSE_INSURANCE_TYPE,
            ],
            [
                'menu'     => 'choose-department',
                'digit'    => "1",
                'response' => self::MENU_CHOOSE_INSURANCE_CATEGORY,
            ],
            [
                'menu'     => 'choose-department',
                'digit'    => "2",
                'response' => self::MENU_THANK_YOU_GOODBYE,
            ],
            [
                'menu'     => 'choose-department',
                'digit'    => "8",
                'response' => self::MENU_TRANSFER_TO_CUSTOMER_SERVICE_REP,
            ],
            [
                'menu'     => 'choose-department',
                'digit'    => TwiMLService::DIGIT_GO_TO_PREVIOUS_MENU,
                'response' => self::MENU_GET_TEXT_COPY_OF_CONVERSATION,
            ],
            [
                'menu'     => 'choose-department',
                'digit'    => TwiMLService::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS,
                'response' => self::MENU_CHOOSE_DEPARTMENT,
            ],
        ];
    }

    #[DataProvider('transcriptionTextProvider')]
    public function testThatRequestsWithTheCallersVoiceResponseAreRecordedCorrectly(Menu $I, Example $example): void
    {
        $I->recordCallerVoiceResponse($example['menu'], $example['transcriptionText']);
    }

    protected function transcriptionTextProvider(): array
    {
        return [
            [
                'menu'              => 'provide-personal-details',
                'transcriptionText' => "Paul McCartney",
            ],
            [
                'menu'              => 'provide-policy-number',
                'transcriptionText' => "TWL123456789",
            ],
        ];
    }
}
