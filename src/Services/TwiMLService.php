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
    public const string BASE_ACTION           = "/menu/step/";
    public const string NO_INPUT_RESPONSE     = "We didn't receive any input. Goodbye.";
    public const string INVALID_MENU_RESPONSE = "That is not a valid menu. Goodbye.";

    private array $menuOptions = [
        "choose-department"             => "choose-insurance-category",
        "choose-insurance-category"     => "choose-insurance-type",
        "choose-insurance-type"         => "choose-new-or-existing-policy",
        "choose-language"               => "get-text-copy-of-conversation",
        "choose-new-or-existing-policy" => "provide-personal-details",
        "get-text-copy-of-conversation" => "choose-department",
        "pre-transfer-confirmation"     => null,
        "provide-personal-details"      => "provide-policy-number",
        "provide-policy-number"         => "pre-transfer-confirmation",
    ];

    public function __construct(private readonly VoiceResponse $response) {}

    public function getMenu(string $menu): VoiceResponse
    {
        if (! in_array($menu, array_keys($this->menuOptions))) {
            $this->response->say(self::INVALID_MENU_RESPONSE);
            return $this->response;
        }

        return $menu !== "pre-transfer-confirmation"
            ? $this->buildMenu($menu, $this->menuOptions[$menu])
            : $this->buildMenu($menu, addNoInputResponse: false);
    }

    private function buildMenu(string $menu, ?string $action = null, bool $addNoInputResponse = true): VoiceResponse
    {
        $baseMenu = trim(
            file_get_contents(
                sprintf("%s/../../data/%s.txt", __DIR__, $menu),
            ),
        );

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
