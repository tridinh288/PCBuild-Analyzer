<?php

namespace App\Enums;

/**
 * Purpose of a template build. Also used as the analysis profile (D-016).
 */
enum BuildPurpose: string
{
    case Gaming = 'gaming';
    case Programming = 'programming';
    case Workstation = 'workstation';
    case GeneralUse = 'general_use';

    public function label(): string
    {
        return match ($this) {
            self::Gaming => 'Chơi game',
            self::Programming => 'Lập trình',
            self::Workstation => 'Đồ họa / Workstation',
            self::GeneralUse => 'Sử dụng chung',
        };
    }
}
