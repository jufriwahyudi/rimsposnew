<?php

use App\Models\RoleMaster;

if (! function_exists('activeRole')) {
    function activeRole()
    {
        return RoleMaster::find(session('selected_role'));
    }
}

if (! function_exists('isStore')) {
    function isStore(): bool
    {
        return activeRole()?->role_type === 'STORE';
    }
}

if (! function_exists('isWarehouse')) {
    function isWarehouse(): bool
    {
        return activeRole()?->role_type === 'WAREHOUSE';
    }
}

if (! function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return activeRole()?->role_type === 'ADMIN';
    }
}
