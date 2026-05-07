<?php

declare(strict_types=1);

namespace Okta\WhatsApp;

use Okta\WhatsApp\Http\HttpClientInterface;
use Okta\WhatsApp\Resources\Admin\WorkspaceChannels;
use Okta\WhatsApp\Resources\Admin\WorkspaceTokens;
use Okta\WhatsApp\Resources\Admin\WorkspaceUsers;
use Okta\WhatsApp\Resources\Admin\Workspaces;

/**
 * Namespace facade for the platform-admin endpoints.
 *
 * Only useful when the calling token holds the `platform.admin` ability;
 * other tokens will see 403 from every method here.
 */
final class AdminClient
{
    private ?Workspaces $workspaces = null;
    private ?WorkspaceUsers $workspaceUsers = null;
    private ?WorkspaceTokens $workspaceTokens = null;
    private ?WorkspaceChannels $workspaceChannels = null;

    public function __construct(private readonly HttpClientInterface $http)
    {
    }

    public function workspaces(): Workspaces
    {
        return $this->workspaces ??= new Workspaces($this->http);
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
}
