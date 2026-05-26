<?php
declare(strict_types=1);

if (!function_exists('DTMIS_resolve_role_office_group')) {
    function DTMIS_resolve_role_office_group(string $roleName): string
    {
        $normalized = strtoupper(trim($roleName));
        if (str_starts_with($normalized, 'PAMO_') || str_starts_with($normalized, 'PASU_')) {
            return 'PAMO';
        }

        if (str_starts_with($normalized, 'CENRO_')) {
            return 'CENRO';
        }

        if (str_starts_with($normalized, 'PENRO_')) {
            return 'PENRO';
        }

        return 'Regional';
    }
}

if (!function_exists('DTMIS_resolve_fixed_office_ids')) {
    function DTMIS_resolve_fixed_office_ids(array $offices): array
    {
        $rolePatterns = [
            'ARD_MS' => [
                ['ARD MS'],
                ['ASSISTANT REGIONAL DIRECTOR', 'MANAGEMENT'],
            ],
            'ARD_TS' => [
                ['ARD TS'],
                ['ASSISTANT REGIONAL DIRECTOR', 'TECHNICAL'],
            ],
            'ORED' => [
                ['(ORED)'],
                ['REGIONAL EXECUTIVE DIRECTOR'],
            ],
            'PACDO' => [
                ['PACDO'],
                ['PUBLIC AFFAIRS', 'COMMUNICATION DEVELOPMENT'],
            ],
        ];

        $resolved = [];
        foreach ($rolePatterns as $roleName => $tokenSets) {
            foreach ($offices as $office) {
                $officeName = strtoupper(trim((string)($office['name'] ?? '')));
                $matched = false;
                foreach ($tokenSets as $tokenSet) {
                    $allPresent = true;
                    foreach ($tokenSet as $token) {
                        if (!str_contains($officeName, strtoupper($token))) {
                            $allPresent = false;
                            break;
                        }
                    }

                    if ($allPresent) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $resolved[$roleName] = (string)($office['id'] ?? '');
                    break;
                }
            }
        }

        return $resolved;
    }
}

if (!function_exists('DTMIS_is_office_allowed_for_role')) {
    function DTMIS_is_office_allowed_for_role(string $roleName, string $officeLevel, string $officeName): bool
    {
        $role = strtoupper(trim($roleName));
        $level = strtoupper(trim($officeLevel));
        $name = strtoupper(trim($officeName));

        if ($role === '') {
            return true;
        }

        if ($role === 'CENRO_ADMIN_RECORD') {
            return $level === 'CENRO_ADMIN_RECORD' || (str_contains($name, 'CENRO') && str_contains($name, 'ADMIN RECORD'));
        }

        if ($role === 'CENRO_OFFICER') {
            return $level === 'CENRO_OFFICER' || (str_contains($name, 'CENRO') && str_contains($name, 'OFFICER'));
        }

        if ($role === 'CENRO_SECTION') {
            return $level === 'CENRO_SECTION';
        }

        if ($role === 'CENRO_UNIT') {
            return $level === 'CENRO_UNIT';
        }

        if ($role === 'PENRO_ADMIN_RECORD') {
            return $level === 'PENRO_ADMIN_RECORD' || (str_contains($name, 'PENRO') && str_contains($name, 'ADMIN RECORD'));
        }

        if ($role === 'PENRO_OFFICER') {
            return $level === 'PENRO_OFFICER' || (str_contains($name, 'PENRO') && str_contains($name, 'OFFICER'));
        }

        if ($role === 'PENRO_DIVISION') {
            return $level === 'PENRO_DIVISION';
        }

        if ($role === 'PENRO_SECTION') {
            return $level === 'PENRO_SECTION';
        }

        if ($role === 'PENRO_SECTION_UNIT') {
            return $level === 'PENRO_SECTION' || $level === 'PENRO_UNIT';
        }

        if ($role === 'PAMO_ADMIN') {
            return $level === 'PAMO_ADMIN' || (str_contains($name, 'PAMO') && str_contains($name, 'ADMIN'));
        }

        if ($role === 'PASU_OFFICER') {
            return $level === 'PASU_OFFICER' || (str_contains($name, 'PASU') && str_contains($name, 'OFFICER'));
        }

        if ($role === 'PAMO_UNIT') {
            return $level === 'PAMO_UNIT';
        }

        if ($role === 'DIVISION_CHIEF') {
            return $level === 'DIVISION' || $level === 'PENRO_DIVISION';
        }

        if ($role === 'SECTION_STAFF') {
            return $level === 'SECTION' || $level === 'CENRO_SECTION' || $level === 'PENRO_SECTION' || $level === 'PENRO_UNIT';
        }

        return true;
    }
}

if (!function_exists('DTMIS_build_office_directory')) {
    function DTMIS_build_office_directory(array $offices): array
    {
        $validOfficeIds = [];
        $officeNameById = [];
        $officeLevelById = [];
        $officeGroups = [
            'PAMO' => [],
            'CENRO' => [],
            'PENRO' => [],
            'Regional' => [],
        ];
        $officeGroupById = [];

        foreach ($offices as $office) {
            $officeId = (string)($office['id'] ?? '');
            $officeName = (string)($office['name'] ?? '');
            $officeLevel = (string)($office['level'] ?? '');

            $validOfficeIds[$officeId] = true;
            $officeNameById[$officeId] = $officeName;
            $officeLevelById[$officeId] = $officeLevel;

            $officeLevelUpper = strtoupper(trim($officeLevel));
            $officeNameUpper = strtoupper(trim($officeName));

            $groupKey = 'Regional';
            if (
                str_contains($officeLevelUpper, 'PAMO')
                || str_contains($officeLevelUpper, 'PASU')
                || str_contains($officeLevelUpper, 'PROTECTED AREA')
                || str_contains($officeNameUpper, 'PAMO')
                || str_contains($officeNameUpper, 'PASU')
            ) {
                $groupKey = 'PAMO';
            } elseif (
                str_contains($officeLevelUpper, 'CENRO')
                || $officeLevelUpper === 'COMMUNITY'
                || str_contains($officeNameUpper, 'CENRO')
            ) {
                $groupKey = 'CENRO';
            } elseif (
                str_contains($officeLevelUpper, 'PENRO')
                || $officeLevelUpper === 'PROVINCIAL'
                || str_contains($officeNameUpper, 'PENRO')
            ) {
                $groupKey = 'PENRO';
            }

            $officeGroups[$groupKey][] = $office;
            $officeGroupById[$officeId] = $groupKey;
        }

        $officeOptionsPayload = [];
        foreach ($officeGroups as $groupLabel => $groupOffices) {
            foreach ($groupOffices as $office) {
                $officeOptionsPayload[] = [
                    'value' => (string)($office['id'] ?? ''),
                    'text' => (string)($office['name'] ?? ''),
                    'group' => $groupLabel,
                    'level' => (string)($office['level'] ?? ''),
                ];
            }
        }

        return [
            'valid_office_ids' => $validOfficeIds,
            'office_name_by_id' => $officeNameById,
            'office_level_by_id' => $officeLevelById,
            'office_groups' => $officeGroups,
            'office_group_by_id' => $officeGroupById,
            'office_options_payload' => $officeOptionsPayload,
            'fixed_office_id_by_role_name' => DTMIS_resolve_fixed_office_ids($offices),
        ];
    }
}

if (!function_exists('DTMIS_validate_role_office_assignment')) {
    function DTMIS_validate_role_office_assignment(string $roleName, string $officeId, array $officeDirectory): string
    {
        $validOfficeIds = is_array($officeDirectory['valid_office_ids'] ?? null) ? $officeDirectory['valid_office_ids'] : [];
        $officeNameById = is_array($officeDirectory['office_name_by_id'] ?? null) ? $officeDirectory['office_name_by_id'] : [];
        $officeLevelById = is_array($officeDirectory['office_level_by_id'] ?? null) ? $officeDirectory['office_level_by_id'] : [];
        $officeGroupById = is_array($officeDirectory['office_group_by_id'] ?? null) ? $officeDirectory['office_group_by_id'] : [];
        $fixedOfficeIdByRoleName = is_array($officeDirectory['fixed_office_id_by_role_name'] ?? null) ? $officeDirectory['fixed_office_id_by_role_name'] : [];

        $normalizedRoleName = strtoupper(trim($roleName));
        $officeIdKey = (string)$officeId;

        if ($officeIdKey === '' || !isset($validOfficeIds[$officeIdKey])) {
            return 'Selected office does not exist.';
        }

        $selectedOfficeName = strtoupper(trim((string)($officeNameById[$officeIdKey] ?? '')));
        $selectedOfficeLevel = strtoupper(trim((string)($officeLevelById[$officeIdKey] ?? '')));
        $selectedOfficeGroup = (string)($officeGroupById[$officeIdKey] ?? '');
        $allowedOfficeGroup = DTMIS_resolve_role_office_group($normalizedRoleName);
        $fixedOfficeId = (string)($fixedOfficeIdByRoleName[$normalizedRoleName] ?? '');

        if ($fixedOfficeId !== '' && $officeIdKey !== $fixedOfficeId) {
            return sprintf('The %s role is assigned to one office only.', $normalizedRoleName);
        }

        if ($selectedOfficeGroup === '' || $selectedOfficeGroup !== $allowedOfficeGroup) {
            return sprintf('Select an office under %s.', $allowedOfficeGroup);
        }

        if (!DTMIS_is_office_allowed_for_role($normalizedRoleName, $selectedOfficeLevel, $selectedOfficeName)) {
            return 'Select an office that matches the selected role.';
        }

        return '';
    }
}
