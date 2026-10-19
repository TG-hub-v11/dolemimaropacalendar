<?php

namespace App\Models\Concerns;

use App\Models\Office;
use App\Models\OfficeMembership;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasOfficeAccess
{
    protected ?array $permissionCache = null;

    public function memberships(): HasMany
    {
        return $this->hasMany(OfficeMembership::class);
    }

    /** Permission slugs held through any membership (cached per request instance). */
    public function permissionSlugs(): array
    {
        return $this->permissionCache ??= $this->memberships()
            ->with('role.permissions')->get()
            ->flatMap(fn ($m) => $m->role->permissions->pluck('slug'))
            ->unique()->values()->all();
    }

    public function hasPermission(string $slug): bool
    {
        return in_array($slug, $this->permissionSlugs(), true);
    }

    /** Permission held specifically in a given office (for office-scoped actions). */
    public function hasPermissionInOffice(string $slug, int $officeId): bool
    {
        $m = $this->memberships()->with('role.permissions')->where('office_id', $officeId)->first();

        return $m && $m->role->permissions->contains('slug', $slug);
    }

    public function isActiveAccount(): bool
    {
        return (bool) $this->is_active;
    }
}
