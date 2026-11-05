<?php

declare(strict_types=1);

namespace App\Modules\Ai\Http\Controllers;

use App\Modules\Ai\Actions\CapGuard;
use App\Modules\Ai\Http\Resources\AllowanceResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AllowanceController
{
    /**
     * What this couple has left this month.
     *
     * No id in the route: a person has at most one live couple and the
     * database guarantees it, so an identifier here would only be a way to
     * ask about somebody else's.
     */
    public function __invoke(Request $request, CapGuard $caps): AllowanceResource
    {
        $couple = $request->user()?->couple();

        if ($couple === null) {
            // An allowance is a property of a couple, so a solo user has none
            // rather than an empty one. 404 matches `GET /api/couple`.
            throw new NotFoundHttpException;
        }

        return new AllowanceResource([
            'couple' => $couple,
            'allowance' => $caps->allowance($couple),
        ]);
    }
}
