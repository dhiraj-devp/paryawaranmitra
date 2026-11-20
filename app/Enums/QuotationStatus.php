<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('ui.quotations.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Accepted => 'success',
            self::Sent, self::Expired => 'warning',
            self::Rejected, self::Cancelled => 'danger',
            default => 'neutral',
        };
    }

    public function needsReason(): bool
    {
        return in_array($this, [self::Rejected, self::Expired, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::Accepted, self::Rejected, self::Expired, self::Cancelled],
            self::Accepted => [self::Cancelled],
            default => [],
        };
    }

    public function allows(self $next): bool
    {
        return in_array($next, $this->next(), true);
    }

    public function canRevise(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Accepted], true);
    }
}
