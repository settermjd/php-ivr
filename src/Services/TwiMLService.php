<?php

declare(strict_types=1);

namespace App\Services;

use Odan\Session\SessionInterface;
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
    public const string BASE_ACTION                  = "/menu/step";
    public const string DIGIT_GO_TO_PREVIOUS_MENU    = '9';
    public const string DIGIT_REPEAT_CURRENT_OPTIONS = '*';
    public const string INVALID_MENU_RESPONSE        = "That is not a valid menu. Goodbye.";
    public const string THANK_YOU_GOODBYE_RESPONSE   = "Thank you. Goodbye.";
    public const string NO_INPUT_RESPONSE            = "We didn't receive any input. Goodbye.";

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
            "parent" => "choose-department",
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
        private readonly SessionInterface $session,
    ) {}

    public function getPreviousMenu(string $menu): VoiceResponse
    {
        return $this->response;
    }

    public function getRedirectToCustomerServiceRepMenu(): VoiceResponse
    {
        $this->response->say("Transferring you now. Goodbye.");
        return $this->response;
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
            $this->response->say(trim(self::THANK_YOU_GOODBYE_RESPONSE));
            return $this->response;
        }

        if (! in_array($menu, array_keys($this->menuOptions))) {
            $this->response->say(trim(self::INVALID_MENU_RESPONSE));
            return $this->response;
        }

        return match ($menu) {
            "pre-transfer-confirmation" => (function () use ($menu) {
                $this->response->say(
                    trim(
                        file_get_contents(
                            sprintf("%s/../../data/%s.txt", __DIR__, $menu),
                        ),
                    ),
                );
                return $this->response;
            })(),
            default => $this->buildGatherMenu($menu),
        };
    }

    /**
     * This function generates a TwiML menu
     */
    private function buildGatherMenu(
        string $menu,
        bool $addNoInputResponse = true,
    ): VoiceResponse {
        $baseMenu = trim(
            file_get_contents(
                sprintf("%s/../../data/%s.txt", __DIR__, $menu),
            ),
        );

        $gather = $this->response->gather(
            [
                'action' => sprintf('%s/%s/respond', self::BASE_ACTION, $menu),
                'method' => 'GET',
            ],
        );
        $gather->say($baseMenu);

        if ($addNoInputResponse) {
            $this->response->say(self::NO_INPUT_RESPONSE);
        }

        return $this->response;
    }

    /**
     * This function handles the user's choice for the "Choose Department" menu
     */
    public function handleChooseDepartmentMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => (function (): VoiceResponse {
                $this->session->set("department", "insurance");
                return $this->getMenu(
                    $this->menuOptions['choose-department']['next'],
                );
            })(),
            '2' => (function (): VoiceResponse {
                $this->session->set("department", "banking");
                return $this->getMenu('thank-you-goodbye');
            })(),
            '8' => $this->getRedirectToCustomerServiceRepMenu(),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-department']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-department'),
        };
    }

    public function handleChooseLanguageMenu(string $digit): VoiceResponse
    {
        return $this->response;
    }

    public function handleChooseInsuranceCategoryMenu(string $digit): VoiceResponse
    {
        return $this->response;
    }

    public function handleGetTextCopyOfConversationMenu(string $digit): VoiceResponse
    {
        return $this->response;
    }

    /**
     * This function handles the user's choice for the "Choose Insurance Type" menu
     */
    public function handleChooseInsuranceTypeMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
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
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    /**
     * This function handles the user's choice for the "Choose New Or Existing Policy" menu
     */
    public function handleChooseNewOrExistingPolicyMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    /**
     * This function handles the user's choice for the "Provide Personal Details" menu
     */
    public function handleProvidePersonalDetailsMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    /**
     * This function handles the user's choice for the "Provide Policy Number" menu
     */
    public function handleProvidePolicyNumberMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }

    /**
     * This function handles the user's choice for the "Pre-Transfer Confirmation" menu
     */
    public function handlePreTransferConfirmationMenu(string $digit): VoiceResponse
    {
        return match ($digit) {
            '1' => $this->getMenu('choose-personal-insurance'),
            '2' => $this->getMenu('choose-commercial-insurance'),
            self::DIGIT_GO_TO_PREVIOUS_MENU => $this->getMenu(
                $this->menuOptions['choose-insurance-category']['parent'],
            ),
            self::DIGIT_REPEAT_CURRENT_OPTIONS => $this->getMenu('choose-insurance-category'),
        };
    }
}
