<?php

declare(strict_types=1);

namespace App\Services;

use Twilio\TwiML\VoiceResponse;

/**
 * This class simplifies generating the required TwiML
 */
class TwiMLService
{
    public const string MENU_LANGUAGE_OPTIONS = <<<EOF
Thank you for calling Happy Community Bank and Insurance Company.
To hear the options in English, press 1.
To hear the options in Spanish, press 2.
To hear those options again, press *.
EOF;

    public const string MENU_TEXT_COPY_OF_CONVERSATION = <<<EOF
To get a text copy of this conversation, press 1.
To go back to the previous menu, press 9.
EOF;

    public const string MENU_CHOOSE_DEPARTMENT = <<<EOF
For insurance, press 1.
For banking, press 2.
To go back to the previous menu, press 9.
EOF;

    public const string MENU_CHOOSE_INSURANCE_CATEGORY = <<<EOF
For personal insurance, press 1.
For commercial insurance, press 2.
To hear those options again, press *.
To go back to the previous menu, press 9.
EOF;

    public const string MENU_CHOOSE_INSURANCE_TYPE = <<<EOF
For home and contents insurance, press 1.
For car insurance, press 2.
To hear those options again, press *.
To go back to the previous menu, press 9.
EOF;

    public const string MENU_CHOOSE_NEW_OR_EXISTING_POLICY = <<<EOF
For a new policy, press 1.
For an existing policy, press 2.
To hear those options again, press *.
To go back to the previous menu, press 9.
EOF;

    public const string MENU_PROVIDE_PERSONAL_DETAILS = <<<EOF
Please provide your first and last names.
EOF;

    public const string MENU_PROVIDE_POLICY_NUMBER = <<<EOF
Please provide the policy number, starting with "MPW".
EOF;

    public const string MENU_PRE_TRANSFER = <<<EOF
Thank you. Transferring you now.
EOF;

    public const string BASE_ACTION       = "/menu/step/";
    public const string NO_INPUT_RESPONSE = "We didn't receive any input. Goodbye!";

    public function __construct(private readonly VoiceResponse $response) {}

    public function getMenu(string $menu): VoiceResponse
    {
        return match ($menu) {
            "choose-department" => $this->buildMenu(self::MENU_CHOOSE_DEPARTMENT, "choose-department"),
            "choose-language" => $this->buildMenu(self::MENU_LANGUAGE_OPTIONS, "choose-language"),
            "get-text-copy-of-conversation" => $this->buildMenu(self::MENU_TEXT_COPY_OF_CONVERSATION, "get-text-copy-of-conversation"),
            "choose-insurance-category" => $this->buildMenu(self::MENU_CHOOSE_INSURANCE_CATEGORY, "choose-insurance-category"),
            "choose-insurance-type" => $this->buildMenu(self::MENU_CHOOSE_INSURANCE_TYPE, "choose-insurance-type"),
            "choose-new-or-existing-policy" => $this->buildMenu(self::MENU_CHOOSE_NEW_OR_EXISTING_POLICY, "choose-new-or-existing-policy"),
            "provide-personal-details" => $this->buildMenu(self::MENU_PROVIDE_PERSONAL_DETAILS, "provide-personal-details"),
            "provide-policy-number" => $this->buildMenu(self::MENU_PROVIDE_PERSONAL_DETAILS, "provide-personal-details"),
            "pre-transfer-confirmation" => $this->buildMenu(self::MENU_PROVIDE_PERSONAL_DETAILS, "provide-personal-details"),
        };
    }

    private function buildMenu(string $baseMenu, ?string $action = null, bool $addNoInputResponse = true): VoiceResponse
    {
        // This is only for items with just a base menu.
        if ($action === null) {
            $this->response->say($baseMenu);
            return $this->response;
        }

        $gather = $this->response->gather(
            [
                'action' => self::BASE_ACTION . $action,
                'method' => 'GET',
            ],
        );
        $gather->say($baseMenu);

        if ($addNoInputResponse) {
            $this->response->say(self::NO_INPUT_RESPONSE);
        }

        return $this->response;
    }
}
