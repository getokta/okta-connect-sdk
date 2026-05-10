<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp;

use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use Okta\Connect\WhatsApp\Resources\Admin\EmbedSecret;
use Okta\Connect\WhatsApp\Resources\Admin\Organizations;
use Okta\Connect\WhatsApp\Resources\Admin\WorkspaceChannels;
use Okta\Connect\WhatsApp\Resources\Admin\WorkspaceTokens;
use Okta\Connect\WhatsApp\Resources\Admin\WorkspaceUsers;
use Okta\Connect\WhatsApp\Resources\Admin\Workspaces;

/**
 * Namespace facade for the platform-admin endpoints.
 *
 * Only useful when the calling token holds the `platform.admin` ability;
 * other tokens will see 403 from every method here.
 */
final class AdminClient
{
    private ?Workspaces $workspaces = null;
    private ?Organizations $organizations = null;
    private ?WorkspaceUsers $workspaceUsers = null;
    private ?WorkspaceTokens $workspaceTokens = null;
    private ?WorkspaceChannels $workspaceChannels = null;
    private ?EmbedSecret $embedSecret = null;

    public function __construct(private readonly HttpClientInterface $http)
    {
    }

    public function workspaces(): Workspaces
    {
        return $this->workspaces ??= new Workspaces($this->http);
    }

    /**
     * Alias for `workspaces()` that maps to the platform's actual
     * `/api/v1/admin/organizations` route — the user-facing "workspace"
     * terminology and the internal `Organization` model are the same
     * thing on the platform side.
     */
    public function organizations(): Organizations
    {
        return $this->organizations ??= new Organizations($this->http);
    }

    public function workspaceUsers(): WorkspaceUsers
    {
        return $this->workspaceUsers ??= new WorkspaceUsers($this->http);
    }

    public function workspaceTokens(): WorkspaceTokens
    {
        return $this->workspaceTokens ??= new WorkspaceTokens($this->http);
    }

    public function workspaceChannels(): WorkspaceChannels
    {
        return $this->workspaceChannels ??= new WorkspaceChannels($this->http);
    }

    public function embedSecret(): EmbedSecret
    {
        return $this->embedSecret ??= new EmbedSecret($this->http);
    }
}
