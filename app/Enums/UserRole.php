<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Pengajar   = 'pengajar';
    case Murid      = 'murid';
    case WaliMurid  = 'wali_murid';
}
