<?php

namespace App\Concerns;

use App\Models\ActivityLog;

/**
 * Panelde (oturum açmış kullanıcı) yapılan oluşturma/güncelleme/silme işlemlerini
 * işlem günlüğüne yazar. Konsol ve vitrin (misafir) işlemleri yazılmaz; toplu
 * aktarımlar günlüğü şişirmesin.
 */
trait LogsActivity
{
    /** Günlüğe yazılmayacak alanlar. */
    protected static array $activityIgnore = ['updated_at', 'created_at', 'remember_token', 'password', 'used_count', 'hits', 'last_hit_at'];

    public static function bootLogsActivity(): void
    {
        static::created(fn ($m) => $m->logActivity('created'));
        static::updated(fn ($m) => $m->logActivity('updated'));
        static::deleted(fn ($m) => $m->logActivity('deleted'));
    }

    protected function logActivity(string $action): void
    {
        if (! auth()->check() || ! auth()->user()->is_admin || app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }
        $changes = null;
        if ($action === 'updated') {
            $changes = [];
            foreach ($this->getChanges() as $k => $new) {
                if (in_array($k, static::$activityIgnore, true)) {
                    continue;
                }
                $old = $this->getOriginal($k);
                $changes[$k] = [
                    'eski' => is_scalar($old) || $old === null ? mb_substr((string) $old, 0, 200) : '…',
                    'yeni' => is_scalar($new) || $new === null ? mb_substr((string) $new, 0, 200) : '…',
                ];
            }
            if (! $changes) {
                return;
            }
        }
        ActivityLog::record($action, $this, $this->activityLabel(), $changes);
    }

    public function activityLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? $this->code ?? $this->number ?? $this->key ?? $this->from_path ?? '#'.$this->getKey());
    }
}
