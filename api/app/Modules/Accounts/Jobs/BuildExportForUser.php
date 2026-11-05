<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Jobs;

use App\Models\User;
use App\Modules\Accounts\Actions\BuildDataExport;
use App\Modules\Accounts\Mail\ExportReadyMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Build the export, then email a link that expires.
 *
 * Queued because it will eventually copy media out of object storage. The link
 * is a **signed, temporary** URL: signed so the path cannot be forged, and
 * temporary so a forwarded email stops working. The route still checks the
 * caller is the owner, because a signature proves the URL was issued by us,
 * not that the person holding it is the right person.
 */
final class BuildExportForUser implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $userId) {}

    public function handle(BuildDataExport $builder): void
    {
        $user = User::find($this->userId);

        if ($user === null || $user->email === null) {
            // A deleted account, or one with no address to send to. Nothing to
            // do, and nothing worth failing over.
            return;
        }

        $path = $builder->handle($user);

        $url = URL::temporarySignedRoute(
            'me.export.download',
            now()->addHours(BuildDataExport::LINK_TTL_HOURS),
            ['user' => $user->getKey(), 'path' => base64_encode($path)],
        );

        Mail::to($user->email)->send(new ExportReadyMail(
            str($user->name)->before(' ')->toString(),
            $url,
            BuildDataExport::LINK_TTL_HOURS,
        ));
    }
}
