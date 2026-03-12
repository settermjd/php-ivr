<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use function sprintf;

class Menu extends \Tests\Support\ApiTester
{
    public function recordCallerKeypadResponse(string $requestedMenu, string $digit, string $menuResponse)
    {
        $I = $this;

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost(sprintf('/menu/step/%s/respond', $requestedMenu), [
            'CallSid' => 'CAa0000000000000000000000000000000',
            'Digits' => $digit,
            'From'   => "+61123456789",
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsXml();
        $I->seeResponseContains($menuResponse);
    }
}
