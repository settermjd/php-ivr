<?php

declare(strict_types=1);

namespace App\Services;

use Twilio\TwiML\VoiceResponse;

use function array_keys;
use function file_get_contents;
use function in_array;
use function sprintf;
use function trim;

/**
 * This class simplifies generating the required TwiML
 */
class TwiMLService
{
    public const string BASE_ACTION                              = "/menu/step";
    public const string DEFAULT_LANGUAGE                         = 'en-AU';
    public const string DIGIT_GO_TO_PREVIOUS_MENU                = '9';
    public const string DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS = '*';
    public const string INVALID_MENU_RESPONSE                    = "That is not a valid menu. Goodbye.";
    public const string NO_INPUT_RESPONSE                        = "We didn't receive any input. Goodbye.";
    public const string THANK_YOU_GOODBYE_RESPONSE               = "Thank you. Goodbye.";

    /** @var array <string,array<string,string>> */
    private array $menuOptions = [
        "choose-department"             => [
            "parent" => "get-text-copy-of-conversation",
            "next"   => "choose-insurance-category",
        ],
        "choose-insurance-category"     => [
            "parent" => "choose-department",
            "next"   => "choose-insurance-type",
        ],
        "choose-insurance-type"         => [
            "parent" => "choose-insurance-category",
            "next"   => "choose-new-or-existing-policy",
        ],
        "choose-language"               => [
            "parent" => null,
            "next"   => "get-text-copy-of-conversation",
        ],
        "choose-new-or-existing-policy" => [
            "parent" => "choose-insurance-type",
            "next"   => "provide-personal-details",
        ],
        "get-text-copy-of-conversation" => [
            "parent" => "choose-language",
            "next"   => "choose-department",
        ],
        "pre-transfer-confirmation"     => [
            "parent" => "choose-department",
            "next"   => null,
        ],
        "provide-personal-details"      => [
            "parent" => "choose-new-or-existing-policy",
            "next"   => "provide-policy-number",
        ],
        "provide-policy-number"         => [
            "parent" => "provide-personal-details",
            "next"   => "pre-transfer-confirmation",
        ],
    ];

    public function __construct(
        private VoiceResponse $response,
        private string $menuLanguage = self::DEFAULT_LANGUAGE
    ) {}

    private function getPreviousMenu(string $menu): VoiceResponse
    {
        return $this->response;
    }

    private function getRedirectToCustomerServiceRepMenu(): VoiceResponse
    {
        $this->response->say(
            "Transferring you now. Goodbye.",
            ['language' => $this->menuLanguage]
        );
        return $this->response;
    }

    /**
     * This function returns the contents for a TwiML Say verb from the filesystem
     */
    private function getSayMenuContent(string $menu): string
    {
        return trim(
            file_get_contents(
                sprintf("%s/../../data/menus/%s.txt", __DIR__, $menu),
            ),
        );
    }

    /**
     * Returns the relevant TwiML for the requested application menu
     *
     * For example:
     *
     * <?xml version="1.0" encoding="UTF-8"?>
     * <Response>
     *     <Gather action="/menu/step/provide-policy-number"
     *             method="GET">
     *         <Say>Please provide your first and last names.</Say>
     *     </Gather>
     *     <Say>We didn't receive any input. Goodbye.</Say>
     * </Response>
     */
    public function getMenu(string $menu): VoiceResponse
    {
        if ($menu === "thank-you-goodbye") {
            $this->response->say(
                trim(self::THANK_YOU_GOODBYE_RESPONSE),
                ['language' => $this->menuLanguage]
            );
            return $this->response;
        }

        if (! in_array($menu, array_keys($this->menuOptions))) {
            $this->response->say(
                trim(self::INVALID_MENU_RESPONSE),
                ['language' => $this->menuLanguage]
            );
            return $this->response;
        }

        return match ($menu) {
            "pre-transfer-confirmation" => (function () use ($menu) {
                $this->response->say(
                    $this->getSayMenuContent($menu),
                    ['language' => $this->menuLanguage]
                );
                return $this->response;
            })(),
            "choose-language",
            "get-text-copy-of-conversation",
            "choose-department",
            "choose-insurance-category",
            "choose-insurance-type",
            "choose-new-or-existing-policy" => (function () use ($menu) {
                $this->addGatherVerb($menu);
                $this->response->say(
                    self::NO_INPUT_RESPONSE,
                    ['language' => $this->menuLanguage]
                );
                return $this->response;
            })(),
            "provide-personal-details",
            "provide-policy-number" => (function () use ($menu) {
                $this->response->say(
                    $this->getSayMenuContent($menu),
                    ['language' => $this->menuLanguage]
                );
                $this->response->record(
                    [
                        'action'             => sprintf('%s/%s/respond', self::BASE_ACTION, $menu),
                        'finishOnKey'        => '*',
                        'method'             => 'post',
                        'timeout'            => '10',
                        'transcribe'         => 'true',
                        'transcribeCallback' => sprintf('%s/%s/record', self::BASE_ACTION, $menu),
                    ],
                );
                $this->response->say(
                    self::NO_INPUT_RESPONSE,
                    ['language' => $this->menuLanguage]
                );

                return $this->response;
            })(),

            // Change this to a default response
            default => $this->addGatherVerb($menu),
        };
    }

    /**
     * This function generates a TwiML menu
     */
    private function addGatherVerb(string $menu): void
    {
        $gather = $this->response->gather(
            [
                'action' => sprintf('%s/%s/respond', self::BASE_ACTION, $menu),
                'method' => 'POST',
            ],
        );
        $gather->say(
            $this->getSayMenuContent($menu),
            ['language' => $this->menuLanguage],
        );
    }

    /**
     * This function handles the user's choice for the "Choose Department" menu
     */
    public function handleChooseDepartmentMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['choose-department']['next'],
                );
            })(),
            '2' => (function (): VoiceResponse {
                return $this->getMenu('thank-you-goodbye');
            })(),
            '8' => $this->getRedirectToCustomerServiceRepMenu(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-department']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-department'),
        };
    }

    public function handleChooseLanguageMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['choose-language']['next'],
                );
            })(),
            '2' => (function (): VoiceResponse {
                return $this->getMenu('thank-you-goodbye');
            })(),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-language'),
        };
    }

    public function handleChooseInsuranceCategoryMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['choose-insurance-category']['next'],
                );
            })(),
            '2' => (function (): VoiceResponse {
                return $this->getMenu('thank-you-goodbye');
            })(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    public function handleGetTextCopyOfConversationMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['get-text-copy-of-conversation']['next'],
                );
            })(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['get-text-copy-of-conversation']['parent'],
            ),
        };
    }

    /**
     * This function handles the user's choice for the "Choose Insurance Type" menu
     */
    public function handleChooseInsuranceTypeMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu('thank-you-goodbye');
            })(),
            '2' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['choose-insurance-type']['next'],
                );
            })(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-type']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-insurance-type'),
        };
    }

    /**
     * This function handles the user's choice for the "Choose Personal Insurance Type" menu
     */
    public function handlePersonalInsuranceTypeMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    /**
     * This function handles the user's choice for the "Choose New Or Existing Policy" menu
     */
    public function handleChooseNewOrExistingPolicyMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                return $this->getMenu('thank-you-goodbye');
            })(),
            '2' => (function (): VoiceResponse {
                return $this->getMenu(
                    $this->menuOptions['choose-new-or-existing-policy']['next'],
                );
            })(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-new-or-existing-policy']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu('choose-new-or-existing-policy'),
        };
    }

    /**
     * This function handles the user's choice for the "Provide Personal Details" menu
     */
    public function handleProvidePersonalDetailsMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu(
                $this->menuOptions['provide-personal-details']['next'],
            ),
        };
    }

    public function handleProvidePersonalDetailsRecordFullNameMenu(string $transcriptionText): VoiceResponse
    {
        return $this->getMenu($this->menuOptions['provide-personal-details']['next']);
    }

    /**
     * This function handles the user's choice for the "Provide Policy Number" menu
     */
    public function handleProvidePolicyNumberMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            self::DIGIT_REPEAT_CURRENT_OR_CONTINUE_OPTIONS => $this->getMenu(
                $this->menuOptions['provide-policy-number']['next'],
            ),
        };
    }

    public function handleProvidePolicyNumberRecordPolicyNumberMenu(string $transcriptionText): VoiceResponse
    {
        return $this->getMenu($this->menuOptions['provide-policy-number']['next']);
    }

    /**
     * This function handles the user's choice for the "Pre-Transfer Confirmation" menu
     */
    public function handlePreTransferConfirmationMenu(): VoiceResponse
    {
        return $this->getMenu('pre-transfer-confirmation');
    }
}
